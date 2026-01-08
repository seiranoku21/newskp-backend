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
        // Skip JWT check for auth routes
        if ($request->is('api/auth/*')) {
            return $next($request);
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

