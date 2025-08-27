<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Http;

class AuthSsoSpl
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Skip auth check for login/logout routes
        if ($request->is('api/auth/*')) {
            return $next($request);
        }

        // Cek hanya simpeg_token di cookie
        $simpegToken = $request->cookie('simpeg_token');
        if (!$simpegToken) {
            return response()->json([
                'status' => 'error',
                'message' => 'No simpeg_token found in cookie'
            ], 401);
        }

        try {
            // Decode base64 simpeg_token
            $decoded = base64_decode($simpegToken, true);
            if ($decoded === false) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Invalid base64 simpeg_token'
                ], 401);
            }

            $userData = json_decode($decoded, true);
            if (!is_array($userData) || !isset($userData['level_pegawai'])) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Invalid simpeg_token data'
                ], 401);
            }

            $levelPegawai = $userData['level_pegawai'];

            // Panggil API spl_role
            // Ganti URL berikut sesuai endpoint API spl_role yang benar
            $splRoleApiUrl = env('SPL_ROLE_API_URL', 'https://example.com/api/spl_role');
            $response = Http::get($splRoleApiUrl, [
                'level_pegawai' => $levelPegawai
            ]);

            if (!$response->ok()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed to fetch SPL role'
                ], 500);
            }

            $splRoleData = $response->json();

            // Merge user info ke request
            $request->merge([
                'simpeg_user' => $userData,
                'spl_role' => $splRoleData,
                'user_id' => $userData['nip'] ?? null,
                'is_simpeg' => true
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Authentication failed: ' . $e->getMessage()
            ], 500);
        }

        return $next($request);
    }
}
