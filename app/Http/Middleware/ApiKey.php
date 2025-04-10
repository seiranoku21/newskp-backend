<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApiKey
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // $apiKey = $request->header('api-key');
        
        // if ($apiKey !== 'NSKP2025#') {
        //     return response()->json([
        //         'status' => false,
        //         'message' => 'Invalid API Key'
        //     ], 401);
        // }

        return $next($request);
    }
} 