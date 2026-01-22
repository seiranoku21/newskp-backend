<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * Security Headers Middleware
 * 
 * Menambahkan security headers untuk melindungi aplikasi dari:
 * - XSS (Cross-Site Scripting)
 * - Clickjacking
 * - MIME type sniffing
 * - Information disclosure
 */
class SecurityHeaders
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    /**
     * Get allowed origins for CORS
     */
    protected function getAllowedOrigins(): array
    {
        $origins = [
            'https://skpv2.untirta.ac.id',
        ];

        // Development: allow localhost
        if (env('APP_ENV') === 'local' || env('APP_ENV') === 'development') {
            $origins[] = 'http://localhost:3000';
            $origins[] = 'http://127.0.0.1:3000';
            $origins[] = 'http://localhost:3001';
        }

        // Add frontend URL from .env if exists
        if ($frontendUrl = env('FRONTEND_URL')) {
            $origins[] = $frontendUrl;
        }

        return array_unique($origins);
    }

    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);
        $origin = $request->header('Origin');
        $allowedOrigins = $this->getAllowedOrigins();
        $isAllowedOrigin = in_array($origin, $allowedOrigins);

        // X-Content-Type-Options: Prevent MIME type sniffing
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        // Detect if this is a PDF or file request
        $isPdfRequest = str_contains($request->path(), '/uploads/files/dokumen') || 
                       str_contains($request->path(), '/uploads/files/') ||
                       str_ends_with($request->path(), '.pdf');
        
        // X-Frame-Options: Prevent clickjacking
        if ($isPdfRequest && $isAllowedOrigin) {
            // Remove X-Frame-Options to allow PDF embedding from allowed origins
            $response->headers->remove('X-Frame-Options');
        } else {
            // For non-PDF or non-allowed origins, use SAMEORIGIN
            $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        }

        // X-XSS-Protection: Enable XSS filter (legacy browsers)
        $response->headers->set('X-XSS-Protection', '1; mode=block');

        // Referrer-Policy: Control referrer information
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        // Content-Security-Policy: Mitigate XSS and injection attacks
        // Note: Sesuaikan dengan kebutuhan aplikasi
        if (env('APP_ENV') === 'production') {
            $response->headers->set('Content-Security-Policy', "default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval'; style-src 'self' 'unsafe-inline';");
        }

        // Strict-Transport-Security: Force HTTPS (hanya di production)
        if ($request->secure() && env('APP_ENV') === 'production') {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        // Permissions-Policy: Control browser features
        $response->headers->set('Permissions-Policy', 'geolocation=(), microphone=(), camera=()');

        // Remove server information disclosure
        $response->headers->remove('X-Powered-By');
        $response->headers->remove('Server');

        return $response;
    }
}
