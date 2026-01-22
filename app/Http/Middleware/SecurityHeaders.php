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
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        // X-Content-Type-Options: Prevent MIME type sniffing
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        // X-Frame-Options: Prevent clickjacking
        // Allow PDF files to be embedded in iframe from frontend
        $isPdfRequest = str_contains($request->path(), '/uploads/files/dokumen') || 
                       str_ends_with($request->path(), '.pdf');
        
        if ($isPdfRequest) {
            // Allow PDF to be embedded from specific origins
            $allowedOrigins = [
                'http://localhost:3000',
                'https://skpv2.untirta.ac.id',
            ];
            $origin = $request->header('Origin');
            
            if (in_array($origin, $allowedOrigins)) {
                // Remove X-Frame-Options to allow embedding
                $response->headers->remove('X-Frame-Options');
                // Set CORS headers for PDF
                $response->headers->set('Access-Control-Allow-Origin', $origin);
                $response->headers->set('Access-Control-Allow-Credentials', 'true');
            } else {
                // Default: same origin only
                $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
            }
        } else {
            // For non-PDF requests, use SAMEORIGIN
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
