<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use OpenAI;

class OpenAIService
{
    protected OpenAI\Client $client;
    protected mixed $assistantId;

    public function __construct()
    {
        $this->client = OpenAI::client(config('services.openai.api_key'));
        $this->assistantId = config('services.openai.assistant_id');
    }

    public function createThread(string $context = "You are a helpful assistant."): string
    {
        // Add any advanced instructions right inside the user message
        $enhancedContext = $context . "\n\n"
            . "IMPORTANT: Do NOT mention the process. Do NOT say phrases like "
            . "'To create your personalized introduction...' or 'I will analyze...'. "
            . "Just give me the final answer in a concise, cohesive format, with no filler.\n";

        // Because the library only supports 'user' and 'assistant', we use 'user'
        $thread = $this->client->threads()->create([
            'messages' => [
                [
                    'role' => 'user',
                    'content' => $enhancedContext
                ]
            ]
        ]);

        return $thread->id;
    }

    public function closeThread($threadId): void
    {
        try {
            // Assuming the API supports closing the thread
            Log::info("Thread $threadId closed successfully. No call exists for API");
        } catch (\Exception $e) {
            Log::error("Error closing thread $threadId: " . $e->getMessage());
        }
    }

    public function uploadJsonToOpenAI(array $jsonData): ?string
    {
        $cacheKey = 'openai_file_' . md5(json_encode($jsonData)); // Unique cache key based on file content

        Cache::forget($cacheKey);

        // Check if file is already uploaded
        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        // Define a temporary file path in Laravel's storage
        $fileName = 'json_upload_' . uniqid() . '.json';
        $filePath = storage_path('app/' . $fileName);

        // Save JSON data to the file
        Storage::disk('local')->put($fileName, json_encode($jsonData));

        try {
            // Upload the file to OpenAI
            $uploadedFile = $this->client->files()->upload([
                'purpose' => 'assistants',
                'file' => fopen($filePath, 'r'),
            ]);

            // Get the file ID
            $fileId = $uploadedFile->id ?? null;

            // Store file ID in cache for reuse (expire after 20 minutes)
            if ($fileId) {
                Cache::put($cacheKey, $fileId, now()->addMinutes(20));
            }
        } finally {
            // Remove the temporary file
            Storage::disk('local')->delete($fileName);
        }

        return $fileId;
    }

    public function uploadJsonToOpenAIFresh(array $jsonData): ?string
    {
        // Fresh upload: do not use caching.
        $fileName = 'json_upload_' . uniqid() . '.txt';
        $filePath = storage_path('app/' . $fileName);

        // Save JSON data to the file
        Storage::disk('local')->put($fileName, json_encode($jsonData));

        try {
            // Upload the file to OpenAI
            $uploadedFile = $this->client->files()->upload([
                'purpose' => 'fine-tune',
                'file' => fopen($filePath, 'r'),
            ]);

            // Get the file ID
            $fileId = $uploadedFile->id ?? null;
        } finally {
            // Remove the temporary file
            Log::info("JSON: ", json_decode(Storage::disk('local')->get($fileName)));
            Storage::disk('local')->delete($fileName);
        }

        return $fileId;
    }

    public function sendMessageToThread($threadId, $message, array $jsonData = null): string
    {

        // Fresh attach the file only if the prompt contains {{personality_profile}} in it.
        $attachments = [];

        if ($jsonData && str_contains($message, 'personality_profile')) {
            $fileId = $this->uploadJsonToOpenAIFresh($jsonData);
            Log::info("Upload fileID $fileId");
            if ($fileId) {
                $attachments[] = [
                    'file_id' => $fileId,
                    'tools' => [
                        ['type' => 'code_interpreter']
                    ],
                ];
            }
        }

        Log::info("Attachments: " . json_encode($attachments, JSON_PRETTY_PRINT));

        // Send the message with the file attachment (if available)
        $this->client->threads()->messages()->create($threadId, [
            'role' => 'user',
            'content' => $message,
            'attachments' => $attachments,
        ]);

        // Create a run to get the AI's response.
        $run = $this->client->threads()->runs()->create($threadId, [
            'assistant_id' => $this->assistantId,
        ]);

        if ($jsonData && str_contains($message, 'personality_profile')) {
            Log::info("--------------------\n\n");
            Log::info(json_encode($run, JSON_PRETTY_PRINT));
            Log::info("--------------------\n\n");
        }

        // Check if the response contains an error about file access.
        if (isset($run->lastError) && str_contains($run->lastError->message, 'does not have access')) {
            Log::warning("Uploaded file expired or inaccessible. Reuploading the JSON data...");

            // Clear the cached file ID.
            $cacheKey = 'openai_file_' . md5(json_encode($jsonData));
            Cache::forget($cacheKey);

            // Reupload the file to get a new file ID.
            $newFileId = $this->uploadJsonToOpenAIFresh($jsonData);
            if ($newFileId) {
                $attachments = [[
                    'file_id' => $newFileId,
                    'tools' => [['type' => 'code_interpreter']],
                ]];

                // Resend the message with the new attachment.
                $this->client->threads()->messages()->create($threadId, [
                    'role' => 'user',
                    'content' => $message,
                    'attachments' => $attachments,
                ]);

                // Create a new run for the re-sent message.
                $run = $this->client->threads()->runs()->create($threadId, [
                    'assistant_id' => $this->assistantId,
                ]);
            }
        }

        // After reupload, if the error still exists, cancel the job.
        if (isset($run->lastError) && str_contains($run->lastError->message, 'does not have access')) {
            Log::error("File reupload attempt did not resolve file access issue. Cancelling job.");
            throw new \Exception("Job cancelled: File still not accessible after reupload.");
        }

        Log::info(json_encode($run, JSON_PRETTY_PRINT));

        return $run->id;
    }

