<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * File Controller
 * 
 * Handles file serving with proper CORS headers for PDF preview
 */
class FileController extends Controller
{
    /**
     * Get allowed origins for CORS
     */
    protected function getAllowedOrigins(): array
    {
        $origins = [
            'https://skpv2.untirta.ac.id',
        ];

        // Development: allow localhost
        if (env('APP_ENV') === 'local' || env('APP_ENV') === 'development') {
            $origins[] = 'http://localhost:3000';
            $origins[] = 'http://127.0.0.1:3000';
            $origins[] = 'http://localhost:3001';
        }

        // Add frontend URL from .env if exists
        if ($frontendUrl = env('FRONTEND_URL')) {
            $origins[] = $frontendUrl;
        }

        return array_unique($origins);
    }

    /**
     * Serve file with CORS headers
     * 
     * @param Request $request
     * @param string $path
     * @return BinaryFileResponse
     */
    public function serve(Request $request, $path)
    {
        // Construct full file path
        $filePath = public_path('uploads/files/' . $path);

        // Check if file exists
        if (!file_exists($filePath)) {
            abort(404, 'File tidak ditemukan');
        }

        // Get origin from request
        $origin = $request->header('Origin');
        $allowedOrigins = $this->getAllowedOrigins();
        $isAllowedOrigin = in_array($origin, $allowedOrigins);

        // Create response
        $response = response()->file($filePath);

        // Add CORS headers if origin is allowed
        if ($isAllowedOrigin) {
            $response->headers->set('Access-Control-Allow-Origin', $origin);
            $response->headers->set('Access-Control-Allow-Methods', 'GET, OPTIONS');
            $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With, Accept, Origin');
            $response->headers->set('Access-Control-Allow-Credentials', 'true');
        }

        // Remove X-Frame-Options to allow embedding in iframe
        $response->headers->remove('X-Frame-Options');

        // Set proper content type
        $mimeType = mime_content_type($filePath);
        $response->headers->set('Content-Type', $mimeType);

        // Add cache headers for better performance
        $response->headers->set('Cache-Control', 'public, max-age=3600');

        return $response;
    }

    /**
     * Handle preflight OPTIONS request
     * 
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function options(Request $request)
    {
        $origin = $request->header('Origin');
        $allowedOrigins = $this->getAllowedOrigins();
        $isAllowedOrigin = in_array($origin, $allowedOrigins);

        if ($isAllowedOrigin) {
            return response('', 200)
                ->header('Access-Control-Allow-Origin', $origin)
                ->header('Access-Control-Allow-Methods', 'GET, OPTIONS')
                ->header('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With, Accept, Origin')
                ->header('Access-Control-Allow-Credentials', 'true')
                ->header('Access-Control-Max-Age', '3600');
        }

        return response('', 403);
    }
}
