<?php

$originsEnv = trim((string) env('CORS_ALLOWED_ORIGINS', ''));

// Defaults for local dev + Vercel pattern (see allowed_origins_patterns below)
$allowedOrigins = [
    'http://localhost:3000',
    'http://127.0.0.1:3000',
    'http://localhost:3001',
    'http://127.0.0.1:3001',
];

// Allow overriding allowed origins via env:
// - "*" to allow any origin (credentials will be disabled below)
// - or a comma-separated list of origins: "https://a.vercel.app,https://b.com"
if ($originsEnv !== '') {
    if ($originsEnv === '*') {
        $allowedOrigins = ['*'];
    } else {
        $allowedOrigins = array_values(array_filter(array_map('trim', explode(',', $originsEnv))));
    }
}

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

    'paths' => ['api/*', 'sanctum/csrf-cookie', 'storage/*'],

    'allowed_methods' => ['*'],

    'allowed_origins' => $allowedOrigins,

    'allowed_origins_patterns' => [
        '/^http:\/\/localhost:\d+$/',
        '/^http:\/\/127\.0\.0\.1:\d+$/',
        // รองรับ Vercel preview URLs
        '/^https:\/\/.*\.vercel\.app$/',
    ],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    // If allowing any origin ("*"), credentials must be disabled per CORS spec.
    'supports_credentials' => $allowedOrigins !== ['*'],

];
