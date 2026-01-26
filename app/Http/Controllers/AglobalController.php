<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Http\Controllers\SimpegController;
use App\Http\Controllers\SkpKontrakController;
use App\Http\Controllers\AktifitasKinerjaController;
use DB;

class AglobalController extends Controller
{   
    // ---SETTING

    public function setting(Request $request){
        // Ambil data setting
        $data = \DB::table('setting')->first();

        if ($data) {
            // Ambil data periode aktif dari ref_periode
            $periode_aktif_tendik = null;
            if (isset($data->periode_aktif_tendik_id)) {
                $periode_aktif_tendik = \DB::table('ref_periode')
                    ->where('id', $data->periode_aktif_tendik_id)
                    ->first();
            }

            $periode_aktif_dosen = null;
            if (isset($data->periode_aktif_dosen_id)) {
                $periode_aktif_dosen = \DB::table('ref_periode')
                    ->where('id', $data->periode_aktif_dosen_id)
                    ->first();
            }

            // Gabungkan data setting dan periode aktif (jika ada)
            $result = [
                "success" => true,
                "data" => $data,
                "periode_aktif_tendik" => $periode_aktif_tendik,
                "periode_aktif_dosen" => $periode_aktif_dosen
            ];

            return response()->json($result);
        } else {
            return response()->json([
                "success" => false,
                "message" => "Data not found"
            ], 404);
        }
    }

    // ---PEGAWAI START---
    public function get_pegawai_by_nip(Request $request){
        $nip = $request->nip;
        $pegawai = SimpegController::pegawai($nip);
        return $pegawai;
    }  
    public function get_jabatan_by_nip(Request $request){
        $nip = $request->nip;
        $jabatan = SimpegController::jabatan($nip);
        return $jabatan;
    }

    public function get_riwayat_jabatan_by_nip(Request $request){
        $nip = $request->nip;
        $riwayat_jabatan = SimpegController::riwayat_jabatan($nip);
        return $riwayat_jabatan;
    }
    // ---PEGAWAI END---

    // ---PERIODE START---
    public function periode_rentang_bln(Request $request){
        $periode_id = $request->periode_id;
        $tahun = $request->tahun ?? date('Y'); // Use current year if not provided
        $data = DB::table('ref_periode')
                ->select('bln_mulai', 'bln_selesai','periode','rentang')
                ->where('id', $periode_id)->first();

        if (!$data) {
            return response()->json(["error" => "Period not found"], 404);
        }

        $periode_mulai = date('Y-m-d', strtotime($tahun . '-' . $data->bln_mulai . '-01'));
        $periode_selesai = date('Y-m-d', strtotime($tahun . '-' . $data->bln_selesai . '-' . date('t', strtotime($tahun . '-' . $data->bln_selesai . '-01'))));
        
        $result = [
            "periode" => $data->periode,
            "rentang" => $data->rentang,
            "tahun" => $tahun,
            "periode_mulai" => $periode_mulai,
            "periode_selesai" => $periode_selesai,
            "bln_mulai" => $data->bln_mulai,
            "bln_selesai" => $data->bln_selesai,
            "periode_id" => $periode_id
        ];

        return response()->json($result);
    }
    // ---PERIODE END---

    // ---PERILAKU KERJA START---
    public function get_ekspektasi_pimpinan(Request $request){
        $uid = $request->uid;
        $kode_pk = $request->kode_pk;
        $data = DB::table('perilaku_kerja')
                ->select('ekspektasi_pimpinan')
                ->where('uid', $uid)
                ->where('perilaku_kerja_kode', $kode_pk)
                ->first();
        return $data;
    }
    // ---PERILAKU KERJA END---

    // ---PORTFOLIO KINERJA START---

    public function get_portofolio(Request $request){
        $nip = $request->nip;
        $uid = $request->uid;

        // Ambil data portofolio_kinerja
        $query = DB::table('portofolio_kinerja')
            ->select(
                'id',
                'uid',
                'tahun',
                DB::raw("CONCAT('[ ', id, '-', SUBSTRING(uid, 1, 4), ' ] - ', jabatan) as no_poki"),
                DB::raw("CONCAT(id, '-', SUBSTRING(uid, 1, 4)) as no_portofolio"),
                'no_sk',
                'nip',
                'email',
                'nama',
                'jabatan_struktural',
                'jabatan_struktural_id',
                'jabatan_fungsional',
                'jabatan_fungsional_id',
                'unit_kerja',
                'unit_kerja_id',
                'homebase',
                'homebase_id',
                'pangkat',
                'pangkat_id',
                'status_kerja',
                'level_pegawai'
            );

        // Filter
        if (!empty($nip)) {
            $query->where('nip', $nip);
        }
        if (!empty($uid)) {
            $query->where('uid', $uid);
        }

        $portofolios = $query->get();

        // Untuk setiap portofolio, ambil detail_rubrik_kinerja dari rencana_hasil_kerja_atasan
        $result = [];
        foreach ($portofolios as $item) {
            $detail_rubrik_kinerja = DB::table('rencana_hasil_kerja_atasan')
                ->select('id','rubrik_kinerja', 'kategori')
                ->where('portofolio_kinerja_uid', $item->uid)
                ->get()
                ->map(function($row){
                    return [
                        'id' => $row->id,
                        'rubrik_kinerja' => $row->rubrik_kinerja,
                        'kategori' => $row->kategori,
                        'detail_kegiatan' => DB::table('rencana_hasil_kerja_item')
                            ->where('rhka_id', $row->id)
                            ->select('id AS rhki_id',
                                    'rhka_id',
                                    'kegiatan',
                                    'ukuran_keberhasilan',
                                    'realisasi',
                                    'aspek_kuantitas',
                                    'aspek_kualitas',
                                    'aspek_waktu'
                                )
                            ->get()
                    ];
                })
                ->toArray();

            $result[] = [
                "id" => $item->id,
                "uid" => $item->uid,
                "tahun" => $item->tahun,
                "no_poki" => $item->no_poki,
                "no_portofolio" => $item->no_portofolio,
                "no_sk" => $item->no_sk,
                "nip" => $item->nip,
                "email" => $item->email,
                "nama" => $item->nama,
                "jabatan_struktural" => $item->jabatan_struktural,
                "jabatan_struktural_id" => $item->jabatan_struktural_id,
                "jabatan_fungsional" => $item->jabatan_fungsional,
                "jabatan_fungsional_id" => $item->jabatan_fungsional_id,
                "unit_kerja" => $item->unit_kerja,
                "unit_kerja_id" => $item->unit_kerja_id,
                "homebase" => $item->homebase,
                "homebase_id" => $item->homebase_id,
                "pangkat" => $item->pangkat,
                "pangkat_id" => $item->pangkat_id,
                "status_kerja" => $item->status_kerja,
                "level_pegawai" => $item->level_pegawai,
                "detail_rubrik_kinerja" => $detail_rubrik_kinerja
            ];
        }

        return response()->json($result);
    }

    public function get_portofolio_by_nip(Request $request){
        $nip = $request->nip;
        $data = DB::table('portofolio_kinerja')
                ->select(DB::raw("CONCAT('[ ', id, '-', SUBSTRING(uid, 1, 4), ' ] - ', jabatan) as no_poki"),'id','uid','jabatan','unit_kerja')            
                ->where('nip', $nip)
                ->get()
                ->toArray();
        return $data;
    }

    public function get_portofolio_by_id(Request $request){
        $id = $request->id;
        $data = DB::table('portofolio_kinerja')
                ->select('uid')
                ->where('id', $id)
                ->first();
        return $data;
    }   

    public function cek_portofolio(Request $request){
        $nip = $request->nip;
        $tahun = $request->tahun;
        $unit_kerja_id = $request->unit_kerja_id;
        $jabatan_struktural_id = $request->jabatan_struktural_id;
        $jabatan_fungsional_id = $request->jabatan_fungsional_id;
        $homebase_id = $request->homebase_id;

        $exists = DB::table('portofolio_kinerja')
            ->where('nip', $nip)
            ->where('tahun', $tahun)
            ->where('unit_kerja_id', $unit_kerja_id)
            ->where('jabatan_struktural_id', $jabatan_struktural_id)
            ->where('jabatan_fungsional_id', $jabatan_fungsional_id)
            ->where('homebase_id', $homebase_id)
            ->whereNotNull('nip')
            ->whereNotNull('tahun')
            ->whereNotNull('unit_kerja_id')
            ->whereNotNull('jabatan_struktural_id')
            ->whereNotNull('jabatan_fungsional_id')
            ->whereNotNull('homebase_id')
            ->exists();

        if ($exists) {
            return response()->json([
                'success' => false,
                'message' => 'Portofolio sudah dibuat sebelumnya dengan Unit Kerja / Homebase dan Jabatan yang sama. Untuk membuat Portofolio baru, pastikan sudah ada perubahan data baik Unit Kerja / Homebase maupun jabatan yang baru. Hubungi Kepegawaian untuk perubahan data pegawai yang baru.',
                'status_cek' => 'Y'
            ], 200);
        } else {
            return response()->json([
                'success' => true,
                'message' => 'Portofolio belum ada, silakan lanjutkan pembuatan.',
                'status_cek' => 'N'
            ], 200);
        }
    }
    // ---PORTFOLIO KINERJA END---

