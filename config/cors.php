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

    'paths' => [
        'api/*', 
        'sanctum/csrf-cookie',
        'uploads/files/*',  // Allow CORS for PDF and file uploads
    ],

    'allowed_methods' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],

    // ⚠️ SECURITY: Hanya izinkan domain yang spesifik
    // Jangan gunakan '*' karena tidak kompatibel dengan credentials
    'allowed_origins' => array_filter([
        env('FRONTEND_URL'),                    // URL frontend dari .env
        'https://skpv2.untirta.ac.id',         // Production frontend
        env('APP_ENV') === 'local' ? 'http://localhost:3000' : null,  // Dev only
        env('APP_ENV') === 'local' ? 'http://127.0.0.1:3000' : null,  // Dev only
    ]),

    'allowed_origins_patterns' => [],

    // Hanya izinkan headers yang diperlukan (lebih secure daripada '*')
    'allowed_headers' => [
        'Content-Type',
        'Authorization',
        'X-Requested-With',
        'Accept',
        'Origin',
        'X-CSRF-TOKEN',
    ],

    // Expose headers yang diperlukan untuk frontend
    'exposed_headers' => [
        'Authorization',
        'X-Total-Count',
    ],

    // Cache preflight request selama 1 jam (3600 detik)
    // Mengurangi jumlah OPTIONS request
    'max_age' => 3600,

    // ⚠️ CRITICAL: HARUS TRUE untuk cookies (httpOnly cookies)
    // Tanpa ini, cookie access_token tidak akan dikirim/diterima
    'supports_credentials' => true,

];
