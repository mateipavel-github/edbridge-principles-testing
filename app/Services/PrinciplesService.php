<?php

namespace App\Services;

use App\Exceptions\PrinciplesApiException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class PrinciplesService
{
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
        $this->bearerToken = $this->getBearerToken();
    }

    /**
     * Get the bearer token for the API from cache, requests another one if none present (ttl 60 minutes).
     *
     * @return string
     * @throws PrinciplesApiException
     */
    private function getBearerToken(): string
    {
        try {
            $token = Cache::get('principles-bearer-token');

            if( $token === null ) {
                $response = Http::asForm()->withBasicAuth(
                    config('principles.clientId'),
                    config('principles.clientSecret'),
                )->post(config('principles.authUrl'), [
                    'grant_type' => 'client_credentials',
                    'scope' => 'com.principles.kernel/integration_account:use',
                ]);

                $decodedResponse = $response->json();

                Cache::put('principles-bearer-token', $decodedResponse['access_token'], $decodedResponse['expires_in'] / 60);

                return $decodedResponse['access_token'];
            }
        } catch (\Exception $exception) {
            throw new PrinciplesApiException("Failed to get the bearer token from the Principles API: {$exception->getMessage()}");
        }

        return $token;
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
            $response = Http::withToken($this->bearerToken)->post(
                    "{$this->baseUrl}/api/v1/integration_account_tenants/{$this->getTenant()}/users",
                    [
                        'email' => $email,
                        'displayName' => $displayName,
                    ]
                );

            $decodedResponse = $response->json();

            if( $response->status() !== 200 ) {
                throw new PrinciplesApiException("API response: {$response->status()} {$decodedResponse['message']}");
            }

            return [
                'account_id' => $decodedResponse['tenantUser']['account']['accountId'],
                'person_id' => $decodedResponse['tenantUser']['person']['personId'],
            ];
        }
        catch (\Exception $exception) {
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
     * @return void
     * @throws PrinciplesApiException
     */
    public function createTenant(string $name): string
    {
        try {
            $response = Http::withToken($this->bearerToken)->post(
                "{$this->baseUrl}/api/v1/integration_account_tenants",
                [
                    'fields' => [
                        'name' => $name
                    ],
                ]
            );

            $decodedResponse = $response->json();

            if( $response->status() !== 200 ) {
                throw new PrinciplesApiException("API response: {$response->status()} {$decodedResponse['message']}");
            }

            return $decodedResponse['tenant']['tenantId'];
        }
        catch (\Exception $exception) {
            throw new PrinciplesApiException("Failed to create the tenant in the Principles API: {$exception->getMessage()}");
        }
    }
}
