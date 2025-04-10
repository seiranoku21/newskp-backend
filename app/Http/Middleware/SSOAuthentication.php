<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class SSOAuthentication
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public $excludePages = ["/", "home", "account"];
    
    // Define role abilities similar to frontend
    private $roleAbilities = [
        'admin' => [],
        'tendik' => [],
        'dosen' => [],
        'dosen_dt' => [],
        'unker' => [],
        'prodi' => [],
        'incognito' => []
    ];

    public function handle(Request $request, Closure $next)
    {
        // Skip SSO check for login/logout routes
        if ($request->is('api/auth/*')) {
            return $next($request);
        }

        // Get tokens from various sources
        $ssoToken = $request->cookie('access_token');
        $simpegToken = $request->cookie('simpeg_token');
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
    }

    private function handleSimpegAuth(Request $request, $token, Closure $next)
    {
        // Decode SIMPEG token
        $decodedToken = base64_decode(urldecode($token));
        $userData = json_decode($decodedToken, true);

        if (!$userData) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid SIMPEG token'
            ], 401);
        }

        // Get role from SIMPEG level_pegawai
        $splRoleId = $userData['level_pegawai'];
        
        // Map SIMPEG role to local role
        $localRole = $this->mapSimpegRoleToLocal($splRoleId);

        // Add user info and role to request
        $request->merge([
            'simpeg_user' => $userData,
            'user_role' => $localRole,
            'user_id' => $userData['nip'],
            'is_simpeg' => true
        ]);

        // Check authorization
        return $this->checkAuthorization($request, $localRole, $next);
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
        
        // Get SSO group and map to local role
        $ssoGroup = $userData['groups'][0]['grup_id'];
        $localRole = $this->mapSSOGroupToRole($ssoGroup);
        
        // Add user info and role to request
        $request->merge([
            'sso_user' => $userData,
            'user_role' => $localRole,
            'user_id' => $userData['employee']['pegawai_nip'],
            'is_sso' => true
        ]);

        // Check authorization
        return $this->checkAuthorization($request, $localRole, $next);
    }

    private function checkAuthorization(Request $request, $userRole, Closure $next)
    {
        // Check if the current page is in the exclude list
        $currentPage = $request->segment(2, "home");
        if (in_array($currentPage, $this->excludePages)) {
            return $next($request);
        }

        // Get allowed routes dynamically
        $allowedRoutes = $this->getAllowedRoutes($userRole);
        
        // Get current route path
        $route = $request->path();
        
        // Check if route is allowed
        $isAllowed = false;
        foreach ($allowedRoutes as $allowedRoute) {
            // Convert route pattern to regex
            $pattern = str_replace('/*', '(/.*)?', $allowedRoute);
            $pattern = '#^' . $pattern . '$#';
            
            if (preg_match($pattern, $route)) {
                $isAllowed = true;
                break;
            }
        }

        if (!$isAllowed) {
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

    private function getAllowedRoutes($role)
    {
        try {
            $token = request()->cookie('simpeg_token');
            $ssoToken = request()->cookie('access_token');
            
            if ($token) {
                // Get routes from SIMPEG user data
                $response = Http::get(config('app.url') . '/api/account/currentuserdata_spl', [
                    'user_role' => $role
                ]);
            } else if ($ssoToken) {
                // Get routes from SSO user data
                $response = Http::get(config('app.url') . '/api/account/currentuserdata_sso', [
                    'user_role' => $role
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
            \Log::error('Error getting allowed routes: ' . $e->getMessage());
            return [];
        }
    }
}
