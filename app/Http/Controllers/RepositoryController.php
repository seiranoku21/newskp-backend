<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use DB;

class RepositoryController extends Controller
{
    //
    function repository(Request $request){
        $email = $request->email;
        $rows = DB::table('aktifitas_kinerja')
                ->select('id', 'tahun', 'nip', 'email', 'gambar', 'dokumen', 'tautan')
                ->where('email', $email)
                ->get();

        // Kelompokkan per tahun, nip, email; repository = list item (id, gambar, dokumen, tautan)
        $grouped = $rows->groupBy(function ($item) {
            return $item->tahun . '|' . $item->nip . '|' . $item->email;
        });

        $data = $grouped->map(function ($items) {
            $first = $items->first();
            $repository = $items->map(function ($row) {
                return [
                    'id' => $row->id,
                    'gambar' => $row->gambar,
                    'dokumen' => $row->dokumen,
                    'tautan' => $row->tautan,
                ];
            })->values()->all();
            return [
                'tahun' => $first->tahun,
                'nip' => $first->nip,
                'email' => $first->email,
                'repository' => $repository,
            ];
        })->values()->all();

        return response()->json([
            'success' => true,
            'message' => 'Data repository berhasil diambil.',
            'data' => $data,
        ]);
    }
}