    public function getResponse($threadId, $runId): string
    {
        $maxRetries = 20; // Stop retrying after 10 attempts
        $attempt = 0;
        $waitTime = 5; // Start polling at 5s
        $rateLimitCount = 0; // Track consecutive rate limit failures

        do {
            Log::info("Sleeping for $waitTime seconds...");
            sleep($waitTime);
            Log::info("Woke up. Let get to work!");
            $attempt++;

            try {
                $run = $this->client->threads()->runs()->retrieve($threadId, $runId);
                Log::info("Waiting for OpenAI response... Attempt: $attempt, Status: {$run->status}");

                // Check if the error message indicates that the request is too large
                if (isset($run->lastError) && str_contains($run->lastError->message, 'Request too large for')) {
                    Log::error("Job cancelled because the request is too large: " . $run->lastError->message);
                    // Throw an exception to cancel the job immediately.
                    throw new \Exception("Job cancelled: Request too large.");
                }

                if ($run->status === 'failed' || $run->status === 'cancelled') {
                    Log::warning("OpenAI request failed/cancelled. Attempting to rerun...");
                    Log::info(json_encode($run, JSON_PRETTY_PRINT));

                    // Handle rate limits
                    if ($run->lastError && $run->lastError->code === 'rate_limit_exceeded') {
                        preg_match('/Please try again in ([0-9.]+)s/', $run->lastError->message, $matches);
                        $retryAfter = isset($matches[1]) ? ceil($matches[1]) : pow(2, $attempt);
                        Log::warning("Rate limit hit! Waiting {$retryAfter} seconds before retrying...");

                        $rateLimitCount++;

                        if ($rateLimitCount >= 3) { // Stop retrying after 3 consecutive rate limits
                            Log::error("Rate limit exceeded 3 times in a row. Waiting 5 minutes before retrying...");
                            sleep(300); // 5-minute cooldown
                            $rateLimitCount = 0; // Reset counter
                        } else {
                            sleep($retryAfter);
                        }

                        continue;
                    }

                    return "Failed due to OpenAI restrictions.";
                }
            } catch (\Exception $e) {
                Log::error("Error retrieving OpenAI response: " . $e->getMessage());
                return "Error retrieving response. Please try again later.";
            }

            $waitTime = min($waitTime + 3, 20); // Increase polling interval up to 20s

        } while ($run->status !== 'completed' && $attempt < $maxRetries);

        if ($run->status !== 'completed') {
            Log::error("OpenAI response timed out after $maxRetries attempts.");
            return "OpenAI rate limits are too strict. Please try again later.";
        }

        return $this->fetchAssistantResponse($threadId);
    }


    /**
     * Fetches the last user message from the thread.
     */
    private function getLastUserMessage($threadId): ?string
    {
        try {
            $messages = $this->client->threads()->messages()->list($threadId)->data;
            foreach (array_reverse($messages) as $message) {
                if ($message->role === 'user') {
                    return $message->content[0]->text->value ?? null;
                }
            }
        } catch (\Exception $e) {
            Log::error("Error fetching last user message: " . $e->getMessage());
        }

        return null;
    }

    /**
     * Reruns the assistant process using the last user message.
     */
    private function rerunAssistant($threadId, $assistantId): string
    {
        try {
            Log::warning("Retrying assistant due to OpenAI failure...");

            // Create a new run
            $newRun = $this->client->threads()->runs()->create($threadId, [
                'assistant_id' => $assistantId
            ]);

            Log::info("Re-running assistant with assistant_id: {$assistantId}");

            return $this->getResponse($threadId, $newRun->id);
        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();

            // Handle rate limits
            if (str_contains($errorMessage, 'rate limit')) {
                preg_match('/Please try again in ([0-9.]+)s/', $errorMessage, $matches);
                $waitTime = isset($matches[1]) ? ceil($matches[1]) : 10; // Default 10s if not found

                Log::warning("Rate limit hit! Waiting for {$waitTime} seconds before retrying...");
                sleep($waitTime);

                return $this->rerunAssistant($threadId, $assistantId);
            }

            Log::error("Error re-running assistant: " . $errorMessage);
            return "Failed to rerun assistant.";
        }
    }

    /**
     * Fetches the latest assistant response from the thread.
     */
    private function fetchAssistantResponse($threadId): string
    {
        try {
            $messages = $this->client->threads()->messages()->list($threadId)->data;

            if (empty($messages)) {
                return 'No response received.';
            }

            foreach ($messages as $message) {
                if ($message->role === 'assistant') {
                    return $message->content[0]->text->value ?? 'No response received.';
                }
            }
        } catch (\Exception $e) {
            Log::error("Error fetching messages: " . $e->getMessage());
            return "Error retrieving response.";
        }

        return 'No assistant response found.';
    }
}