    // ---RUBRIK KEGIATAN START

    public function rubrik_kegiatan_rhki(Request $request){
        $nip = $request->nip;
        $portofolio_uid = $request->portofolio_uid;

        $query = DB::table('rencana_hasil_kerja_item')
                ->select(
                    'id as rhki_id',
                    'kegiatan',
                    'nip',
                    'portofolio_kinerja_uid as portofolio_uid',
                    'rhka_id',
                    'ukuran_keberhasilan',
                    'realisasi',
                    'aspek_kualitas',
                    'aspek_kuantitas',
                    'aspek_waktu'
                );
        // Filter
        if (!empty($nip)) {
             $query->where('nip', $nip);
        }
        if (!empty($portofolio_uid)) {
            $query->where('portofolio_kinerja_uid', $portofolio_uid);
        }

        $data = $query->get()->toArray();
        return $data;
        
        
    }

    // ---RUBRIK KEGIATAN END

    // ---AKTIFITAS KINERJA---
    public function get_aktifitas(Request $request){
        try {
            $nip = $request->nip;
            $perPage = $request->input('per_page', 50); // default 50
            $page = $request->input('page', 1);

            if (empty($nip)) {
                return response()->json([
                    'success' => false,
                    'message' => 'NIP harus diisi',
                    'data' => [],
                    'jml_data' => 0,
                    'pagination' => [
                        'current_page' => (int)$page,
                        'per_page' => (int)$perPage,
                        'total' => 0,
                        'last_page' => 0,
                    ]
                ], 400);
            }

            $query = DB::table('aktifitas_kinerja as a');
            $query->leftJoin('rencana_hasil_kerja_item as b', 'a.rhki_id', '=', 'b.id');    
            $query->leftJoin('rencana_hasil_kerja_atasan as c', 'a.rhka_id', '=', 'c.id');
            $query->select(
                        'a.id',
                        'a.rhki_id',
                        'b.kegiatan',
                        'c.kategori',
                        'a.tanggal_mulai',
                        'a.tanggal_selesai',
                        'a.jumlah',
                        'a.satuan',
                        'a.gambar',
                        'a.dokumen',
                        'a.tautan',
                        'a.rating_hasil_kerja',
                        'a.poin',
                        'a.portofolio_kinerja_uid as portofolio_uid',
                        'a.rhka_id',
                        'b.ukuran_keberhasilan',
                        'b.realisasi'
            );
            $query->where('a.nip', $nip);
            
            if($request->has('tanggal_mulai') && $request->has('tanggal_selesai')){
                $tanggal_mulai = $request->tanggal_mulai;
                $tanggal_selesai = $request->tanggal_selesai;
                $query->whereBetween('tanggal_mulai', [$tanggal_mulai, $tanggal_selesai]);
            }

            $jml_data = $query->count();

            // Pagination
            $data = $query->forPage($page, $perPage)->get();

            return response()->json([
                'success' => true,
                'message' => 'sukses',
                'jml_data' => $jml_data,
                'data' => $data,
                'pagination' => [
                    'current_page' => (int)$page,
                    'per_page' => (int)$perPage,
                    'total' => $jml_data,
                    'last_page' => ceil($jml_data / $perPage),
                ]
            ]);
        } catch (\Exception $e) {
            \Log::error('Error get_aktifitas: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage(),
                'data' => [],
                'jml_data' => 0,
                'pagination' => [
                    'current_page' => (int)($request->input('page', 1)),
                    'per_page' => (int)($request->input('per_page', 10)),
                    'total' => 0,
                    'last_page' => 0,
                ]
            ], 500);
        }
    }

    public function post_aktifitas(Request $request){
        // Validasi data yang diperlukan
        $validated = $request->validate([
            'nip' => 'required|string',
            'rhki_id' => 'nullable|integer',
            'tanggal_mulai' => 'required|date',
            'tanggal_selesai' => 'required|date',
            'jumlah' => 'nullable|numeric',
            'satuan' => 'nullable|string',

        ]);

        // Insert ke tabel aktifitas_kinerja
        $insertedId = DB::table('aktifitas_kinerja')->insertGetId([
            'nip' => $validated['nip'],
            'rhki_id' => $validated['rhki_id'] ?? null,
            'tanggal_mulai' => $validated['tanggal_mulai'],
            'tanggal_selesai' => $validated['tanggal_selesai'],
            'satuan' => $validated['satuan'] ?? null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Ambil data yang baru saja diinsert
        $data = DB::table('aktifitas_kinerja')->where('id', $insertedId)->first();

        return response()->json([
            'success' => true,
            'message' => 'Aktifitas kinerja berhasil ditambahkan.',
            'data' => $data
        ]);
    }

    public function laporan_aktifitas(Request $request){
        $nip = $request->input('nip');
        $tahun = $request->input('tahun');

        $query = DB::table('aktifitas_kinerja');

        if (!empty($nip)) {
            $query->where('nip', $nip);
        }

        if (!empty($tahun)) {
            $query->whereYear('tanggal_mulai', $tahun);
        }

        $data = $query->get();

        // Buat rekap jumlah aktifitas per bulan (1-12)
        $rekap_perbulan = [];
        for ($bulan = 1; $bulan <= 12; $bulan++) {
            $rekap_perbulan[$bulan] = 0;
        }
        foreach ($data as $item) {
            // Ambil bulan dari tanggal_mulai
            $bulan = date('n', strtotime($item->tanggal_mulai));
            if (isset($rekap_perbulan[$bulan])) {
                $rekap_perbulan[$bulan]++;
            }
        }

        // Buat rekap jumlah poin per bulan (1-12)
        $rekap_poin_perbulan = [];
        for ($bulan = 1; $bulan <= 12; $bulan++) {
            $rekap_poin_perbulan[$bulan] = 0;
        }
        foreach ($data as $item) {
            $bulan = date('n', strtotime($item->tanggal_mulai));
            if (isset($rekap_poin_perbulan[$bulan])) {
                // Jika ada field 'poin', gunakan, jika tidak, asumsikan 1
                $poin = isset($item->poin) ? floatval($item->poin) : 1;
                $rekap_poin_perbulan[$bulan] += $poin;
            }
        }

        $rekap_bobot_poin_perbulan = [];
        for ($bulan = 1; $bulan <= 12; $bulan++) {
            if ($rekap_perbulan[$bulan] > 0) {
                $rekap_bobot_poin_perbulan[$bulan] = (($rekap_poin_perbulan[$bulan]) / ($rekap_perbulan[$bulan] * 2)) * 100;
            } else {
                $rekap_bobot_poin_perbulan[$bulan] = 0;
            }
        }

        // Buat rekap jumlah rating_hasil_kerja (AE, SE, BE) per bulan (1-12)
        $rekap_rating_hasil_kerja = [];
        $rating_types = ['AE', 'SE', 'BE'];
        // Inisialisasi array 12 bulan untuk setiap rating
        foreach ($rating_types as $rating) {
            for ($bulan = 1; $bulan <= 12; $bulan++) {
                $rekap_rating_hasil_kerja[$rating][$bulan] = 0;
            }
        }
        foreach ($data as $item) {
            $bulan = date('n', strtotime($item->tanggal_mulai));
            $rating = isset($item->rating_hasil_kerja) ? $item->rating_hasil_kerja : null;
            if (in_array($rating, $rating_types) && isset($rekap_rating_hasil_kerja[$rating][$bulan])) {
                $rekap_rating_hasil_kerja[$rating][$bulan]++;
            }
        }

        // Rekap jumlah gambar yang diupload per bulan (1-12)
        $rekap_bukti_gambar = [];
        for ($bulan = 1; $bulan <= 12; $bulan++) {
            $rekap_bukti_gambar[$bulan] = 0;
        }
        foreach ($data as $item) {
            $bulan = date('n', strtotime($item->tanggal_mulai));
            // Asumsi field 'bukti_gambar' adalah array atau string json/array gambar
            if (isset($item->gambar)) {
                $gambar = $item->gambar;
                // Jika string json, decode dulu
                if (is_string($gambar)) {
                    $decoded = json_decode($gambar, true);
                    if (is_array($decoded)) {
                        $jumlah_gambar = count($decoded);
                    } else {
                        // Jika bukan array, asumsikan 1 gambar jika tidak kosong
                        $jumlah_gambar = !empty($gambar) ? 1 : 0;
                    }
                } elseif (is_array($gambar)) {
                    $jumlah_gambar = count($gambar);
                } else {
                    $jumlah_gambar = 0;
                }
                if (isset($rekap_bukti_gambar[$bulan])) {
                    $rekap_bukti_gambar[$bulan] += $jumlah_gambar;
                }
            }
        }

        // Rekap jumlah bukti dokumen (field: 'dokumen') per bulan (1-12)
        $rekap_bukti_dokumen = [];
        for ($bulan = 1; $bulan <= 12; $bulan++) {
            $rekap_bukti_dokumen[$bulan] = 0;
        }
        foreach ($data as $item) {
            $bulan = date('n', strtotime($item->tanggal_mulai));
            // Asumsi field 'dokumen' adalah array atau string json/array dokumen
            if (isset($item->dokumen)) {
                $dokumen = $item->dokumen;
                // Jika string json, decode dulu
                if (is_string($dokumen)) {
                    $decoded = json_decode($dokumen, true);
                    if (is_array($decoded)) {
                        $jumlah_dokumen = count($decoded);
                    } else {
                        // Jika bukan array, asumsikan 1 dokumen jika tidak kosong
                        $jumlah_dokumen = !empty($dokumen) ? 1 : 0;
                    }
                } elseif (is_array($dokumen)) {
                    $jumlah_dokumen = count($dokumen);
                } else {
                    $jumlah_dokumen = 0;
                }
                if (isset($rekap_bukti_dokumen[$bulan])) {
                    $rekap_bukti_dokumen[$bulan] += $jumlah_dokumen;
                }
            }
        }

        // Rekap jumlah bukti tautan (field: 'tautan') per bulan (1-12)
        $rekap_bukti_tautan = [];
        for ($bulan = 1; $bulan <= 12; $bulan++) {
            $rekap_bukti_tautan[$bulan] = 0;
        }
        foreach ($data as $item) {
            $bulan = date('n', strtotime($item->tanggal_mulai));
            // Asumsi field 'tautan' adalah array atau string json/array tautan
            if (isset($item->tautan)) {
                $tautan = $item->tautan;
                // Jika string json, decode dulu
                if (is_string($tautan)) {
                    $decoded = json_decode($tautan, true);
                    if (is_array($decoded)) {
                        $jumlah_tautan = count($decoded);
                    } else {
                        // Jika bukan array, asumsikan 1 tautan jika tidak kosong
                        $jumlah_tautan = !empty($tautan) ? 1 : 0;
                    }
                } elseif (is_array($tautan)) {
                    $jumlah_tautan = count($tautan);
                } else {
                    $jumlah_tautan = 0;
                }
                if (isset($rekap_bukti_tautan[$bulan])) {
                    $rekap_bukti_tautan[$bulan] += $jumlah_tautan;
                }
            }
        }

        // Rekap keseluruhan selama 1 tahun (12 bulan)
        $rekap_aktifitas_keseluruhan = array_sum($rekap_perbulan);
        $rekap_poin_keseluruhan = array_sum($rekap_poin_perbulan);
        $rekap_bobot_poin_keseluruhan = $rekap_poin_keseluruhan / ($rekap_aktifitas_keseluruhan * 2) * 100 ;
        $rekap_rating_hasil_kerja_ae = 0;
        foreach ($data as $item) {
            if (isset($item->rating_hasil_kerja) && $item->rating_hasil_kerja === 'AE') {
                $rekap_rating_hasil_kerja_ae++;
            }
        }

        $rekap_rating_hasil_kerja_se = 0;
        foreach ($data as $item) {
            if (isset($item->rating_hasil_kerja) && $item->rating_hasil_kerja === 'SE') {
                $rekap_rating_hasil_kerja_se++;
            }
        }

        $rekap_rating_hasil_kerja_be = 0;
        foreach ($data as $item) {
            if (isset($item->rating_hasil_kerja) && $item->rating_hasil_kerja === 'BE') {
                $rekap_rating_hasil_kerja_be++;
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Data aktifitas kinerja berhasil diambil.',
            'data' => $data,
            'rekap_perbulan' => $rekap_perbulan,
            'rekap_poin_perbulan' => $rekap_poin_perbulan,
            'rekap_rating_hasil_kerja' => $rekap_rating_hasil_kerja,
            'rekap_bukti_gambar' => $rekap_bukti_gambar,
            'rekap_bukti_dokumen' =>  $rekap_bukti_dokumen,
            'rekap_bukti_tautan' => $rekap_bukti_tautan,
            'rekap_bobot_poin_perbulan' => $rekap_bobot_poin_perbulan,
            'rekap_aktifitas_keseluruhan' => $rekap_aktifitas_keseluruhan,
            'rekap_poin_keseluruhan' => $rekap_poin_keseluruhan,
            'rekap_bobot_poin_keseluruhan' => $rekap_bobot_poin_keseluruhan,
            'rekap_rating_hasil_kerja_ae' => $rekap_rating_hasil_kerja_ae,
            'rekap_rating_hasil_kerja_se' => $rekap_rating_hasil_kerja_se,
            'rekap_rating_hasil_kerja_be' => $rekap_rating_hasil_kerja_be
        
        ]);
    } 

    // ---AKTIFITAS KINERJA END---  


    // ---VERIFIKASI START---
    public function vrf_listing(Request $request){
        $nip_penilai = $request->nip_penilai;
        $tahun = $request->tahun;
        $skp_tipe_id = $request->skp_tipe_id;
        $periode_id = $request->periode_id;
        $status_id = $request->status_id;

        $data = DB::table('skp_kontrak as a')
                ->leftJoin('portofolio_kinerja as b', 'a.portofolio_id', '=', 'b.id')
                ->leftJoin('ref_skp_tipe as c', 'a.skp_tipe_id', '=', 'c.id')
                ->leftJoin('ref_periode as d', 'a.periode_id', '=', 'd.id')
                ->leftJoin('ref_status as e', 'a.status_id', '=', 'e.id')
                ->select('a.uid', 
                        'a.skp_tipe_id',
                        'a.periode_id',
                         'a.pegawai_nip', 
                         'a.pegawai_nama',
                         'a.pegawai_jabatan',
                         'a.portofolio_id',
                         'b.uid as portofolio_uid',
                         'c.skp_tipe as skp_tipe',
                         'd.periode as periode',
                         'd.rentang as rentang',
                         'd.jml_bln as jml_bln',
                         'e.status as status',
                         'e.id as status_id',
                         'a.rating_hasil_kerja as rating_hasil_kerja',
                         'a.rating_perilaku_kerja as rating_perilaku_kerja',
                         'a.predikat_kinerja as predikat_kinerja',
                         'a.poin as poin',
                         'a.bobot_persen as bobot_persen',
                         'a.penilai_nama as penilai_nama'
                         )
                ->where('a.penilai_nip', $nip_penilai)
                ->where('a.tahun', $tahun)
                ->where('a.skp_tipe_id', $skp_tipe_id)
                ->where('a.periode_id', $periode_id)
                ->orderBy('a.pegawai_nama', 'asc')
                ->get();

        $jml_data = $data->count();

        $response = [
            'tahun' => $tahun,
            'skp_tipe_id' => $skp_tipe_id,
            'periode_id' => $periode_id,
            'jml_data' => $jml_data,
            'records' => $data
        ];

        // Only add these fields if data exists
        if($jml_data > 0) {
            $response['skp_tipe'] = $data[0]->skp_tipe;
            $response['periode'] = $data[0]->periode;
            $response['rentang'] = $data[0]->rentang;
            $response['jml_bln'] = $data[0]->jml_bln;
        }

        return response()->json($response);
    }

    function is_vrf_skp(Request $request){
        $nip_penilai = $request->nip_penilai;
        $exists = DB::table('skp_kontrak')
                ->where('penilai_nip', $nip_penilai)
                ->exists();
        return response()->json([
            'exists' => $exists
        ]);
    }

    function is_vrf_skp_data(Request $request){
        $nip_penilai = $request->nip_penilai;
        $data = DB::table('skp_kontrak')
                ->where('penilai_nip', $nip_penilai)
                ->first();
        return response()->json($data);
    }

    function cek_perilaku_kerja_template(Request $request){
        $uid = $request->uid;
        $data = DB::table('perilaku_kerja')
                ->where('uid', $uid)
                ->exists();
        return response()->json($data);
    }

    function tambah_perilaku_kerja_template(Request $request){
        $uid = $request->uid;
        
        $perilaku_kerja_kode = [1, 2, 3, 4, 5, 6, 7];
        $ekspektasi_pimpinan = [
            'Memberikan pelayanan yang maksimal',
            'Menjunjung tinggi prinsip-prinsip yang sudah ditetapkan Unit kerja/Organisasi/Lembaga',
            'Menyelesaikan setiap pekerjaan sesuai dengan target dan standar mutu yang ditetapkan',
            'Membangun komunikasi yang lebih terbuka dan menjaga hubungan baik dengan stakeholder',
            'Melaksanakan perintah dari pimpinan dengan sungguh-sungguh',
            'Aktif berkomunikasi dengan sesama pegawai terkait dengan pelayanan',
            'berkolaborasi dengan bagian lain yang berhubungan tugas pokok dan fungsinya'
        ];

        $data = [];
        for($i = 0; $i < count($perilaku_kerja_kode); $i++) {
            $data[] = [
                'uid' => $uid,
                'perilaku_kerja_kode' => $perilaku_kerja_kode[$i],
                'ekspektasi_pimpinan' => $ekspektasi_pimpinan[$i]
            ];
        }

        DB::table('perilaku_kerja')->insert($data);

        return response()->json([
            'success' => true,
            'message' => 'Data perilaku kerja berhasil ditambahkan'
        ]);
    }

    function tambah_perilaku_kerja_template_blank(Request $request){
        $uid = $request->uid;
        
        $perilaku_kerja_kode = [1, 2, 3, 4, 5, 6, 7];
        $ekspektasi_pimpinan = [
            '',
            '',
            '',
            '',
            '',
            '',
            ''
        ];

        $data = [];
        for($i = 0; $i < count($perilaku_kerja_kode); $i++) {
            $data[] = [
                'uid' => $uid,
                'perilaku_kerja_kode' => $perilaku_kerja_kode[$i],
                'ekspektasi_pimpinan' => $ekspektasi_pimpinan[$i]
            ];
        }

        DB::table('perilaku_kerja')->insert($data);

        return response()->json([
            'success' => true,
            'message' => 'Data perilaku kerja berhasil ditambahkan'
        ]);
    }

    function ubah_perilaku_kerja(Request $request){
        try {
            $uid = $request->uid;
            $perilaku_kerja_kode = $request->perilaku_kerja_kode;
            $ekspektasi_pimpinan = $request->ekspektasi_pimpinan;
            
            // Validate required fields
            if (!$uid || !$perilaku_kerja_kode) {
                return response()->json([
                    'success' => false,
                    'message' => 'UID dan kode perilaku kerja harus diisi'
                ], 400);
            }

            // Check if record exists
            $exists = DB::table('perilaku_kerja')
                    ->where('uid', $uid)
                    ->where('perilaku_kerja_kode', $perilaku_kerja_kode)
                    ->exists();

            if (!$exists) {
                return response()->json([
                    'success' => false, 
                    'message' => 'Data perilaku kerja tidak ditemukan'
                ], 404);
            }

            // Update record
            DB::table('perilaku_kerja')
                ->where('uid', $uid)
                ->where('perilaku_kerja_kode', $perilaku_kerja_kode)
                ->update([
                    'ekspektasi_pimpinan' => $ekspektasi_pimpinan
                ]);

            return response()->json([
                'success' => true,
                'message' => 'Data perilaku kerja berhasil diperbarui'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }

    function get_poin_aktifitas(Request $request){  
        $nip = $request->nip;
        $tanggal_mulai = $request->tanggal_mulai;
        $tanggal_selesai = $request->tanggal_selesai;
        $data = DB::table('aktifitas_kinerja')
                ->where('nip', $nip)
                ->whereBetween('tanggal_mulai', [$tanggal_mulai, $tanggal_selesai])
                ->get();

        $total_poin = $data->sum('poin');

        $hk_ae = $data->where('rating_hasil_kerja','AE')->count();
        $hk_se = $data->where('rating_hasil_kerja','SE')->count();
        $hk_be = $data->where('rating_hasil_kerja','BE')->count();

        $total_aktifitas = $data->count();
        $total_aktifitas_dinilai = $data->whereNotIn('rating_hasil_kerja', ['BM'])->count();

        $bobot_nilai = $total_aktifitas * 2;

        $bobot_persen = $bobot_nilai > 0 ? number_format(($total_poin / $bobot_nilai) * 100, 2, '.', '') : 0;

        return response()->json([
            'success' => true,
            'data' => $total_poin,
            'hk_ae' => $hk_ae,
            'hk_se' => $hk_se,
            'hk_be' => $hk_be,
            'jml_aktifitas_dinilai' => $total_aktifitas_dinilai,
            'total_aktifitas' => $total_aktifitas,
            'bobot_persen' => $bobot_persen
        ]);
    }

    function rating_hasil_kerja_aktifitas(Request $request){
        $nip = $request->nip;
        $tanggal_mulai = $request->tanggal_mulai;
        $tanggal_selesai = $request->tanggal_selesai;
        $data = DB::table('aktifitas_kinerja')
                ->select('rating_hasil_kerja', DB::raw('count(*) as count'))
                ->where('nip', $nip)
                ->whereBetween('tanggal_mulai', [$tanggal_mulai, $tanggal_selesai])
                ->groupBy('rating_hasil_kerja')
                ->orderByDesc('count')
                ->first();
        
        return response()->json([
            'success' => true,
            'data' => $data ? $data->rating_hasil_kerja : null
        ]);
    }

    function ubah_skp_kontrak_vrf(Request $request){    
        $uid = $request->uid;
        $rating_hasil_kerja = $request->rating_hasil_kerja;
        $rating_perilaku_kerja = $request->rating_perilaku_kerja; 
        $predikat_kinerja = $request->predikat_kinerja;
        $poin = $request->poin;

        $hk_ae = $request->hk_ae;
        $hk_se = $request->hk_se;
        $hk_be = $request->hk_be;
        $bobot_persen = $request->bobot_persen;


        // Validate input data
        if (empty($uid)) {
            return response()->json([
                'success' => false,
                'message' => 'UID SKP Kontrak harus diisi'
            ], 400);
        }

        try {
            // Check if record exists first
            $exists = DB::table('skp_kontrak')
                ->where('uid', $uid)
                ->exists();

            if (!$exists) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data SKP Kontrak tidak ditemukan'
                ], 404);
            }

            // Prepare update data
            $updateData = [];
            if (!empty($rating_hasil_kerja)) {
                $updateData['rating_hasil_kerja'] = $rating_hasil_kerja;
            }
            if (!empty($rating_perilaku_kerja)) {
                $updateData['rating_perilaku_kerja'] = $rating_perilaku_kerja;
            }
            if (!empty($predikat_kinerja)) {
                $updateData['predikat_kinerja'] = $predikat_kinerja;
            }
            if (!empty($poin)) {
                $updateData['poin'] = $poin;
            }
            if (!empty($hk_ae)) {
                $updateData['hk_ae'] = $hk_ae;
            }
            if (!empty($hk_se)) {
                $updateData['hk_se'] = $hk_se;
            }
            if (!empty($hk_be)) {
                $updateData['hk_be'] = $hk_be;
            }
            if (!empty($bobot_persen)) {
                $updateData['bobot_persen'] = $bobot_persen;
            }

            // Only update if there are fields to update
            if (!empty($updateData)) {
                $updated = DB::table('skp_kontrak')
                    ->where('uid', $uid)
                    ->update($updateData);

                return response()->json([
                    'success' => true,
                    'message' => 'Data SKP Kontrak berhasil diperbarui',
                    'updated_data' => $updateData
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Tidak ada data yang diperbarui'
                ], 400);
            }

        } catch (\Exception $e) {
            \Log::error('Error updating SKP Kontrak: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }

    function ubah_perilaku_kerja_vrf(Request $request){
        try {
            $uid = $request->uid;
            $rating_perilaku_kerja = $request->rating_perilaku_kerja;

            // Check if record exists
            $exists = DB::table('skp_kontrak')
                    ->where('uid', $uid)
                    ->exists();

            if (!$exists) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data SKP Kontrak tidak ditemukan'
                ], 404);
            }

            // Update the record
            DB::table('skp_kontrak')
                ->where('uid', $uid)
                ->update(['rating_perilaku_kerja' => $rating_perilaku_kerja]);

            // Get updated data
            $updated = DB::table('skp_kontrak')
                    ->where('uid', $uid)
                    ->select('rating_perilaku_kerja')
                    ->first();

            return response()->json([
                'success' => true,
                'message' => 'Rating perilaku kerja berhasil diperbarui',
                'data' => $updated
            ]);

        } catch (\Exception $e) {
            \Log::error('Error updating perilaku kerja: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }

    function ubah_predikat_kinerja(Request $request){
        $uid = $request->uid;
        $predikat_kinerja = $request->predikat_kinerja;

        // Check if record exists
        $exists = DB::table('skp_kontrak')
                ->where('uid', $uid)
                ->exists();

        if (!$exists) {
            return response()->json([
                'success' => false,
                'message' => 'Data SKP Kontrak tidak ditemukan'
            ], 404);
        }

        // Update the record
        DB::table('skp_kontrak')
            ->where('uid', $uid)
            ->update(['predikat_kinerja' => $predikat_kinerja]);
        
        return response()->json([
            'success' => true,
            'message' => 'Predikat kinerja berhasil diperbarui',
            'data' => $predikat_kinerja
        ]);
        
    }

    function get_rating(Request $request){
        $uid = $request->uid;
        $data = DB::table('skp_kontrak')
                ->where('uid', $uid)
                ->select('rating_perilaku_kerja', 'rating_hasil_kerja', 'predikat_kinerja', 'poin','status_vrf_id')
                ->first();
        return $data;
    }

    function ubah_status_vrf(Request $request){
        $uid = $request->uid;
        $status_vrf_id = $request->status_vrf_id;
        DB::table('skp_kontrak')
            ->where('uid', $uid)
            ->update(['status_vrf_id' => $status_vrf_id]);
        return response()->json([
            'success' => true,
            'message' => 'Status verifikasi berhasil diperbarui',
            'data' => $status_vrf_id
        ]);
    }

    // ---VERIFIKASI END---

    // ---ADMIN START---
    function listing_vrf(Request $request){
        $tahun = $request->tahun;       
        $periode_id = $request->periode_id;
        $skp_tipe_id = $request->skp_tipe_id;

        $data = DB::table('skp_kontrak')
                ->select('penilai_nip', 
                         'penilai_nama',
                         'penilai_jabatan',
                         'penilai_unit_kerja'
                         )
                ->where('tahun', $tahun)
                ->where('periode_id', $periode_id)
                ->where('skp_tipe_id', $skp_tipe_id)
                ->groupBy('penilai_nip', 
                         'penilai_nama',
                         'penilai_jabatan', 
                         'penilai_unit_kerja')
                ->get();

        $jml_data = $data->count();
                
        return response()->json([
            'success' => true,
            'data' => $data->toArray(),
            'jml_data' => $jml_data
        ]);
    }
    // ---ADMIN END---

    // ---KONTRAK KINERJA SKP START---
    
    function get_skp_kontrak(Request $request){
        $uid = $request->skp_kontrak_uid;
        $data = DB::table('skp_kontrak')
                ->select('skp_kontrak.*')
                ->where('uid', $uid)
                ->first();
        return $data;
            
    }
    // ---KONTRAK KINERJA SKP END---

    // ---AJUAN SKP START---

    function list_ajuan_skp(Request $request){
        $nip = $request->nip;
        $tahun = $request->tahun;
        $rate_sts = [
            'AE' => 'DIATAS EKPEKTASI',
            'SE' => 'SESUAI EKPEKTASI',
            'BE' => 'DIBAWAH EKPEKTASI'
        ];
        $query = DB::table('skp_kontrak as a')
                ->join('ref_skp_tipe as b', 'b.id','=','a.skp_tipe_id')
                ->join('ref_periode as c', 'c.id','=','a.periode_id')
                ->join('ref_status as d', 'd.id','=','a.status_id' )
                ->join('ref_status_vrf as e','e.id','=','a.status_vrf_id')
                ->select(
                    'a.id',
                    'a.uid',
                    'a.tahun',
                    'a.skp_tipe_id',
                    'b.skp_tipe',
                    'a.periode_id',
                    'c.periode',
                    'c.rentang as periode_rentang',
                    'a.periode_awal',
                    'a.periode_akhir',
                    'a.pegawai_nip',
                    'a.pegawai_nama',
                    'a.penilai_nip',
                    'a.penilai_nama',
                    'a.status_id',
                    'd.status',
                    'a.status_vrf_id',
                    'e.status_vrf',
                    DB::raw("CASE a.rating_hasil_kerja
                        WHEN 'AE' THEN '{$rate_sts['AE']}'
                        WHEN 'SE' THEN '{$rate_sts['SE']}'
                        WHEN 'BE' THEN '{$rate_sts['BE']}'
                        ELSE a.rating_hasil_kerja END as rating_hasil_kerja"),
                    DB::raw("CASE a.rating_perilaku_kerja
                        WHEN 'AE' THEN '{$rate_sts['AE']}'
                        WHEN 'SE' THEN '{$rate_sts['SE']}'
                        WHEN 'BE' THEN '{$rate_sts['BE']}'
                        ELSE a.rating_perilaku_kerja END as rating_perilaku_kerja"),
                    'a.predikat_kinerja',
                    'a.poin',
                    'a.bobot_persen'
                )
                ->where('pegawai_nip', $nip);
        
        // Filter berdasarkan tahun jika parameter tahun ada
        if ($tahun) {
            $query->where('tahun', $tahun);
        }
        
        $data = $query->get();

        return $data;
    }

    // ---REFERENSI START--

    // --Perilaku Kerja
    public function ref_perilaku_kerja(){
        $data = DB::table('ref_perilaku_kerja')
                ->select('ref_perilaku_kerja.*')
                ->orderBy('kode')
                ->get()
                ->groupBy(function($item) {
                    // Get the main parent code (e.g., for "1.2" return "1")
                    return explode('.', $item->kode)[0];
                });
        return $data;
    }

    public function ref_skp_tipe(Request $request){
        $skp_id = $request->skp_id;
        $data = DB::table('ref_skp_tipe')
                ->select('ref_skp_tipe.*')
                ->where('id', $skp_id)
                ->first();
        return $data;
    }

    //  ---Hasil Kerja
    public function ref_hasil_kerja(Request $request){
        $kode = $request->kode;
        $data = DB::table('ref_hasil_kerja')
                ->select('ref_hasil_kerja.*')
                ->where('kode', $kode)
                ->first();
        return $data;
    }

    //  ---Satuan
    public function ref_satuan(Request $request){
        $data = DB::table('ref_satuan')
                ->select('okgId as id','okgNama as satuan')
                ->get();
        return $data;
    }

    //  ---Periode
    public function ref_periode(Request $request){
        $data = DB::table('ref_periode')
                ->get();
        return $data;
    }

    //  ---Predikat
    public function ref_predikat(Request $request){
        $data = DB::table('ref_predikat')
                ->get();
        return $data;
    }

    //  ---Status Ajuan
    public function ref_status(Request $request){
        $data = DB::table('ref_status')
                ->get();
        return $data;
    }

    //  ---Status Verifikasi
    public function ref_status_vrf(Request $request){
        $data = DB::table('ref_status_vrf')
                ->get();
        return $data;
    }

    //  ---SKP Tipe
    public function ref_tipe_skp(Request $request){
        $data = DB::table('ref_skp_tipe')
                ->get();
        return $data;
    }

    // ---REFERENSI END--

    // ---REKAP POIN SEMESTER
    public function rekap_poin_semester(Request $request){
        $periode_awal = $request->periode_awal; 
        $periode_akhir = $request->periode_akhir;
        $nip = $request->nip;
        $semester = $request->semester;
        $id_periode = $request->id_periode; //uuid dari periode

        // Validasi parameter periode
        if(empty($periode_awal) || empty($periode_akhir)) {
            return response()->json([
                'success' => false,
                'message' => 'Periode awal dan periode akhir harus diisi',
                'data' => []
            ], 400);
        }

        try {
            // Query data from skp_kontrak table and group by pegawai_nip
            $query = DB::table('skp_kontrak')
                ->whereBetween('periode_awal', [$periode_awal, $periode_akhir])
                ->whereBetween('periode_akhir', [$periode_awal, $periode_akhir])
                ->whereNotNull('bobot_persen') // Only include records where bobot_persen is not null
                ->where('bobot_persen', '!=', ''); // And not empty string

            // Add NIP filter if provided 
            if (!empty($nip)) {
                $query->where('pegawai_nip', $nip);
            }

            $data = $query->select(
                    DB::raw(($semester !== null ? (int)$semester : 'NULL') . ' as semester'),
                    DB::raw("'" . $id_periode . "' as id_periode"),
                    DB::raw("DATE_FORMAT('" . $periode_awal . "', '%Y-%m-%d') as periode_awal"),
                    DB::raw("DATE_FORMAT('" . $periode_akhir . "', '%Y-%m-%d') as periode_akhir"),
                    'pegawai_id',
                    'pegawai_nip',
                    'pegawai_email',
                    'pegawai_nama', 
                    DB::raw('SUM(poin) as poin_aktifitas'),
                    DB::raw('SUM(bobot_persen) / COUNT(*) as bobot_persen'),
                    DB::raw('COUNT(*) as jumlah_ajuan')
                    
                )
                ->groupBy('pegawai_nip', 'pegawai_email', 'pegawai_nama','pegawai_id')
                ->get();

            // Add poin_remun calculation based on formula (bobot_persen/100)*28
            $data = $data->map(function($item) {
                $item->poin_remun = ($item->bobot_persen / 100) * 28;
                // Add poin_remun_akhir calculation
                $item->poin_remun_akhir = $item->poin_remun >= 28 ? 28 : $item->poin_remun;
                return $item; 
            });

            if($data->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data tidak ditemukan',
                    'data' => []
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Data rekap poin semester berhasil diambil',
                'periode' => [
                    'awal' => $periode_awal,
                    'akhir' => $periode_akhir,
                    'id_periode' => $id_periode
                ],
                'semester' => $semester,
                'data' => $data
            ]);

        } catch(\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage(),
                'data' => []
            ], 500);
        }
    }

    // ---HTML OOUTPUT START

    // ---Portofolio
    public function get_portofolio_html(Request $request){
        $nip = $request->nip;
        $uid = $request->uid;

        // Ambil data portofolio_kinerja
        $query = DB::table('portofolio_kinerja')
            ->select(
                'id',
                'uid',
                'tahun',
                DB::raw("CONCAT('[ ', id, '-', SUBSTRING(uid, 1, 4), ' ] - ', jabatan) as no_poki"),
                DB::raw("CONCAT(id, '-', SUBSTRING(uid, 1, 4)) as no_portofolio"),
                'no_sk',
                'nip',
                'email',
                'nama',
                'jabatan_struktural',
                'jabatan_struktural_id',
                'jabatan_fungsional',
                'jabatan_fungsional_id',
                'unit_kerja',
                'unit_kerja_id',
                'homebase',
                'homebase_id',
                'pangkat',
                'pangkat_id',
                'status_kerja',
                'level_pegawai'
            );

        // Filter
        if (!empty($nip)) {
            $query->where('nip', $nip);
        }
        if (!empty($uid)) {
            $query->where('uid', $uid);
        }

        $portofolios = $query->get();

        // Mulai HTML dengan CDN Tailwind dan font Poppins
        $html = '<!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>Portofolio Kinerja</title>
          
            <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
            <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
            <style>
                body, .table-sm, .table-sm th, .table-sm td, .print-btn, .text-sm, .text-base, .text-xs, .font-bold, .font-semibold, .font-normal, .block, .text-center, .text-left, .text-right, .text-gray-400, .text-gray-500, .text-gray-600, .bg-gray-50, .bg-blue-100, .bg-gray-100, .bg-blue-50, .bg-white, .border, .rounded, .rounded-lg, .shadow-sm, .overflow-x-auto, .mb-4, .mb-5, .mb-8, .px-2, .px-3, .py-1, .py-1.5, .py-2, .w-1\/6, .w-1\/3, .col-no, .cursor-pointer, .border-none, .bg-blue-600, .hover\:bg-blue-700, .text-white, .print-btn, .table-sm th, .table-sm td {
                    font-family: "Poppins", Arial, Helvetica, sans-serif !important;
                }
                @media print {
                    .print-btn { display: none !important; }
                }
                .table-sm th, .table-sm td { padding-top: 0.25rem !important; padding-bottom: 0.25rem !important; padding-left: 0.5rem !important; padding-right: 0.5rem !important; font-size: 0.85rem !important; }
                .table-sm th { font-size: 0.9rem !important; }
                /* Kolom No lebar 80px */
                .col-no { width: 80px !important; min-width: 80px !important; max-width: 80px !important; }
            </style>
        </head>
        <body class="bg-gray-50 p-4 text-sm" style="font-family: Poppins, Arial, Helvetica, sans-serif;">';

        // Tombol cetak (print) DIHAPUS

        if (count($portofolios) == 0) {
            $html .= "<div class='text-gray-600 text-sm' style='font-family: Poppins, Arial, Helvetica, sans-serif;'>Tidak ada data portofolio ditemukan.</div>";
        }

        foreach ($portofolios as $item) {
            $html .= '<div class="overflow-x-auto mb-8">';
            $html .= '<table class="min-w-full border border-gray-300 mb-4 bg-white shadow-sm rounded-lg table-sm text-sm" style="font-family: Poppins, Arial, Helvetica, sans-serif;">';
            $html .= '<tr class="bg-blue-100 font-bold text-sm">
                        <th colspan="4" class="px-3 py-2 text-left text-base" style="font-family: Poppins, Arial, Helvetica, sans-serif;">Portofolio Kinerja: '.$item->no_poki.'</th>
                      </tr>';
            $html .= '<tr>
                        <td class="border px-3 py-1 font-semibold w-1/6" style="font-family: Poppins, Arial, Helvetica, sans-serif;">Tahun</td><td class="border px-3 py-1 w-1/3" style="font-family: Poppins, Arial, Helvetica, sans-serif;">'.$item->tahun.'</td>
                        <td class="border px-3 py-1 font-semibold w-1/6" style="font-family: Poppins, Arial, Helvetica, sans-serif;">No SK</td><td class="border px-3 py-1 w-1/3" style="font-family: Poppins, Arial, Helvetica, sans-serif;">'.$item->no_sk.'</td>
                      </tr>';
            $html .= '<tr>
                        <td class="border px-3 py-1 font-semibold" style="font-family: Poppins, Arial, Helvetica, sans-serif;">NIP</td><td class="border px-3 py-1" style="font-family: Poppins, Arial, Helvetica, sans-serif;">'.$item->nip.'</td>
                        <td class="border px-3 py-1 font-semibold" style="font-family: Poppins, Arial, Helvetica, sans-serif;">Nama</td><td class="border px-3 py-1" style="font-family: Poppins, Arial, Helvetica, sans-serif;">'.$item->nama.'</td>
                      </tr>';
            $html .= '<tr>
                        <td class="border px-3 py-1 font-semibold" style="font-family: Poppins, Arial, Helvetica, sans-serif;">Jabatan Struktural</td><td class="border px-3 py-1" style="font-family: Poppins, Arial, Helvetica, sans-serif;">'.$item->jabatan_struktural.'</td>
                        <td class="border px-3 py-1 font-semibold" style="font-family: Poppins, Arial, Helvetica, sans-serif;">Jabatan Fungsional</td><td class="border px-3 py-1" style="font-family: Poppins, Arial, Helvetica, sans-serif;">'.$item->jabatan_fungsional.'</td>
                      </tr>';
            $html .= '<tr>
                        <td class="border px-3 py-1 font-semibold" style="font-family: Poppins, Arial, Helvetica, sans-serif;">Unit Kerja</td><td class="border px-3 py-1" style="font-family: Poppins, Arial, Helvetica, sans-serif;">'.$item->unit_kerja.'</td>
                        <td class="border px-3 py-1 font-semibold" style="font-family: Poppins, Arial, Helvetica, sans-serif;">Homebase</td><td class="border px-3 py-1" style="font-family: Poppins, Arial, Helvetica, sans-serif;">'.$item->homebase.'</td>
                      </tr>';
            $html .= '<tr>
                        <td class="border px-3 py-1 font-semibold" style="font-family: Poppins, Arial, Helvetica, sans-serif;">Pangkat</td><td class="border px-3 py-1" style="font-family: Poppins, Arial, Helvetica, sans-serif;">'.$item->pangkat.'</td>
                        <td class="border px-3 py-1 font-semibold" style="font-family: Poppins, Arial, Helvetica, sans-serif;">Status Kerja</td><td class="border px-3 py-1" style="font-family: Poppins, Arial, Helvetica, sans-serif;">'.$item->status_kerja.'</td>
                      </tr>';
            $html .= '<tr>
                        <td class="border px-3 py-1 font-semibold" style="font-family: Poppins, Arial, Helvetica, sans-serif;">Level Pegawai</td><td class="border px-3 py-1" colspan="3" style="font-family: Poppins, Arial, Helvetica, sans-serif;">'.$item->level_pegawai.'</td>
                      </tr>';
            $html .= '</table>';

            // Ambil detail rubrik kinerja
            $detail_rubrik_kinerja = DB::table('rencana_hasil_kerja_atasan')
                ->select('id','rubrik_kinerja', 'kategori')
                ->where('portofolio_kinerja_uid', $item->uid)
                ->get();

            if (count($detail_rubrik_kinerja) > 0) {
                foreach ($detail_rubrik_kinerja as $rubrik) {
                    $html .= '<div class="overflow-x-auto mb-4">';
                    $html .= '<table class="min-w-full border border-gray-300 bg-white shadow-sm rounded-lg table-sm text-sm" style="font-family: Poppins, Arial, Helvetica, sans-serif;">';
                    $html .= '<tr class="bg-gray-100 font-bold text-sm">
                        <th colspan="5" class="px-3 py-2 text-left" style="font-family: Poppins, Arial, Helvetica, sans-serif;">Rubrik Kinerja: '.$rubrik->rubrik_kinerja.' <span class="font-normal text-xs text-gray-500" style="font-family: Poppins, Arial, Helvetica, sans-serif;">('.$rubrik->kategori.')</span></th>
                    </tr>';
                    $html .= '<tr class="bg-gray-50 text-sm">
                        <th class="border px-2 py-1 col-no text-center" style="font-family: Poppins, Arial, Helvetica, sans-serif;">No</th>
                        <th class="border px-2 py-1" style="font-family: Poppins, Arial, Helvetica, sans-serif;">Kegiatan</th>
                        <th class="border px-2 py-1" style="font-family: Poppins, Arial, Helvetica, sans-serif;">Ukuran Keberhasilan</th>
                        <th class="border px-2 py-1" style="font-family: Poppins, Arial, Helvetica, sans-serif;">Realisasi</th>
                        <th class="border px-2 py-1" style="font-family: Poppins, Arial, Helvetica, sans-serif;">Aspek</th>
                    </tr>';

                    $detail_kegiatan = DB::table('rencana_hasil_kerja_item')
                        ->where('rhka_id', $rubrik->id)
                        ->select('id AS rhki_id',
                                'rhka_id',
                                'kegiatan',
                                'ukuran_keberhasilan',
                                'realisasi',
                                'aspek_kuantitas',
                                'aspek_kualitas',
                                'aspek_waktu'
                            )
                        ->get();

                    if (count($detail_kegiatan) == 0) {
                        $html .= '<tr><td colspan="5" class="text-center text-gray-400 py-2" style="font-family: Poppins, Arial, Helvetica, sans-serif;">Tidak ada kegiatan</td></tr>';
                    } else {
                        $no = 1;
                        foreach ($detail_kegiatan as $keg) {
                            $html .= '<tr class="bg-blue-50">';
                            $html .= '<td class="border px-2 py-1 text-center col-no" style="font-family: Poppins, Arial, Helvetica, sans-serif;">'.$no.'</td>';
                            $html .= '<td class="border px-2 py-1" style="font-family: Poppins, Arial, Helvetica, sans-serif;">'.$keg->kegiatan.'</td>';
                            $html .= '<td class="border px-2 py-1" style="font-family: Poppins, Arial, Helvetica, sans-serif;">'.$keg->ukuran_keberhasilan.'</td>';
                            $html .= '<td class="border px-2 py-1" style="font-family: Poppins, Arial, Helvetica, sans-serif;">'.$keg->realisasi.'</td>';
                            $html .= '<td class="border px-2 py-1" style="font-family: Poppins, Arial, Helvetica, sans-serif;">
                                <span class="block"><span class="font-semibold">Kuantitas:</span> '.($keg->aspek_kuantitas ?? '-').'</span>
                                <span class="block"><span class="font-semibold">Kualitas:</span> '.($keg->aspek_kualitas ?? '-').'</span>
                                <span class="block"><span class="font-semibold">Waktu:</span> '.($keg->aspek_waktu ?? '-').'</span>
                            </td>';
                            $html .= '</tr>';
                            $no++;
                        }
                    }
                    $html .= '</table>';
                    $html .= '</div>';
                }
            } else {
                $html .= '<div class="mb-5 text-gray-500 text-sm" style="font-family: Poppins, Arial, Helvetica, sans-serif;">Tidak ada rubrik kinerja untuk portofolio ini.</div>';
            }
            $html .= '</div>';
        }

        $html .= '</body></html>';

        return response($html, 200)->header('Content-Type', 'text/html');
    }

    // ---Laporan Aktifitas
    public function laporan_aktifitas_html(Request $request){
        $nip = $request->input('nip');
        $tahun = $request->input('tahun');
        $download = $request->input('download', false);

        $query = DB::table('aktifitas_kinerja');

        if (!empty($nip)) {
            $query->where('nip', $nip);
        }

        if (!empty($tahun)) {
            $query->whereYear('tanggal_mulai', $tahun);
        }

        $data = $query->get();

        // Rekap per bulan
        $rekap_perbulan = [];
        $rekap_poin_perbulan = [];
        $rekap_bobot_poin = [];
        $rekap_rating_ae = [];
        $rekap_rating_se = [];
        $rekap_rating_be = [];
        $rekap_bukti_gambar = [];
        $rekap_bukti_dokumen = [];
        $rekap_bukti_tautan = [];
        for ($bulan = 1; $bulan <= 12; $bulan++) {
            $rekap_perbulan[$bulan] = 0;
            $rekap_poin_perbulan[$bulan] = 0;
            $rekap_bobot_poin[$bulan] = 0;
            $rekap_rating_ae[$bulan] = 0;
            $rekap_rating_se[$bulan] = 0;
            $rekap_rating_be[$bulan] = 0;
            $rekap_bukti_gambar[$bulan] = 0;
            $rekap_bukti_dokumen[$bulan] = 0;
            $rekap_bukti_tautan[$bulan] = 0;
        }

        foreach ($data as $item) {
            $bulan = date('n', strtotime($item->tanggal_mulai));
            // 1. Jumlah Aktifitas
            if (isset($rekap_perbulan[$bulan])) {
                $rekap_perbulan[$bulan]++;
            }
            // 2. Poin Aktifitas
            if (isset($rekap_poin_perbulan[$bulan])) {
                $poin = isset($item->poin) ? floatval($item->poin) : 1;
                $rekap_poin_perbulan[$bulan] += $poin;
            }
            // 3-5. Rating AE, SE, BE
            $rating = isset($item->rating_hasil_kerja) ? $item->rating_hasil_kerja : null;
            if ($rating == 'AE') $rekap_rating_ae[$bulan]++;
            if ($rating == 'SE') $rekap_rating_se[$bulan]++;
            if ($rating == 'BE') $rekap_rating_be[$bulan]++;
            // 6. Bukti Gambar
            if (isset($item->gambar)) {
                $gambar = $item->gambar;
                if (is_string($gambar)) {
                    $decoded = json_decode($gambar, true);
                    if (is_array($decoded)) {
                        $jumlah_gambar = count($decoded);
                    } else {
                        $jumlah_gambar = !empty($gambar) ? 1 : 0;
                    }
                } elseif (is_array($gambar)) {
                    $jumlah_gambar = count($gambar);
                } else {
                    $jumlah_gambar = 0;
                }
                $rekap_bukti_gambar[$bulan] += $jumlah_gambar;
            }
            // 7. Bukti Dokumen
            if (isset($item->dokumen)) {
                $dokumen = $item->dokumen;
                if (is_string($dokumen)) {
                    $decoded = json_decode($dokumen, true);
                    if (is_array($decoded)) {
                        $jumlah_dokumen = count($decoded);
                    } else {
                        $jumlah_dokumen = !empty($dokumen) ? 1 : 0;
                    }
                } elseif (is_array($dokumen)) {
                    $jumlah_dokumen = count($dokumen);
                } else {
                    $jumlah_dokumen = 0;
                }
                $rekap_bukti_dokumen[$bulan] += $jumlah_dokumen;
            }
            // 8. Bukti Tautan
            if (isset($item->tautan)) {
                $tautan = $item->tautan;
                if (is_string($tautan)) {
                    $decoded = json_decode($tautan, true);
                    if (is_array($decoded)) {
                        $jumlah_tautan = count($decoded);
                    } else {
                        $jumlah_tautan = !empty($tautan) ? 1 : 0;
                    }
                } elseif (is_array($tautan)) {
                    $jumlah_tautan = count($tautan);
                } else {
                    $jumlah_tautan = 0;
                }
                $rekap_bukti_tautan[$bulan] += $jumlah_tautan;
            }
        }

        // Hitung bobot poin per bulan
        for ($bulan = 1; $bulan <= 12; $bulan++) {
            if ($rekap_perbulan[$bulan] > 0) {
                $rekap_bobot_poin[$bulan] = ($rekap_poin_perbulan[$bulan] / ($rekap_perbulan[$bulan] * 2)) * 100;
            } else {
                $rekap_bobot_poin[$bulan] = 0;
            }
        }

        // Hitung total per baris
        $total_aktifitas = array_sum($rekap_perbulan);
        $total_poin = array_sum($rekap_poin_perbulan);
        $total_ae = array_sum($rekap_rating_ae);
        $total_se = array_sum($rekap_rating_se);
        $total_be = array_sum($rekap_rating_be);
        $total_gambar = array_sum($rekap_bukti_gambar);
        $total_dokumen = array_sum($rekap_bukti_dokumen);
        $total_tautan = array_sum($rekap_bukti_tautan);

        // Hitung total bobot poin
        if ($total_aktifitas > 0) {
            $total_bobot_poin = ($total_poin / ($total_aktifitas * 2)) * 100;
        } else {
            $total_bobot_poin = 0;
        }

        // Buat HTML tabel dengan Tailwind CSS
        $html = '
        <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
        <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
        <style>
            .row-header { background-color: #f3f4f6; font-weight: bold; }
            .kolom-komponen { text-align: left !important; }
        </style>
        ';
        $html .= '<div class="p-3">';

        $html .= '<div class="overflow-x-auto">';
        $html .= '<table class="min-w-full text-xs md:text-sm border border-gray-300 rounded-lg">';
        $html .= '<thead>
        <tr>
            <th rowspan="2" class="border border-gray-300 px-2 py-2 bg-gray-100 text-center align-middle" style="width:30px;">No</th>
            <th rowspan="2" class="border border-gray-300 px-2 py-2 bg-gray-100 text-center align-middle" style="width:260px;">KOMPONEN AKTIFITAS</th>
            <th colspan="12" class="border border-gray-300 px-2 py-2 bg-gray-100 text-center">BULAN</th>
            <th rowspan="2" class="border border-gray-300 px-2 py-2 bg-gray-100 text-center align-middle" style="width:60px;">TOTAL</th>
        </tr>
        <tr>';
        for ($bulan = 1; $bulan <= 12; $bulan++) {
            $html .= '<th class="border border-gray-300 px-2 py-1 bg-gray-50 text-center" style="width:32px;">'.$bulan.'</th>';
        }
        $html .= '</tr>
        </thead>
        <tbody>';

        // --- JUMLAH DAN POIN AKTIVITAS (Header)
        $html .= '<tr class="row-header"><td colspan="15" class="text-left px-2 py-2 border border-gray-300 bg-gray-50">JUMLAH DAN POIN AKTIFITAS</td></tr>';

        // 1. Jumlah Aktifitas
        $html .= '<tr>
            <td class="border border-gray-300 px-2 py-1 text-center">1</td>
            <td class="border border-gray-300 px-2 py-1 kolom-komponen">Jumlah Aktifitas</td>';
        for ($bulan = 1; $bulan <= 12; $bulan++) {
            $html .= '<td class="border border-gray-300 px-2 py-1 text-center">'.$rekap_perbulan[$bulan].'</td>';
        }
        $html .= '<td class="border border-gray-300 px-2 py-1 text-center">'.$total_aktifitas.'</td></tr>';

        // 2. Poin Aktifitas
        $html .= '<tr>
            <td class="border border-gray-300 px-2 py-1 text-center">2</td>
            <td class="border border-gray-300 px-2 py-1 kolom-komponen">Poin Aktifitas</td>';
        for ($bulan = 1; $bulan <= 12; $bulan++) {
            $html .= '<td class="border border-gray-300 px-2 py-1 text-center">'.(is_float($rekap_poin_perbulan[$bulan]) ? number_format($rekap_poin_perbulan[$bulan],2,'.','') : $rekap_poin_perbulan[$bulan]).'</td>';
        }
        $html .= '<td class="border border-gray-300 px-2 py-1 text-center">'.(is_float($total_poin) ? number_format($total_poin,2,'.','') : $total_poin).'</td></tr>';

        // 3. Bobot Poin Aktifitas
        $html .= '<tr>
            <td class="border border-gray-300 px-2 py-1 text-center">3</td>
            <td class="border border-gray-300 px-2 py-1 kolom-komponen">Bobot Poin Aktifitas (%)</td>';
        for ($bulan = 1; $bulan <= 12; $bulan++) {
            $html .= '<td class="border border-gray-300 px-2 py-1 text-center"><span class="text-xs">'.number_format($rekap_bobot_poin[$bulan],0,'.','').'</span></td>';
        }
        $html .= '<td class="border border-gray-300 px-2 py-1 text-center">'.number_format($total_bobot_poin,0,'.','').'</td></tr>';

        // --- RATING HASIL KERJA (Header)
        $html .= '<tr class="row-header"><td colspan="15" class="text-left px-2 py-2 border border-gray-300 bg-gray-50">RATING HASIL KERJA</td></tr>';

        // 4. Diatas Ekspektasi (AE)
        $html .= '<tr>
            <td class="border border-gray-300 px-2 py-1 text-center">1</td>
            <td class="border border-gray-300 px-2 py-1 kolom-komponen">Diatas Ekspektasi (AE)</td>';
        for ($bulan = 1; $bulan <= 12; $bulan++) {
            $html .= '<td class="border border-gray-300 px-2 py-1 text-center">'.$rekap_rating_ae[$bulan].'</td>';
        }
        $html .= '<td class="border border-gray-300 px-2 py-1 text-center">'.$total_ae.'</td></tr>';

        // 5. Sesuai Ekspektasi (SE)
        $html .= '<tr>
            <td class="border border-gray-300 px-2 py-1 text-center">2</td>
            <td class="border border-gray-300 px-2 py-1 kolom-komponen">Sesuai Ekspektasi (SE)</td>';
        for ($bulan = 1; $bulan <= 12; $bulan++) {
            $html .= '<td class="border border-gray-300 px-2 py-1 text-center">'.$rekap_rating_se[$bulan].'</td>';
        }
        $html .= '<td class="border border-gray-300 px-2 py-1 text-center">'.$total_se.'</td></tr>';

        // 6. Dibawah Ekspektasi (BE)
        $html .= '<tr>
            <td class="border border-gray-300 px-2 py-1 text-center">3</td>
            <td class="border border-gray-300 px-2 py-1 kolom-komponen">Dibawah Ekspektasi (BE)</td>';
        for ($bulan = 1; $bulan <= 12; $bulan++) {
            $html .= '<td class="border border-gray-300 px-2 py-1 text-center">'.$rekap_rating_be[$bulan].'</td>';
        }
        $html .= '<td class="border border-gray-300 px-2 py-1 text-center">'.$total_be.'</td></tr>';

        // --- BUKTI AKTIVITAS (Header)
        $html .= '<tr class="row-header"><td colspan="15" class="text-left px-2 py-2 border border-gray-300 bg-gray-50">BUKTI AKTIFITAS</td></tr>';

        // 7. Bukti Aktifitas: Photo / Gambar
        $html .= '<tr>
            <td class="border border-gray-300 px-2 py-1 text-center">1</td>
            <td class="border border-gray-300 px-2 py-1 kolom-komponen">Photo / Gambar / Screenshoot</td>';
        for ($bulan = 1; $bulan <= 12; $bulan++) {
            $html .= '<td class="border border-gray-300 px-2 py-1 text-center">'.$rekap_bukti_gambar[$bulan].'</td>';
        }
        $html .= '<td class="border border-gray-300 px-2 py-1 text-center">'.$total_gambar.'</td></tr>';

        // 8. Bukti Aktifitas: Dokumen
        $html .= '<tr>
            <td class="border border-gray-300 px-2 py-1 text-center">2</td>
            <td class="border border-gray-300 px-2 py-1 kolom-komponen">Dokumen (Pdf)</td>';
        for ($bulan = 1; $bulan <= 12; $bulan++) {
            $html .= '<td class="border border-gray-300 px-2 py-1 text-center">'.$rekap_bukti_dokumen[$bulan].'</td>';
        }
        $html .= '<td class="border border-gray-300 px-2 py-1 text-center">'.$total_dokumen.'</td></tr>';

        // 9. Bukti Aktifitas: Tautan / Link
        $html .= '<tr>
            <td class="border border-gray-300 px-2 py-1 text-center">3</td>
            <td class="border border-gray-300 px-2 py-1 kolom-komponen">Tautan / External Link</td>';
        for ($bulan = 1; $bulan <= 12; $bulan++) {
            $html .= '<td class="border border-gray-300 px-2 py-1 text-center">'.$rekap_bukti_tautan[$bulan].'</td>';
        }
        $html .= '<td class="border border-gray-300 px-2 py-1 text-center">'.$total_tautan.'</td></tr>';

        $html .= '</tbody></table>';
        $html .= '</div>';

        if ($download) {
            $pdf = PDF::loadHTML($html);
            return $pdf->download('laporan_aktifitas_' . $nip . '_' . $tahun . '.pdf');
        } else {
            return response($html, 200)->header('Content-Type', 'text/html');
        }
    }

    

    // --HTML OUTPUT END

}
