<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\Users;
use App\Helpers\JWTHelper;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AuthSsoSpl
{
    public $excludePages = ["/", "home", "account"];

    private $roleAbilities = [
        'admin' => [],
        'tendik' => [],
        'dosen' => [],
        'dosen_dt' => [],
        'unker' => [],
        'prodi' => [],
        'incognito' => []
    ];

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Skip SSO check for login/logout routes
        if ($request->is('api/auth/*')) {
            return $next($request);
        }

        // Get tokens from various sources
        $ssoToken = $request->cookie('access_token') ?? $request->header('access_token');
        $simpegToken = $request->cookie('simpeg_token') ?? $request->header('simpeg_token');
        $bearerToken = null;

        // Check Bearer token from Authorization header
        if ($request->hasHeader('Authorization')) {
            $bearerToken = str_replace('Bearer ', '', $request->header('Authorization'));
        }

        // If no tokens found, return unauthorized
        if (!$ssoToken && !$simpegToken && !$bearerToken) {
            return response()->json([
                'status' => 'error',
                'message' => 'No authentication token found'
            ], 401);
        }

        try {
            // Handle SIMPEG token authentication
            if ($simpegToken) {
                return $this->handleSimpegAuth($request, $simpegToken, $next);
            }
            // Handle SSO token authentication
            else if ($ssoToken || $bearerToken) {
                return $this->handleSSOAuth($request, $ssoToken ?? $bearerToken, $next);
            }

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error', 
                'message' => 'Authentication failed: ' . $e->getMessage()
            ], 500);
        }

        return $next($request);
    }

    private function handleSimpegAuth(Request $request, $token, Closure $next)
    {
        $userId = JWTHelper::decode($token);
        $simpegUser = Users::find($userId);

        if (!$simpegUser) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid SIMPEG token or user not found'
            ], 401);
        }

        // Assuming getSsoRoles can interpret the simpegToken or userData if needed
        $simpegRoles = $simpegUser->getSsoRoles($token); 
        $simpegUser->setRoles($simpegRoles); 
        
        // Add user info and role to request
        $request->merge([
            'simpeg_user' => $simpegUser->toArray(), // Convert user object to array for merging
            'user_role' => $simpegUser->getRoleNames(),
            'user_id' => $simpegUser->UserId(),
            'is_simpeg' => true
        ]);

        // Check authorization using the canAccess method
        return $this->checkAuthorization($request, $simpegUser, $next);
    }

    private function handleSSOAuth(Request $request, $token, Closure $next)
    {
        // Verify token with SSO server
        $response = Http::withToken($token)
            ->post('https://sso.untirta.ac.id/api/v1/userinfo');

        if (!$response->successful()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid SSO token'
            ], 401);
        }

        $userData = $response->json();

        $userId = JWTHelper::decode($token);
        $ssoUser = Users::find($userId);

        if (!$ssoUser) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid SSO token or user not found'
            ], 401);
        }

        $ssoRoles = $ssoUser->getSsoRoles($token); 
        $ssoUser->setRoles($ssoRoles); 

        // Add user info and role to request
        $request->merge([
            'sso_user' => $ssoUser->toArray(), // Convert user object to array for merging
            'user_role' => $ssoUser->getRoleNames(),
            'user_id' => $ssoUser->UserId(),
            'is_sso' => true
        ]);

        // Check authorization using the canAccess method
        return $this->checkAuthorization($request, $ssoUser, $next);
    }

    private function checkAuthorization(Request $request, $user, Closure $next)
    {
        $page = $request->segment(2, "home");
        $action = $request->segment(3, "index");
        $page_action = strtolower("$page/$action");

        // Check if the current page is in the exclude list
        if (in_array($page, $this->excludePages)) {
            return $next($request);
        }

        // Use the canAccess method of the user object
        if (!$user->canAccess($page_action)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized access'
            ], 403);
        }

        return $next($request);
    }

    private function mapSimpegRoleToLocal($splRoleId)
    {
        // Implement SIMPEG role mapping logic here
        $roleMapping = [
            // Example mapping:
            'pegawai' => 'tendik',
            'dosen' => 'dosen',
            "dosen_dt" => "dosen_dt",
            "unker" => "unker",
            "prodi" => "prodi",
            // Add more mappings as needed
        ];

        return $roleMapping[$splRoleId] ?? 'incognito';
    }

    private function mapSSOGroupToRole($ssoGroup)
    {
        // Implement role mapping logic here
        // This should match your frontend role mapping
        // You might want to store this in database or config
        $roleMapping = [
            // Example mapping:
            'sso_admin_group' => 'admin',
            'sso_dosen_group' => 'dosen',
            'sso_dosen_dt_group' => 'dosen_dt',
            'sso_tendik_group' => 'tendik',
            'sso_unker_group' => 'unker',
            'sso_prodi_group' => 'prodi',
            // Add more mappings as needed
        ];

        return $roleMapping[$ssoGroup] ?? 'incognito';
    }

    private function getAllowedRoutes($user)
    {
        try {
            $token = request()->cookie('simpeg_token');
            $ssoToken = request()->cookie('access_token');
            
            if ($token) {
                // Get routes from SIMPEG user data
                // Using $user->getRoleNames() to pass roles to the API endpoint
                $response = Http::get(config('app.url') . '/api/account/currentuserdata_spl', [
                    'user_role' => $user->getRoleNames()
                ]);
            } else if ($ssoToken) {
                // Get routes from SSO user data
                // Using $user->getRoleNames() to pass roles to the API endpoint
                $response = Http::get(config('app.url') . '/api/account/currentuserdata_sso', [
                    'user_role' => $user->getRoleNames()
                ]);
            } else {
                return [];
            }

            if ($response->successful()) {
                $userData = $response->json();
                // Assuming the API returns pages/routes in userData.pages
                return $userData['pages'] ?? [];
            }

            return [];
        } catch (\Exception $e) {
            Log::error('Error getting allowed routes: ' . $e->getMessage());
            return [];
        }
    }
}
