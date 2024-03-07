<?php

return [
    'authUrl' => env('PRINCIPLES_AUTH_URL', 'https://principles-stg-primary.auth.us-east-1.amazoncognito.com/oauth2/token'),
    'baseUrl' => env('PRINCIPLES_BASE_URL', 'https://app.stg40.principles.com'),
    'clientId' => env('PRINCIPLES_CLIENT_ID', 'client-id'),
    'clientSecret' => env('PRINCIPLES_CLIENT_SECRET', 'client-secret'),
    'tenantId' => env('PRINCIPLES_TENANT_ID', 'tenant-id'),
];
