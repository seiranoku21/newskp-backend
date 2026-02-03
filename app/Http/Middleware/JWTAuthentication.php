<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Helpers\JWTHelper;
use App\Models\Users;
use Exception;

class JWTAuthentication
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
        // Skip JWT check for public routes (login, logout, sso_untirta_login, dll.)
        $publicRoutes = [
            'api/auth/login',
            'api/auth/logout',
            'api/auth/sso_untirta_login',
            'api/auth/*',
            'api/login',
            'api/setting',
            'api/components_data/*',
            'api/simpeg_*',
            'api/account/currentuserdata_sso',
            'api/account/currentuserdata_spl',
            'api/sso_role',
            'api/spl_role',
            'api/get_pegawai',
            'api/ref_*',
            'api/lap_*',
            'api/portofolio_html',
            'api/fileuploader/*',
            'api/skp_tipe_deskripsi/*',
            'api/home',
        ];

        foreach ($publicRoutes as $pattern) {
            if ($request->is($pattern)) {
                return $next($request);
            }
        }

        // Get JWT token from cookie or Authorization header
        $token = $request->cookie('access_token');
        
        if (!$token && $request->hasHeader('Authorization')) {
            $token = str_replace('Bearer ', '', $request->header('Authorization'));
        }

        if (!$token) {
            return response()->json([
                'status' => 'error',
                'message' => 'No authentication token found'
            ], 401);
        }

        try {
            // Decode JWT token
            $userId = JWTHelper::decode($token);

            // Get user from database
            $user = Users::find($userId);

            if (!$user) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'User not found'
                ], 401);
            }

            // Set user in request for use in controllers
            $request->merge([
                'auth_user' => $user,
                'user_id' => $user->user_id
            ]);

            // Set authenticated user for Laravel Auth
            auth()->setUser($user);

            return $next($request);

        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid or expired token: ' . $e->getMessage()
            ], 401);
        }
    }
}

