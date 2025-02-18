<?php

namespace App\Services;

use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Utils;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use OpenAI;
use App\Helpers\ConsoleOutput;

class OpenAIService
{
    protected OpenAI\Client $client;
    protected mixed $assistantId;

    protected PrinciplesService $principlesService;

    public function __construct(PrinciplesService $principlesService)
    {
        $this->client = OpenAI::client(config('services.openai.api_key'));
        $this->assistantId = config('services.openai.assistant_id');
        $this->principlesService = $principlesService;
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
            ConsoleOutput::log("Thread $threadId closed successfully. No call exists for API");
        } catch (\Exception $e) {
            ConsoleOutput::log("Error closing thread $threadId: " . $e->getMessage(), [], 'error');
        }
    }

    public function uploadDocumentToOpenAIFresh(string $uid): ?string
    {
        // Retrieve the PDF results using the principlesService.
        // Here, we're assuming that the $uid array contains the unique identifier needed.
        // You may adjust this if you expect a single UID value.
        $pdfResults = $this->principlesService->getPdfResults($uid);
        $pdfContent = $pdfResults->body(); // Extract the raw PDF content

        // Use a .pdf extension for the PDF file.
        $fileName = 'pdf_upload_' . uniqid() . '.pdf';
        $filePath = storage_path('app/' . $fileName);

        ConsoleOutput::log("Uploading PDF document: " . $fileName);

        // Save the PDF content to a local file.
        Storage::disk('local')->put($fileName, $pdfContent);

        $fileId = null;

        try {
            // Ensure the file exists and is readable.
            if (!file_exists($filePath) || !is_readable($filePath)) {
                ConsoleOutput::log("File not found or not readable: $filePath", [], 'error');
                return null;
            }

            // Open the file for reading.
            $handle = fopen($filePath, 'r');
            if (!$handle) {
                ConsoleOutput::log("Failed to open file: $filePath", [], 'error');
                return null;
            }

            // Upload the file to OpenAI.
            // The file is uploaded with a purpose of 'assistants'.
            $uploadedFile = $this->client->files()->upload([
                'purpose' => 'assistants',
                'file' => $handle,
            ]);

            ConsoleOutput::log("Message after upload: ", (array)$uploadedFile->toArray());

            $fileId = $uploadedFile->id ?? null;

            if (is_resource($handle)) {
                fclose($handle);
            } else {
                ConsoleOutput::log("File handle is not a valid resource. Skipping fclose.");
            }
        } catch (\Exception $e) {
            ConsoleOutput::log("Error uploading PDF to OpenAI: " . $e->getMessage(), [], 'error');
        } finally {
            // Clean up the temporary file.
            Storage::disk('local')->delete($fileName);
        }

        return $fileId;
    }

    /**
     * @throws \Exception
     */
    public function sendMessageToThread($threadId, $message): string
    {
        $attachments = [];

        // Send the message with the file attachment (if available).
        $this->client->threads()->messages()->create($threadId, [
            'role' => 'user',
            'content' => $message,
            'attachments' => $attachments,
        ]);

        // Create a run to get the AI's response.
        $run = $this->client->threads()->runs()->create($threadId, [
            'assistant_id' => $this->assistantId,
        ]);

        // Log::info(json_encode($run, JSON_PRETTY_PRINT));

        return $run->id;
    }

    public function getResponse($threadId, $runId): string
    {
        $maxRetries = 10; // Stop retrying after 5 attempts
        $attempt = 0;
        $waitTime = 2; // Start polling at 5s
        $rateLimitCount = 0; // Track consecutive rate limit failures

        do {
            ConsoleOutput::log("Sleeping for $waitTime seconds...");
            sleep($waitTime);
            ConsoleOutput::log("Woke up. Let get to work!");
            $attempt++;

            try {
                $run = $this->client->threads()->runs()->retrieve($threadId, $runId);
                ConsoleOutput::log("Waiting for OpenAI response... Attempt: $attempt, Status: {$run->status}");

                // Check if the error message indicates that the request is too large
                if (isset($run->lastError) && str_contains($run->lastError->message, 'Request too large for')) {
                    ConsoleOutput::log("Job cancelled because the request is too large: " . $run->lastError->message, [], 'error');
                    // Throw an exception to cancel the job immediately.
                    throw new \Exception("Job cancelled: Request too large.");
                }

                // If we hit a rate limit, check for the Retry-After header.
                if ($run->lastError && $run->lastError->code === 'rate_limit_exceeded') {
                    $retryAfter = null;
                    // Attempt to read the header if available.
                    if (isset($run->lastError->headers) && is_array($run->lastError->headers)) {
                        $retryAfterHeader = $run->lastError->headers['Retry-After'] ?? null;
                        if ($retryAfterHeader) {
                            $retryAfter = (int)$retryAfterHeader[0];
                        }
                    }
                    // Fallback if the header is missing.
                    if (!$retryAfter) {
                        $retryAfter = pow(2, $attempt);
                    }

                    ConsoleOutput::log("Rate limit hit! Waiting {$retryAfter} seconds before retrying...");
                    $rateLimitCount++;

                    if ($rateLimitCount >= 3) { // Stop retrying after 3 consecutive rate limits
                        ConsoleOutput::log("Rate limit exceeded 3 times in a row. Waiting 5 minutes before retrying...");
                        sleep(30); // 30 seconds cooldown
                        $rateLimitCount = 0; // Reset counter
                    } else {
                        sleep($retryAfter);
                    }

                    continue;
                }

                if ($run->status === 'failed' || $run->status === 'cancelled') {
                    ConsoleOutput::log("OpenAI request failed/cancelled. Attempting to rerun...");
                    ConsoleOutput::log(json_encode($run, JSON_PRETTY_PRINT));

                    return "Failed due to OpenAI restrictions.";
                }
            } catch (RequestException $e) {
                // If a Guzzle request exception is caught, try to get the Retry-After header
                if ($e->hasResponse()) {
                    $response = $e->getResponse();
                    $retryAfterHeader = $response->getHeader('Retry-After');
                    if (!empty($retryAfterHeader)) {
                        $retryAfter = (int)$retryAfterHeader[0];
                        ConsoleOutput::log("Rate limit reached (RequestException). Waiting for {$retryAfter} seconds before retrying...");
                        sleep($retryAfter);
                        continue;
                    }
                }
                ConsoleOutput::log("Error retrieving OpenAI response: " . $e->getMessage(), [], 'error');
                return "Error retrieving response. Please try again later.";
            } catch (\Exception $e) {
                ConsoleOutput::log("Error retrieving OpenAI response: " . $e->getMessage(), [], 'error');
                return "Error retrieving response. Please try again later.";
            }

            // $waitTime = min($waitTime + 3, 20); // Increase polling interval up to 20s

        } while ($run->status !== 'completed' && $attempt < $maxRetries);

        if ($run->status !== 'completed') {
            ConsoleOutput::log("OpenAI response timed out after $maxRetries attempts.");
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
            ConsoleOutput::log("Error fetching last user message: " . $e->getMessage(), [], 'error');
        }

        return null;
    }

    /**
     * Reruns the assistant process using the last user message.
     */
    private function rerunAssistant($threadId, $assistantId): string
    {
        try {
            ConsoleOutput::log("Retrying assistant due to OpenAI failure...");

            // Create a new run
            $newRun = $this->client->threads()->runs()->create($threadId, [
                'assistant_id' => $assistantId
            ]);

            ConsoleOutput::log("Re-running assistant with assistant_id: {$assistantId}");

            return $this->getResponse($threadId, $newRun->id);
        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();

            // Handle rate limits from exceptions directly
            if (str_contains($errorMessage, 'rate limit')) {
                if (method_exists($e, 'getResponse') && $e->getResponse()) {
                    $response = $e->getResponse();
                    $retryAfterHeader = $response->getHeader('Retry-After');
                    $waitTime = !empty($retryAfterHeader) ? (int)$retryAfterHeader[0] : 10;
                    ConsoleOutput::log("Rate limit hit! Waiting for {$waitTime} seconds before retrying...");
                    sleep($waitTime);
                } else {
                    sleep(5);
                }

                return $this->rerunAssistant($threadId, $assistantId);
            }

            ConsoleOutput::log("Error re-running assistant: " . $errorMessage, [], 'error');
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
            ConsoleOutput::log("Error fetching messages: " . $e->getMessage(), [], 'error');
            return "Error retrieving response.";
        }

        return 'No assistant response found.';
    }
}
