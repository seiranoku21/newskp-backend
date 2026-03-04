<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Middleware untuk memvalidasi akses endpoint berdasarkan roles_menu user
 * Memastikan user hanya bisa mengakses endpoint yang sesuai dengan menu yang diberikan
 */
class RolesMenuGuard
{
    /**
     * Daftar endpoint yang dikecualikan dari validasi roles_menu
     * Endpoint ini dapat diakses oleh semua user yang sudah terautentikasi
     */
    protected $excludedEndpoints = [
        'account/currentuserdata',
        'account/change-password',
        'home',
        'get_photo',
        'dashboard',
    ];

    /**
     * Mapping dari endpoint API ke menu_url yang sesuai
     * Format: 'api_endpoint' => 'menu_url'
     */
    protected $endpointToMenuMapping = [
        'stat_verifikasi' => '/statistik',
        'vrf_listing' => '/verifikasi',
        'vrf_detail' => '/verifikasi',
        'lembar_skp' => '/ajuan',
        'lembar_skp_html' => '/ajuan',
        'lembar_skp_pdf' => '/ajuan',
        // Tambahkan mapping lainnya sesuai kebutuhan
    ];

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // Dapatkan user yang sedang login
        $user = $request->user();
        
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized - User not authenticated'
            ], 401);
        }

        // Dapatkan path endpoint yang diakses (tanpa prefix /api)
        $path = $request->path();
        $endpoint = str_replace('api/', '', $path);

        // Skip validasi untuk endpoint yang dikecualikan
        if ($this->isExcludedEndpoint($endpoint)) {
            return $next($request);
        }

        // Dapatkan roles_menu user
        $rolesMenu = $this->getUserRolesMenu($user->id);

        if (empty($rolesMenu)) {
            Log::warning("User {$user->id} tidak memiliki roles_menu, akses ke {$endpoint} ditolak");
            return response()->json([
                'success' => false,
                'message' => 'Forbidden - Anda tidak memiliki akses ke fitur ini'
            ], 403);
        }

        // Validasi akses berdasarkan mapping endpoint ke menu_url
        if (!$this->hasAccessToEndpoint($endpoint, $rolesMenu)) {
            Log::warning("User {$user->id} mencoba akses {$endpoint} tanpa permission di roles_menu");
            return response()->json([
                'success' => false,
                'message' => 'Forbidden - Anda tidak memiliki akses ke fitur ini. Silakan hubungi administrator.'
            ], 403);
        }

        // Akses diizinkan
        return $next($request);
    }

    /**
     * Cek apakah endpoint dikecualikan dari validasi
     */
    protected function isExcludedEndpoint($endpoint)
    {
        foreach ($this->excludedEndpoints as $excluded) {
            if (str_starts_with($endpoint, $excluded)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Dapatkan roles_menu user dari database
     */
    protected function getUserRolesMenu($userId)
    {
        try {
            // Dapatkan user dengan role
            $user = DB::table('users')
                ->where('id', $userId)
                ->first();

            if (!$user || !$user->user_role_id) {
                return [];
            }

            // Dapatkan roles_menu berdasarkan role_id
            $rolesMenu = DB::table('roles_menu')
                ->where('role_id', $user->user_role_id)
                ->get();

            return $rolesMenu->toArray();
        } catch (\Exception $e) {
            Log::error("Error getting roles_menu for user {$userId}: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Validasi apakah user memiliki akses ke endpoint
     */
    protected function hasAccessToEndpoint($endpoint, $rolesMenu)
    {
        // Cek apakah ada mapping untuk endpoint ini
        if (!isset($this->endpointToMenuMapping[$endpoint])) {
            // Jika tidak ada mapping, izinkan akses (untuk backward compatibility)
            // Atau bisa diubah menjadi return false untuk strict mode
            Log::info("Endpoint {$endpoint} tidak ada di mapping, akses diizinkan (backward compatibility)");
            return true;
        }

        $requiredMenuUrl = $this->endpointToMenuMapping[$endpoint];

        // Cek apakah menu_url ada di roles_menu user
        foreach ($rolesMenu as $menu) {
            if ($menu->menu_url === $requiredMenuUrl) {
                return true;
            }
        }

        return false;
    }
}
