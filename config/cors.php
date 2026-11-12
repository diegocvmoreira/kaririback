<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration - Kariri.app
    |--------------------------------------------------------------------------
    */

    'paths' => [
        'api/*',
        'sanctum/csrf-cookie',
    ],

    'allowed_methods' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],

    'allowed_origins' => [
        'https://ais-dev-4ayuyalyppe566cqnqe7u7-631265259379.us-east1.run.app',
        'https://kariri.app.br',
        'https://www.kariri.app.br',
        'https://ais-pre-4ayuyalyppe566cqnqe7u7-631265259379.us-east1.run.app',
        'http://localhost:3000',
        'http://localhost:5173',
    ],

    'allowed_origins_patterns' => [
        'ais-[a-z0-9\-\.]+\.run\.app',
        '.*\.kariri\.app\.br',
    ],

    'allowed_headers' => [
        'Content-Type',
        'X-Requested-With',
        'Authorization',
        'Accept',
        'Origin',
        'X-XSRF-TOKEN',
        'Cache-Control',
        'Pragma',
    ],

    'exposed_headers' => [
        'X-RateLimit-Limit',
        'X-RateLimit-Remaining',
        'X-Total-Count',
    ],

    'max_age' => 86400,

    'supports_credentials' => true,

];