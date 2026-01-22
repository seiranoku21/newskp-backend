<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * Secure CORS Middleware
 * 
 * Middleware ini menambahkan layer keamanan ekstra untuk CORS:
 * 1. Validasi origin yang diizinkan
 * 2. Set headers CORS dengan secure
 * 3. Handle preflight OPTIONS request
 * 4. Support credentials (cookies)
 */
class SecureCors
{
    /**
     * Daftar origins yang diizinkan
     * 
     * @return array
     */
    protected function getAllowedOrigins(): array
    {
        $origins = [
            'https://skpv2.untirta.ac.id', // Production frontend
        ];

        // Tambahkan frontend URL dari .env jika ada
        if ($frontendUrl = env('FRONTEND_URL')) {
            $origins[] = $frontendUrl;
        }

        // Development: izinkan localhost
        if (env('APP_ENV') === 'local' || env('APP_ENV') === 'development') {
            $origins[] = 'http://localhost:3000';
            $origins[] = 'http://127.0.0.1:3000';
            $origins[] = 'http://localhost:3001';
        }

        return array_unique($origins);
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $origin = $request->header('Origin');
        $allowedOrigins = $this->getAllowedOrigins();

        // Cek apakah origin diizinkan
        $isAllowed = in_array($origin, $allowedOrigins);

        // Handle preflight OPTIONS request
        if ($request->isMethod('OPTIONS')) {
            return response('', 200)
                ->header('Access-Control-Allow-Origin', $isAllowed ? $origin : '')
                ->header('Access-Control-Allow-Methods', 'GET, POST, PUT, PATCH, DELETE, OPTIONS')
                ->header('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With, Accept, Origin, X-CSRF-TOKEN')
                ->header('Access-Control-Allow-Credentials', 'true')
                ->header('Access-Control-Max-Age', '3600');
        }

        // Process request
        $response = $next($request);

        // Add CORS headers to response (hanya jika origin diizinkan)
        if ($isAllowed) {
            $response->headers->set('Access-Control-Allow-Origin', $origin);
            $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, PATCH, DELETE, OPTIONS');
            $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With, Accept, Origin, X-CSRF-TOKEN');
            $response->headers->set('Access-Control-Allow-Credentials', 'true');
            $response->headers->set('Access-Control-Expose-Headers', 'Authorization, X-Total-Count');
        }

        return $response;
    }
}
