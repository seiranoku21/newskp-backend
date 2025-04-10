<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckToken
{
    public $excludePages = ["/", "home", "account", "simpeg_login", "sso_login"];

    public function handle(Request $request, Closure $next)
    {
        // Periksa apakah path saat ini ada di daftar excludePages
        if (in_array($request->path(), $this->excludePages)) {
            return $next($request);
        }

        // Periksa keberadaan salah satu dari cookie yang diperlukan
        // Menggunakan trim() untuk menghilangkan spasi yang mungkin ada
        $simpegToken = $request->cookie('simpeg_token');
        $accessToken = $request->cookie('access_token');

        if (!$simpegToken && !$accessToken) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized access. Token not found.'
            ], 401);
        }

        return $next($request);
    }
} 