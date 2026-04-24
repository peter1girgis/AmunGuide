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

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_origins' => [
        'http://localhost:8080',
        'https://amun-guide-application.up.railway.app'
    ],

    'allowed_methods' => ['*'], // بيسمح بكل العمليات GET, POST...

    'allowed_headers' => ['*'], // بيسمح بكل الـ Headers

    'supports_credentials' => true,

    'allowed_origins_patterns' => [],


    'exposed_headers' => [],

    'max_age' => 0,


];
