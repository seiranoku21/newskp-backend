<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use DB;

class PegawaiController extends Controller
{
    /**
     * Get pegawai data for autocomplete
     * Params: nip, email, search
     * Returns: id_pegawai, id_user, nip, nama, nama_gelar
     */
    public function data_pegawai(Request $request)
    {
        try {
            $query = DB::table('pegawai')
                ->select('id_pegawai', 'id_user', 'nip', 'nama', 'nama_gelar');

            // Filter by NIP if provided
            if ($request->has('nip') && !empty($request->nip)) {
                $query->where('nip', $request->nip);
            }

            // Filter by email if provided
            if ($request->has('email') && !empty($request->email)) {
                $query->where('email', $request->email);
            }

            // Search by nama_gelar or nip if search param provided
            if ($request->has('search') && !empty($request->search)) {
                $searchTerm = $request->search;
                $query->where(function($q) use ($searchTerm) {
                    $q->where('nama_gelar', 'LIKE', "%{$searchTerm}%")
                      ->orWhere('nip', 'LIKE', "%{$searchTerm}%")
                      ->orWhere('nama', 'LIKE', "%{$searchTerm}%");
                });
            }

            // Order by nama_gelar
            $query->orderBy('nama_gelar', 'asc');

            // Limit results to 20 for autocomplete
            $query->limit(20);

            $data = $query->get();

            return response()->json($data, 200);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to fetch pegawai data',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get single pegawai by NIP
     */
    public function get_pegawai_by_nip($nip)
    {
        try {
            $pegawai = DB::table('pegawai')
                ->select('id_pegawai', 'id_user', 'nip', 'nama', 'nama_gelar')
                ->where('nip', $nip)
                ->first();

            if (!$pegawai) {
                return response()->json([
                    'error' => 'Pegawai not found'
                ], 404);
            }

            return response()->json($pegawai, 200);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to fetch pegawai data',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
