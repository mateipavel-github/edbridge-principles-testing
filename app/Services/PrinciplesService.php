<?php

namespace App\Services;

use App\Exceptions\PrinciplesApiException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class PrinciplesService
{
    const ANSWER_DISAGREE_STRONGLY = 'disagree_strongly';
    const ANSWER_DISAGREE = 'disagree';
    const ANSWER_DISAGREE_SLIGHTLY = 'disagree_slightly';
    const ANSWER_NEITHER_AGREE_NOR_DISAGREE = 'neither_agree_nor_disagree';
    const ANSWER_AGREE_SLIGHTLY = 'agree_slightly';
    const ANSWER_AGREE = 'agree';
    const ANSWER_AGREE_STRONGLY = 'agree_strongly';
    const QUESTION_POSSIBLE_ANSWERS = [
        self::ANSWER_DISAGREE_STRONGLY => [
            'label' => 'Disagree strongly',
            'value' => 1,
        ],
        self::ANSWER_DISAGREE => [
            'label' => 'Disagree',
            'value' => 2,
        ],
        self::ANSWER_DISAGREE_SLIGHTLY => [
            'label' => 'Disagree slightly',
            'value' => 3,
        ],
        self::ANSWER_NEITHER_AGREE_NOR_DISAGREE => [
            'label' => 'Neither agree nor disagree',
            'value' => 4,
        ],
        self::ANSWER_AGREE_SLIGHTLY => [
            'label' => 'Agree slightly',
            'value' => 5,
        ],
        self::ANSWER_AGREE => [
            'label' => 'Agree',
            'value' => 6,
        ],
        self::ANSWER_AGREE_STRONGLY => [
            'label' => 'Agree strongly',
            'value' => 7,
        ]
    ];

    /**
     * The base URL for the API.
     *
     * @var string
     */
    private string $baseUrl;

    /**
     * The bearer token for the API.
     *
     * @var string
     */
    private string $bearerToken;

    public function __construct()
    {
        $this->baseUrl = config('principles.baseUrl');
    }

    /**
     * Get the bearer token for the API from cache, requests another one if none present (ttl 60 minutes).
     *
     * @return string
     * @throws PrinciplesApiException
     */
    private function getBearerToken(): string
    {
        // Return the token if it's already been fetched.
        if (!empty($this->bearerToken)) {
            return $this->bearerToken;
        }

        try {
            Log::info('Checking for existing token in cache.');
            $token = Cache::get('principles-bearer-token');

            if ($token !== null) {
                Log::info('Token found in cache.');
                return $this->bearerToken = $token;
            }

            Log::info('No token found in cache, requesting new token from API.');
            $response = Http::asForm()->withBasicAuth(
                config('principles.clientId'),
                config('principles.clientSecret')
            )->post(config('principles.authUrl'), [
                'grant_type' => 'client_credentials',
                'scope' => 'com.principles.kernel/integration_account:use',
            ]);

            Log::info('API Response: ' . $response->body());

            if ($response->failed()) {
                throw new PrinciplesApiException("API returned an error: " . $response->status());
            }

            $decodedResponse = $response->json();
            if (!isset($decodedResponse['access_token'])) {
                throw new PrinciplesApiException("API response does not contain an access token.");
            }

            Cache::put('principles-bearer-token', $decodedResponse['access_token'], $decodedResponse['expires_in'] / 60);

            Log::info('New token stored in cache.');

            return $this->bearerToken = $decodedResponse['access_token'];
        } catch (\Exception $exception) {
            Log::error("Failed to get the bearer token: {$exception->getMessage()}");
            throw new PrinciplesApiException("Failed to get the bearer token from the Principles API: {$exception->getMessage()}");
        }
    }


    /**
     * Create a student in the context of the tenant.
     *
     * @param string $email
     * @param string $displayName
     * @return array
     * @throws PrinciplesApiException
     */
    public function createStudent(string $email, string $displayName): array
    {
        try {
            $response = Http::withToken($this->getBearerToken())->post(
                "{$this->baseUrl}/api/v1/integration_account_tenants/{$this->getTenant()}/users",
                [
                    'email' => $email,
                    'displayName' => $displayName,
                ]
            );

            $decodedResponse = $response->json();

            if ($response->status() !== 200) {
                throw new PrinciplesApiException("API response: {$response->status()} {$decodedResponse['message']}");
            }

            return [
                'account_id' => $decodedResponse['tenantUser']['account']['accountId'],
                'person_id' => $decodedResponse['tenantUser']['person']['personId'],
            ];
        } catch (\Exception $exception) {
            throw new PrinciplesApiException("Failed to create the student in the Principles API: {$exception->getMessage()}");
        }
    }

    /**
     * Get the tenant ID from the config.
     *
     * @return string
     * @throws PrinciplesApiException
     */
    private function getTenant(): string
    {
        $tenantId = config('principles.tenantId');

        if ($tenantId === null) {
            throw new PrinciplesApiException('Tenant ID is not set in the config/principles.php file.');
        }

        return $tenantId;
    }

    /**
     * Create a tenant in the Principles API.
     *
     * @param string $name
     * @return string
     * @throws PrinciplesApiException
     */
    public function createTenant(string $name): string
    {
        try {
            $response = Http::withToken($this->getBearerToken())->post(
                "{$this->baseUrl}/api/v1/integration_account_tenants",
                [
                    'fields' => [
                        'name' => $name
                    ],
                ]
            );

            $decodedResponse = $response->json();

            if ($response->status() !== 200) {
                throw new PrinciplesApiException("API response: {$response->status()} {$decodedResponse['message']}");
            }

            return $decodedResponse['tenant']['tenantId'];
        } catch (\Exception $exception) {
            throw new PrinciplesApiException("Failed to create the tenant in the Principles API: {$exception->getMessage()}");
        }
    }

    /**
     * Get the next set of questions from the Principles API.
     *
     * @param string $accountUid
     * @return array
     * @throws PrinciplesApiException
     */
    public function getNextQuestions(string $accountUid): array
    {

        try {
            $response = Http::withToken($this->getBearerToken())
                ->withHeaders([
                    'x-on-behalf-of' => $accountUid,
                ])
                ->get(
                    "{$this->baseUrl}/api/v1/assessment/questions"
                );

            if ($response->status() !== 200) {
                throw new PrinciplesApiException("API response: {$response->status()} {$response->json()['message']}");
            }

            $decodedResponse = $response->json();
            $translationsArray = json_decode(Storage::get('principles-questions.json'));

            return [
                'assessmentProgress' => $decodedResponse['assessmentProgress'],
                'questions' => collect($decodedResponse['questions'])->map(function ($question, $index) use ($translationsArray) {
                    $translatedQuestion = collect($translationsArray)->where('number', $question['number'])->first();

                    return [
                        'text' => "{$question['text']}",
                        'explanation' => "{$translatedQuestion->explanation}",
                        'number' => $question['number'],
                    ];
                })
            ];
        } catch (\Exception $exception) {
            throw new PrinciplesApiException("Failed to get the next set of questions from the Principles API: {$exception->getMessage()}");
        }
    }

    /**
     * Store the answers to the personality test.
     *
     * @param string $accountUid
     * @param array $answers
     * @throws PrinciplesApiException
     */
    public function storeAnswers(string $accountUid, array $answers): void
    {
        try {
            $response = Http::withToken($this->getBearerToken())
                ->withHeaders([
                    'x-on-behalf-of' => $accountUid,
                ])
                ->post(
                    "{$this->baseUrl}/api/v1/assessment/answers",
                    [
                        'answers' => $answers,
                    ]
                );

            if ($response->status() !== 200) {
                throw new PrinciplesApiException("API response: {$response->status()} {$response->json()['message']}");
            }
        } catch (\Exception $exception) {
            throw new PrinciplesApiException("Failed to store the answers in the Principles API: {$exception->getMessage()}");
        }
    }

    /**
     * Get the results of the personality test.
     *
     * @param string $accountUid
     * @return array
     * @throws PrinciplesApiException
     */
    public function getResults(string $accountUid): array
    {
        try {
            $response = Http::withToken($this->getBearerToken())
                ->withHeaders([
                    'x-on-behalf-of' => $accountUid,
                ])
                ->get(
                    "{$this->baseUrl}/api/v2/assessment_results/{$accountUid}"
                );

            if ($response->status() !== 200) {
                throw new PrinciplesApiException("API response: {$response->status()} {$response->json()['message']}");
            }

            return $response->json();
        } catch (\Exception $exception) {
            throw new PrinciplesApiException("Failed to get the results from the Principles API: {$exception->getMessage()}");
        }
    }

    /**
     * Get the results of the personality test.
     *
     * @param string $accountUid
     * @param array $occupationWeightings
     * @return array
     * @throws PrinciplesApiException
     */
    public function getCareerCompatibilityScore(string $accountUid, array $occupationWeightings): array
    {
        try {
            $response = Http::withToken($this->getBearerToken())
                ->withHeaders([
                    'x-on-behalf-of' => $accountUid,
                    'Accept' => 'application/json',
                ])
                ->post(
                    "{$this->baseUrl}/api/v1/ppm/accounts/{$accountUid}/custom_occupations_error_margins",
                    $occupationWeightings
                );

            if ($response->status() !== 200) {
                throw new PrinciplesApiException("API response: {$response->status()} " . json_encode($response->json()));
            }

            return $response->json();
        } catch (\Exception $exception) {
            throw new PrinciplesApiException("Failed to get the results from the Principles API: {$exception->getMessage()}");
        }
    }

    /**
     * Get the RIASEC results of the personality test.
     *
     * @param string $accountUid
     * @return array
     * @throws PrinciplesApiException
     */

    public function getPpmOccupations(string $accountUid): array
    {
        try {
            $response = Http::withToken($this->getBearerToken())
                ->withHeaders([
                    'x-on-behalf-of' => $accountUid,
                ])
                ->get(
                    "{$this->baseUrl}/api/v1/ppm/accounts/{$accountUid}/occupations?pageSize=1500"
                );

            if ($response->status() !== 200) {
                throw new PrinciplesApiException("API response: {$response->status()} {$response->json()['message']}");
            }

            return $response->json();
        } catch (\Exception $exception) {
            throw new PrinciplesApiException("Failed to get the RIASEC results from the Principles API: {$exception->getMessage()}");
        }
    }

    public function getPpmScores(string $accountUid): array
    {
        try {
            $response = Http::withToken($this->getBearerToken())
                ->withHeaders([
                    'x-on-behalf-of' => $accountUid,
                ])
                ->get(
                    "{$this->baseUrl}/api/v1/ppm/accounts/{$accountUid}/score"
                );

            if ($response->status() !== 200) {
                throw new PrinciplesApiException("API response: {$response->status()} {$response->json()['message']}");
            }
            return $response->json();
        } catch (\Exception $exception) {
            throw new PrinciplesApiException("Failed to get the RIASEC results from the Principles API: {$exception->getMessage()}");
        }
    }

    public function getRiasecOccupations(string $accountUid): array
    {
        try {
            $response = Http::withToken($this->getBearerToken())
                ->withHeaders([
                    'x-on-behalf-of' => $accountUid,
                ])
                ->get(
                    "{$this->baseUrl}/api/v1/accounts/{$accountUid}/riasec_occupations"
                );

            if ($response->status() !== 200) {
                throw new PrinciplesApiException("API response: {$response->status()} {$response->json()['message']}");
            }

            return $response->json();
        } catch (\Exception $exception) {
            throw new PrinciplesApiException("Failed to get the RIASEC results from the Principles API: {$exception->getMessage()}");
        }
    }

    /**
     * Get the RIASEC scores of the personality test.
     *
     * @param string $accountUid
     * @return array
     * @throws PrinciplesApiException
     */
    public function getRiasecScores(string $accountUid): array
    {
        try {
            $response = Http::withToken($this->getBearerToken())
                ->withHeaders([
                    'x-on-behalf-of' => $accountUid,
                ])
                ->get(
                    "{$this->baseUrl}/api/v1/accounts/{$accountUid}/riasec"
                );

            if ($response->status() !== 200) {
                throw new PrinciplesApiException("API response: {$response->status()} {$response->json()['message']}");
            }

            return $response->json();
        } catch (\Exception $exception) {
            throw new PrinciplesApiException("Failed to get the RIASEC scores from the Principles API: {$exception->getMessage()}");
        }
    }

    /**
     * Get the PDF results.
     *
     * @param string $accountUid
     * @return \GuzzleHttp\Promise\PromiseInterface|\Illuminate\Http\Client\Response
     * @throws PrinciplesApiException
     */
    public function getPdfResults(string $accountUid): \GuzzleHttp\Promise\PromiseInterface|\Illuminate\Http\Client\Response
    {
        try {
            return Http::withToken($this->getBearerToken())
                ->withHeaders([
                    'x-on-behalf-of' => $accountUid,
                ])
                ->get(
                    "{$this->baseUrl}/api/v1/assessment_results/{$accountUid}/pdf"
                );
        } catch (\Exception $exception) {
            throw new PrinciplesApiException("Failed to get the PDF generation from the Principles API: {$exception->getMessage()}");
        }
    }

    /**
     * Get information about the current user.
     *
     * @return array
     * @throws PrinciplesApiException
     */
    public function info(): array
    {
        try {
            $response = Http::withToken($this->getBearerToken())
                ->get("{$this->baseUrl}/api/v2/me");

            if ($response->status() !== 200) {
                throw new PrinciplesApiException("API response: {$response->status()} {$response->json()['message']}");
            }

            return $response->json();
        } catch (\Exception $exception) {
            throw new PrinciplesApiException("Failed to get the user info from the Principles API: {$exception->getMessage()}");
        }
    }
}
