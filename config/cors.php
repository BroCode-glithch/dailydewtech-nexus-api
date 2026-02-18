<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your settings for cross-origin resource sharing
    | or "CORS". This determines what cross-origin operations may execute
    | in web browsers. You are free to adjust these settings as needed.
    |
    | To learn more: https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS
    |
    */

    // Limit CORS to the API endpoints and the Sanctum csrf endpoint only.
    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    // Allow only the frontend origin (set FRONTEND_URL in .env)
    'allowed_methods' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],

    'allowed_origins' => [env('FRONTEND_URL', 'http://localhost:8080')],

    'allowed_origins_patterns' => [],

    // Restrict headers; allow common headers and auth header
    'allowed_headers' => ['Content-Type', 'X-Requested-With', 'Authorization', 'Accept'],

    'exposed_headers' => ['Authorization'],

    'max_age' => 0,

    // For stateless token APIs, do not send cookies/credentials from browser
    'supports_credentials' => false,

];
