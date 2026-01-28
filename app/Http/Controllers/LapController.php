<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\SkpKontrakController;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\RefSkpTipe;
use App\Models\AktifitasKinerja;
use App\Models\RencanaHasilKerjaAtasan;
use App\Models\RencanaHasilKerjaItem;
use App\Models\RefHasilKerja;
use App\Models\PerilakuKerja;
use App\Models\RefPerilakuKerja;
use App\Models\RefPredikat;

use DB;

class LapController extends Controller
{
    public function lembar_skp(Request $request){

        $uid = $request->uid;
        $tipe = $request->tipe ?? 2; // Default to 2 ("EVALUASI KINERJA PEGAWAI") if not provided

        $skp_kontrak = \DB::table('skp_kontrak')->where('uid', $uid)->first();

        // --SKP
        $skp_tipe = RefSkpTipe::where('id', $skp_kontrak->skp_tipe_id)->first();
        $skp_tipe_deskripsi = $skp_tipe->deskripsi;
        $skp_tipe_nama = $skp_tipe->skp_tipe;

        // ---Header
        $institusi = "UNIVERSITAS SULTAN AGENG TIRTAYASA";
        if ($tipe == 1) {
            $judul_1 = "SASARAN KINERJA PEGAWAI";
        } else {
            $judul_1 = "EVALUASI KINERJA PEGAWAI";
        }
        $judul_2 = "PENDEKATAN HASIL KERJA " . $skp_tipe_nama;
        $header = [
            "institusi" => $institusi,
            "judul_1" => $judul_1,
            "judul_2" => $judul_2,
            "skp_tipe" => $skp_tipe_nama,
            "skp_tipe_deskripsi" => $skp_tipe_deskripsi
        ];

        // ---Periode
        $periodde_penilaian = $skp_kontrak->periode_awal . " - " . $skp_kontrak->periode_akhir;
        $tgl_awal = $skp_kontrak->periode_awal;
        $tgl_akhir = $skp_kontrak->periode_akhir;
        function tglIndo($tanggal) {
            $tgl = date('d', strtotime($tanggal));
            $bln = date('F', strtotime($tanggal));
            $thn = date('Y', strtotime($tanggal));
            return $tgl . ' ' . bulanIndo($bln) . ' ' . $thn;
        }
        $periode_penilaian_tgl_indo = tglIndo($skp_kontrak->periode_awal) . " - " . tglIndo($skp_kontrak->periode_akhir);
        $tahun_penilaian = $skp_kontrak->tahun;

        $periode = [
            "tgl_awal" => $tgl_awal,
            "tgl_akhir" => $tgl_akhir,
            "tgl_penilaian" => $periodde_penilaian,
            "tgl_penilaian_indo" => $periode_penilaian_tgl_indo
        ];
        // ---Pegawai
        $pegawai =[
            "nip" => $skp_kontrak->pegawai_nip, 
            "nama" => $skp_kontrak->pegawai_nama,
            "pangkat_golongan" => $skp_kontrak->pegawai_pangkat,
            "jabatan" => $skp_kontrak->pegawai_jabatan,
            "unit_kerja" => $skp_kontrak->pegawai_unit_kerja
        ];
        // ---Penilai
        $penilai =[
            "nip" => $skp_kontrak->penilai_nip, 
            "nama" => $skp_kontrak->penilai_nama,
            "pangkat_golongan" => $skp_kontrak->penilai_pangkat,
            "jabatan" => $skp_kontrak->penilai_jabatan,
            "unit_kerja" => $skp_kontrak->penilai_unit_kerja
        ];

        // --Hasil Kerja
        $kinerja = AktifitasKinerja::select('rhki_id','rhka_id')
            ->with([
                'rencanaHasilKerjaAtasan:id,rubrik_kinerja,kategori',
                'rencanaHasilKerjaItem:id,kegiatan,ukuran_keberhasilan,realisasi'
            ])
            ->where('nip', $skp_kontrak->pegawai_nip)
            ->where('tanggal_mulai', '>=', $skp_kontrak->periode_awal)
            ->where('tanggal_selesai', '<=', $skp_kontrak->periode_akhir)
            ->get();


        // Kelompokkan hasil kerja berdasarkan kategori
        $hasil_kerja_grouped = [
            'utama' => [],
            'tambahan' => []
        ];

        foreach ($kinerja as $item) {
            $kategori = $item->rencanaHasilKerjaAtasan->kategori ?? null;
            if ($kategori === 'utama') {
                $hasil_kerja_grouped['utama'][] = $item;
            } elseif ($kategori === 'tambahan') {
                $hasil_kerja_grouped['tambahan'][] = $item;
            }
        }

        // --Rating hasil kerja
        $rating_hasil_kerja = RefHasilKerja::where('kode', $skp_kontrak->rating_hasil_kerja)->first()->hasil_kerja;

        // --Perilaku kerja
        $perilaku_kerja_raw = PerilakuKerja::with('refPerilakuKerja')
            ->where('uid', $skp_kontrak->uid)
            ->get();
        
        // Ambil semua data ref_perilaku_kerja
        $ref_perilaku_kerja_all = RefPerilakuKerja::all();
        $ref_perilaku_kerja = $ref_perilaku_kerja_all->keyBy('kode');
        
        // Buat mapping ekspektasi_pimpinan berdasarkan kode
        $ekspektasi_map = [];
        foreach ($perilaku_kerja_raw as $item) {
            $ekspektasi_map[$item->perilaku_kerja_kode] = $item->ekspektasi_pimpinan;
        }
        
        // Kelompokkan perilaku kerja berdasarkan kategori utama
        $perilaku_kerja_grouped = [];
        
        // Filter parent (kode integer: 1, 2, 3, dst) dan child (kode decimal: 1.1, 1.2, 1.3, dst)
        foreach ($ref_perilaku_kerja_all as $item) {
            $kode = $item->kode;
            
            // Jika kode adalah integer (1, 2, 3, dst), ini adalah parent
            if (strpos($kode, '.') === false) {
                $perilaku_kerja_grouped[$kode] = [
                    'no' => $kode,
                    'kode' => $kode,
                    'perilaku_kerja' => $item->perilaku_kerja,
                    'ekspektasi_pimpinan' => $ekspektasi_map[$kode] ?? '',
                    'items' => []
                ];
            } else {
                // Jika kode adalah decimal (1.1, 1.2, dst), ini adalah child
                // Ambil parent kode (bagian sebelum titik)
                $parent_kode = explode('.', $kode)[0];
                
                // Tambahkan sebagai child dari parent
                if (isset($perilaku_kerja_grouped[$parent_kode])) {
                    $perilaku_kerja_grouped[$parent_kode]['items'][] = [
                        'kode' => $kode,
                        'perilaku_kerja' => $item->perilaku_kerja,
                    ];
                }
            }
        }
        
        // Convert to indexed array
        $perilaku_kerja = array_values($perilaku_kerja_grouped);

        // --Rating perilaku kerja
        $rating_perilaku_kerja = RefHasilKerja::where('kode', $skp_kontrak->rating_perilaku_kerja)->first()->hasil_kerja;

        // --Predikat kinerja
        $predikat_kinerja = $skp_kontrak->predikat_kinerja;

        // ---Respon
        if($skp_kontrak){
            $result = [
                "success" => true,
                "header" => $header,
                "periode" => $periode,
                "pegawai_dinilai" => $pegawai,
                "pejabat_penilai" => $penilai,
                "hasil_kerja" => $hasil_kerja_grouped,
                "rating_hasil_kerja" => $rating_hasil_kerja,
                "perilaku_kerja" => $perilaku_kerja,
                "rating_perilaku_kerja" => $rating_perilaku_kerja,
                "predikat_kinerja" => $predikat_kinerja,
                // "data" => $skp_kontrak,
            ];
            return response()->json($result);
        } else {
            return response()->json([
                "success" => false,
                "message" => "Data tidak ditemukan"
            ], 404);
        }

    }

    public function lembar_skp_html(Request $request){
        $uid = $request->uid;
        $tipe = $request->tipe ?? 2; // Default tipe = 2
        $skp_kontrak = \DB::table('skp_kontrak')->where('uid', $uid)->first();

        // --SKP
        $skp_tipe = RefSkpTipe::where('id', $skp_kontrak->skp_tipe_id)->first();
        $skp_tipe_deskripsi = $skp_tipe->deskripsi;
        $skp_tipe_nama = $skp_tipe->skp_tipe;
        // ---Header
        $logo = "https://cdn.jsdelivr.net/gh/seiranoku/ukonstyles@main/images/favicon.png";
        $institusi = "UNIVERSITAS SULTAN AGENG TIRTAYASA";
        $judul_1 = ($tipe == 1) ? "SASARAN KINERJA PEGAWAI" : "EVALUASI KINERJA PEGAWAI";
        $judul_2 = "PENDEKATAN HASIL KERJA " . $skp_tipe_nama;
        $header = [
            "logo" => $logo,
            "institusi" => $institusi,
            "judul_1" => $judul_1,
            "judul_2" => $judul_2,
            "skp_tipe" => $skp_tipe_nama,
            "skp_tipe_deskripsi" => $skp_tipe_deskripsi
        ];
        // ---Periode
        $periodde_penilaian = $skp_kontrak->periode_awal . " - " . $skp_kontrak->periode_akhir;
        $tgl_awal = $skp_kontrak->periode_awal;
        $tgl_akhir = $skp_kontrak->periode_akhir;
        function tglIndo($tanggal) {
            $tgl = date('d', strtotime($tanggal));
            $bln = date('F', strtotime($tanggal));
            $thn = date('Y', strtotime($tanggal));
            return $tgl . ' ' . bulanIndo($bln) . ' ' . $thn;
        }
        $periode_penilaian_tgl_indo = tglIndo($skp_kontrak->periode_awal) . " - " . tglIndo($skp_kontrak->periode_akhir);
        $tahun_penilaian = $skp_kontrak->tahun;

        $periode = [
            "tgl_awal" => $tgl_awal,
            "tgl_akhir" => $tgl_akhir,
            "tgl_penilaian" => $periodde_penilaian,
            "tgl_penilaian_indo" => $periode_penilaian_tgl_indo
        ];
        // ---Pegawai
        $pegawai =[
            "nip" => $skp_kontrak->pegawai_nip, 
            "nama" => $skp_kontrak->pegawai_nama,
            "pangkat_golongan" => $skp_kontrak->pegawai_pangkat,
            "jabatan" => $skp_kontrak->pegawai_jabatan,
            "unit_kerja" => $skp_kontrak->pegawai_unit_kerja
        ];
        // ---Penilai
        $penilai =[
            "nip" => $skp_kontrak->penilai_nip, 
            "nama" => $skp_kontrak->penilai_nama,
            "pangkat_golongan" => $skp_kontrak->penilai_pangkat,
            "jabatan" => $skp_kontrak->penilai_jabatan,
            "unit_kerja" => $skp_kontrak->penilai_unit_kerja
        ];

        // --Hasil Kerja
        $kinerja = AktifitasKinerja::select('rhki_id','rhka_id')
            ->with([
                'rencanaHasilKerjaAtasan:id,rubrik_kinerja,kategori',
                'rencanaHasilKerjaItem:id,kegiatan,ukuran_keberhasilan,realisasi'
            ])
            ->where('nip', $skp_kontrak->pegawai_nip)
            ->where('tanggal_mulai', '>=', $skp_kontrak->periode_awal)
            ->where('tanggal_selesai', '<=', $skp_kontrak->periode_akhir)
            ->get();


        // Kelompokkan hasil kerja berdasarkan kategori
        $hasil_kerja_grouped = [
            'utama' => [],
            'tambahan' => []
        ];
        
        foreach ($kinerja as $item) {
            $kategori = $item->rencanaHasilKerjaAtasan->kategori ?? null;
            if ($kategori === 'utama') {
                $hasil_kerja_grouped['utama'][] = $item;
            } elseif ($kategori === 'tambahan') {
                $hasil_kerja_grouped['tambahan'][] = $item;
            }
        }

        // --Rating hasil kerja
        $rating_hasil_kerja = RefHasilKerja::where('kode', $skp_kontrak->rating_hasil_kerja)->first()->hasil_kerja;

        // --Perilaku kerja
        $perilaku_kerja_raw = PerilakuKerja::with('refPerilakuKerja')
            ->where('uid', $skp_kontrak->uid)
            ->get();
        
        // Ambil semua data ref_perilaku_kerja
        $ref_perilaku_kerja_all = RefPerilakuKerja::all();
        $ref_perilaku_kerja = $ref_perilaku_kerja_all->keyBy('kode');
        
        // Buat mapping ekspektasi_pimpinan berdasarkan kode
        $ekspektasi_map = [];
        foreach ($perilaku_kerja_raw as $item) {
            $ekspektasi_map[$item->perilaku_kerja_kode] = $item->ekspektasi_pimpinan;
        }
        
        // Kelompokkan perilaku kerja berdasarkan kategori utama
        $perilaku_kerja_grouped = [];
        
        // Filter parent (kode integer: 1, 2, 3, dst) dan child (kode decimal: 1.1, 1.2, 1.3, dst)
        foreach ($ref_perilaku_kerja_all as $item) {
            $kode = $item->kode;
            
            // Jika kode adalah integer (1, 2, 3, dst), ini adalah parent
            if (strpos($kode, '.') === false) {
                $perilaku_kerja_grouped[$kode] = [
                    'no' => $kode,
                    'kode' => $kode,
                    'perilaku_kerja' => $item->perilaku_kerja,
                    'ekspektasi_pimpinan' => $ekspektasi_map[$kode] ?? '',
                    'items' => []
                ];
            } else {
                // Jika kode adalah decimal (1.1, 1.2, dst), ini adalah child
                // Ambil parent kode (bagian sebelum titik)
                $parent_kode = explode('.', $kode)[0];
                
                // Tambahkan sebagai child dari parent
                if (isset($perilaku_kerja_grouped[$parent_kode])) {
                    $perilaku_kerja_grouped[$parent_kode]['items'][] = [
                        'kode' => $kode,
                        'perilaku_kerja' => $item->perilaku_kerja,
                    ];
                }
            }
        }
        
        // Convert to indexed array
        $perilaku_kerja = array_values($perilaku_kerja_grouped);
        
        // Debug: Log struktur perilaku_kerja
        \Log::info('Perilaku Kerja Structure for HTML (FIXED):', ['count' => count($perilaku_kerja), 'first_item' => isset($perilaku_kerja[0]) ? $perilaku_kerja[0] : null]);

        // --Rating perilaku kerja
        $rating_perilaku_kerja = RefHasilKerja::where('kode', $skp_kontrak->rating_perilaku_kerja)->first()->hasil_kerja;

        // --Predikat kinerja
        $predikat_kinerja = $skp_kontrak->predikat_kinerja;

        // ---Respon HTML
        if($skp_kontrak){
            $html = '
            <div style="font-family: Arial, sans-serif; font-size: 12px; margin: 0; padding: 0;">
                <div style="text-align: center; margin-bottom: 20px; padding: 0;">
                    <img src="' . $header['logo'] . '" alt="Logo Untirta" style="width: 100px; height: auto; margin-bottom: 10px; display: block; margin-left: auto; margin-right: auto;">
                    <div style="margin: 5px 0; font-size: 16px; font-weight: bold;">' . $header['institusi'] . '</div>
                    <div style="margin: 5px 0; font-size: 14px; font-weight: bold;">' . $header['judul_1'] . '</div>
                    <div style="margin: 5px 0; font-size: 14px; font-weight: bold;">' . $header['judul_2'] . '</div>
                </div>

                <table style="width: 100%; border-collapse: collapse; margin-bottom: 20px; border: none;">
                    <tr>
                        <td style="width: 30%; padding: 8px; text-align: left; border: none;"><strong>Periode Penilaian</strong></td>
                        <td style="padding: 8px; text-align: left; border: none;">: ' . $periode['tgl_penilaian_indo'] . '</td>
                    </tr>
                </table>

                <table style="width: 100%; border-collapse: collapse; margin-bottom: 20px; border: 1px solid #000;">
                    <tr>
                        <td colspan="2" style="padding: 8px; text-align: center; font-weight: bold; border: 1px solid #000; background-color: #d0d0d0;">PEGAWAI YANG DINILAI</td>
                    </tr>
                    <tr>
                        <td style="width: 25%; padding: 8px; text-align: left; border: 1px solid #000;"><strong>Nama</strong></td>
                        <td style="padding: 8px; text-align: left; border: 1px solid #000;">' . $pegawai['nama'] . '</td>
                    </tr>
                    <tr>
                        <td style="width: 25%; padding: 8px; text-align: left; border: 1px solid #000;"><strong>NIP</strong></td>
                        <td style="padding: 8px; text-align: left; border: 1px solid #000;">' . $pegawai['nip'] . '</td>
                    </tr>
                    <tr>
                        <td style="width: 25%; padding: 8px; text-align: left; border: 1px solid #000;"><strong>Pangkat/Golongan</strong></td>
                        <td style="padding: 8px; text-align: left; border: 1px solid #000;">' . $pegawai['pangkat_golongan'] . '</td>
                    </tr>
                    <tr>
                        <td style="width: 25%; padding: 8px; text-align: left; border: 1px solid #000;"><strong>Jabatan</strong></td>
                        <td style="padding: 8px; text-align: left; border: 1px solid #000;">' . $pegawai['jabatan'] . '</td>
                    </tr>
                    <tr>
                        <td style="width: 25%; padding: 8px; text-align: left; border: 1px solid #000;"><strong>Unit Kerja</strong></td>
                        <td style="padding: 8px; text-align: left; border: 1px solid #000;">' . $pegawai['unit_kerja'] . '</td>
                    </tr>
                </table>

                <table style="width: 100%; border-collapse: collapse; margin-bottom: 20px; border: 1px solid #000;">
                    <tr>
                        <td colspan="2" style="padding: 8px; text-align: center; font-weight: bold; border: 1px solid #000; background-color: #d0d0d0;">PEJABAT PENILAI</td>
                    </tr>
                    <tr>
                        <td style="width: 25%; padding: 8px; text-align: left; border: 1px solid #000;"><strong>Nama</strong></td>
                        <td style="padding: 8px; text-align: left; border: 1px solid #000;">' . $penilai['nama'] . '</td>
                    </tr>
                    <tr>
                        <td style="width: 25%; padding: 8px; text-align: left; border: 1px solid #000;"><strong>NIP</strong></td>
                        <td style="padding: 8px; text-align: left; border: 1px solid #000;">' . $penilai['nip'] . '</td>
                    </tr>
                    <tr>
                        <td style="width: 25%; padding: 8px; text-align: left; border: 1px solid #000;"><strong>Pangkat/Golongan</strong></td>
                        <td style="padding: 8px; text-align: left; border: 1px solid #000;">' . $penilai['pangkat_golongan'] . '</td>
                    </tr>
                    <tr>
                        <td style="width: 25%; padding: 8px; text-align: left; border: 1px solid #000;"><strong>Jabatan</strong></td>
                        <td style="padding: 8px; text-align: left; border: 1px solid #000;">' . $penilai['jabatan'] . '</td>
                    </tr>
                    <tr>
                        <td style="width: 25%; padding: 8px; text-align: left; border: 1px solid #000;"><strong>Unit Kerja</strong></td>
                        <td style="padding: 8px; text-align: left; border: 1px solid #000;">' . $penilai['unit_kerja'] . '</td>
                    </tr>
                </table>

                <table style="width: 100%; border-collapse: collapse; margin-bottom: 20px; border: 1px solid #000; table-layout: fixed;">
                    <colgroup>
                        <col style="width: 40px;">
                        ' . ($tipe == 1 ? '<col>' : '<col style="width: 30%;"><col style="width: calc(35% - 20px);"><col style="width: calc(35% - 20px);">') . '
                    </colgroup>
                    <tr>
                        <td colspan="' . ($tipe == 1 ? '2' : '4') . '" style="padding: 8px; text-align: center; font-weight: bold; border: 1px solid #000; background-color: #d0d0d0;">HASIL KERJA UTAMA</td>
                    </tr>
                    <tr>';
                    
                    if ($tipe == 1) {
                        $html .= '
                        <td style="padding: 8px; text-align: center; font-weight: bold; border: 1px solid #000; background-color: #e0e0e0;">No</td>
                        <td style="padding: 8px; text-align: center; font-weight: bold; border: 1px solid #000; background-color: #e0e0e0;">Kegiatan</td>';
                    } else {
                        $html .= '
                        <td style="padding: 8px; text-align: center; font-weight: bold; border: 1px solid #000; background-color: #e0e0e0;">No</td>
                        <td style="padding: 8px; text-align: center; font-weight: bold; border: 1px solid #000; background-color: #e0e0e0;">Kegiatan</td>
                        <td style="padding: 8px; text-align: center; font-weight: bold; border: 1px solid #000; background-color: #e0e0e0;">Ukuran Keberhasilan</td>
                        <td style="padding: 8px; text-align: center; font-weight: bold; border: 1px solid #000; background-color: #e0e0e0;">Realisasi</td>';
                    }
                    
                    $html .= '
                    </tr>';
                    
                    $no = 1;
                    foreach ($hasil_kerja_grouped['utama'] as $item) {
                        $kegiatan = $item->rencanaHasilKerjaItem->kegiatan ?? '-';
                        $ukuran = $item->rencanaHasilKerjaItem->ukuran_keberhasilan ?? '-';
                        $realisasi = $item->rencanaHasilKerjaItem->realisasi ?? '-';
                        
                        if ($tipe == 1) {
                            $html .= '
                    <tr>
                        <td style="padding: 8px; text-align: center; vertical-align: middle; border: 1px solid #000;">' . $no++ . '</td>
                        <td style="padding: 8px; text-align: left; vertical-align: middle; border: 1px solid #000;">' . $kegiatan . '</td>
                    </tr>';
                        } else {
                            $html .= '
                    <tr>
                        <td style="padding: 8px; text-align: center; vertical-align: middle; border: 1px solid #000;">' . $no++ . '</td>
                        <td style="padding: 8px; text-align: left; vertical-align: middle; border: 1px solid #000;">' . $kegiatan . '</td>
                        <td style="padding: 8px; text-align: left; vertical-align: middle; border: 1px solid #000;">' . $ukuran . '</td>
                        <td style="padding: 8px; text-align: left; vertical-align: middle; border: 1px solid #000;">' . $realisasi . '</td>
                    </tr>';
                        }
                    }
                    
                    if (empty($hasil_kerja_grouped['utama'])) {
                        $colspan = ($tipe == 1) ? '2' : '4';
                        $html .= '
                    <tr>
                        <td colspan="' . $colspan . '" style="padding: 8px; text-align: center; border: 1px solid #000;">Tidak ada data</td>
                    </tr>';
                    }
                    
                    $html .= '
                </table>

                <table style="width: 100%; border-collapse: collapse; margin-bottom: 20px; border: 1px solid #000; table-layout: fixed;">
                    <colgroup>
                        <col style="width: 40px;">
                        ' . ($tipe == 1 ? '<col>' : '<col style="width: 30%;"><col style="width: calc(35% - 20px);"><col style="width: calc(35% - 20px);">') . '
                    </colgroup>
                    <tr>
                        <td colspan="' . ($tipe == 1 ? '2' : '4') . '" style="padding: 8px; text-align: center; font-weight: bold; border: 1px solid #000; background-color: #d0d0d0;">HASIL KERJA TAMBAHAN</td>
                    </tr>
                    <tr>';
                    
                    if ($tipe == 1) {
                        $html .= '
                        <td style="padding: 8px; text-align: center; font-weight: bold; border: 1px solid #000; background-color: #e0e0e0;">No</td>
                        <td style="padding: 8px; text-align: center; font-weight: bold; border: 1px solid #000; background-color: #e0e0e0;">Kegiatan</td>';
                    } else {
                        $html .= '
                        <td style="padding: 8px; text-align: center; font-weight: bold; border: 1px solid #000; background-color: #e0e0e0;">No</td>
                        <td style="padding: 8px; text-align: center; font-weight: bold; border: 1px solid #000; background-color: #e0e0e0;">Kegiatan</td>
                        <td style="padding: 8px; text-align: center; font-weight: bold; border: 1px solid #000; background-color: #e0e0e0;">Ukuran Keberhasilan</td>
                        <td style="padding: 8px; text-align: center; font-weight: bold; border: 1px solid #000; background-color: #e0e0e0;">Realisasi</td>';
                    }
                    
                    $html .= '
                    </tr>';
                    
                    $no = 1;
                    foreach ($hasil_kerja_grouped['tambahan'] as $item) {
                        $kegiatan = $item->rencanaHasilKerjaItem->kegiatan ?? '-';
                        $ukuran = $item->rencanaHasilKerjaItem->ukuran_keberhasilan ?? '-';
                        $realisasi = $item->rencanaHasilKerjaItem->realisasi ?? '-';
                        
                        if ($tipe == 1) {
                            $html .= '
                    <tr>
                        <td style="padding: 8px; text-align: center; vertical-align: middle; border: 1px solid #000;">' . $no++ . '</td>
                        <td style="padding: 8px; text-align: left; vertical-align: middle; border: 1px solid #000;">' . $kegiatan . '</td>
                    </tr>';
                        } else {
                            $html .= '
                    <tr>
                        <td style="padding: 8px; text-align: center; vertical-align: middle; border: 1px solid #000;">' . $no++ . '</td>
                        <td style="padding: 8px; text-align: left; vertical-align: middle; border: 1px solid #000;">' . $kegiatan . '</td>
                        <td style="padding: 8px; text-align: left; vertical-align: middle; border: 1px solid #000;">' . $ukuran . '</td>
                        <td style="padding: 8px; text-align: left; vertical-align: middle; border: 1px solid #000;">' . $realisasi . '</td>
                    </tr>';
                        }
                    }
                    
                    if (empty($hasil_kerja_grouped['tambahan'])) {
                        $colspan = ($tipe == 1) ? '2' : '4';
                        $html .= '
                    <tr>
                        <td colspan="' . $colspan . '" style="padding: 8px; text-align: center; border: 1px solid #000;">Tidak ada data</td>
                    </tr>';
                    }
                    
                    $html .= '
                </table>

                ';
                
                if ($tipe != 1) {
                    $html .= '
                <table style="width: 100%; border-collapse: collapse; margin-bottom: 20px; border: 1px solid #000;">
                    <tr>
                        <td style="width: 30%; padding: 8px; text-align: left; border: 1px solid #000; background-color: #e0e0e0;"><strong>Rating Hasil Kerja</strong></td>
                        <td style="padding: 8px; text-align: left; border: 1px solid #000; background-color: #e0e0e0;"><strong>' . $rating_hasil_kerja . '</strong></td>
                    </tr>
                </table>';
                }
                
                $html .= '

                <table style="width: 100%; border-collapse: collapse; margin-bottom: 20px; border: 1px solid #000; table-layout: fixed;">
                    <colgroup>
                        <col style="width: 40px;">
                        <col style="width: 45%;">
                        <col style="width: calc(55% - 40px);">
                    </colgroup>
                    <tr>
                        <td colspan="3" style="padding: 8px; text-align: center; font-weight: bold; border: 1px solid #000; background-color: #d0d0d0;">PERILAKU KERJA</td>
                    </tr>
                    <tr>
                        <td style="padding: 8px; text-align: center; font-weight: bold; border: 1px solid #000; background-color: #e0e0e0;">No</td>
                        <td style="padding: 8px; text-align: center; font-weight: bold; border: 1px solid #000; background-color: #e0e0e0;">Perilaku Kerja</td>
                        <td style="padding: 8px; text-align: center; font-weight: bold; border: 1px solid #000; background-color: #e0e0e0;">Ekspektasi Pimpinan</td>
                    </tr>';
                    
                    foreach ($perilaku_kerja as $pk) {
                        // Debug: Log jumlah items
                        $items_count = isset($pk['items']) ? count($pk['items']) : 0;
                        
                        // Hitung jumlah baris untuk rowspan (1 untuk parent + jumlah items)
                        $rowspan = 1 + $items_count;
                        
                        // Baris pertama (parent) dengan rowspan
                        $html .= '<tr>';
                        $html .= '<td style="padding: 8px; text-align: center; border: 1px solid #000;"><strong>' . $pk['no'] . '</strong></td>';
                        $html .= '<td style="padding: 8px; text-align: left; border: 1px solid #000;"><strong>' . $pk['perilaku_kerja'] . '</strong></td>';
                        $html .= '<td rowspan="' . $rowspan . '" style="padding: 8px; text-align: left; vertical-align: top; border: 1px solid #000;">' . $pk['ekspektasi_pimpinan'] . '</td>';
                        $html .= '</tr>';
                        
                        // Tampilkan sub-items
                        if (!empty($pk['items']) && is_array($pk['items'])) {
                            foreach ($pk['items'] as $item) {
                                $html .= '<tr>';
                                $html .= '<td style="padding: 8px; text-align: center; border: 1px solid #000;">' . $item['kode'] . '</td>';
                                $html .= '<td style="padding: 8px; text-align: left; border: 1px solid #000;">' . $item['perilaku_kerja'] . '</td>';
                                $html .= '</tr>';
                            }
                        }
                    }
                    
                    $html .= '
                </table>

                ';
                
                if ($tipe != 1) {
                    $html .= '
                <table style="width: 100%; border-collapse: collapse; margin-bottom: 20px; border: 1px solid #000;">
                    <tr>
                        <td style="width: 30%; padding: 8px; text-align: left; border: 1px solid #000; background-color: #e0e0e0;"><strong>Rating Perilaku Kerja</strong></td>
                        <td style="padding: 8px; text-align: left; border: 1px solid #000; background-color: #e0e0e0;"><strong>' . $rating_perilaku_kerja . '</strong></td>
                    </tr>
                </table>

                <table style="width: 100%; border-collapse: collapse; margin-bottom: 20px; border: 1px solid #000;">
                    <tr>
                        <td style="width: 30%; padding: 8px; text-align: left; border: 1px solid #000; background-color: #e0e0e0;"><strong>Predikat Kinerja</strong></td>
                        <td style="padding: 8px; text-align: left; border: 1px solid #000; background-color: #e0e0e0;"><strong>' . $predikat_kinerja . '</strong></td>
                    </tr>
                </table>';
                }
                
                $html .= '

                <table style="width: 100%; border-collapse: collapse; margin-top: 40px; border: none;">
                    <tr>
                        <td style="width: 50%; padding: 15px; vertical-align: bottom; text-align: center; border: none;">
                            <div style="margin-bottom: 10px;"><strong>PEGAWAI YANG DINILAI</strong></div>
                            <div style="height: 80px;"></div>
                            <div style="border-bottom: 1px solid #000; display: inline-block; min-width: 250px; padding-bottom: 2px; margin-bottom: 5px;">
                                <strong>' . $pegawai['nama'] . '</strong>
                            </div>
                            <div>NIP. ' . $pegawai['nip'] . '</div>
                        </td>
                        <td style="width: 50%; padding: 15px; vertical-align: bottom; text-align: center; border: none;">
                            <div style="margin-bottom: 10px;"><strong>PEJABAT PENILAI</strong></div>
                            <div style="height: 80px;"></div>
                            <div style="border-bottom: 1px solid #000; display: inline-block; min-width: 250px; padding-bottom: 2px; margin-bottom: 5px;">
                                <strong>' . $penilai['nama'] . '</strong>
                            </div>
                            <div>NIP. ' . $penilai['nip'] . '</div>
                        </td>
                    </tr>
                </table>

                <table style="width: 100%; border-collapse: collapse; margin-top: 20px; border: 1px solid #000;">
                    <tr>
                        <td style="width: 70%; padding: 15px; vertical-align: middle; text-align: left; border: 1px solid #000;">
                            <div><strong>Dicetak pada:</strong> ' . date('d F Y, H:i:s') . '</div>
                        </td>
                        <td style="width: 30%; padding: 10px; vertical-align: middle; text-align: center; border: 1px solid #000;">
                            <img src="https://api.qrserver.com/v1/create-qr-code/?size=120x120&data=' . urlencode($uid) . '" alt="QR Code" style="width: 120px; height: 120px; display: block; margin: 0 auto;">
                        </td>
                    </tr>
                </table>

            </div>';
            
            return response($html)->header('Content-Type', 'text/html');
        } else {
            return response()->json([
                "success" => false,
                "message" => "Data tidak ditemukan"
            ], 404);
        }
    }

    public function lembar_skp_pdf(Request $request){
        $uid = $request->uid;
        $download = $request->input('download', 'true'); // Default: true (download)
        $isDownload = filter_var($download, FILTER_VALIDATE_BOOLEAN);
        $tipe = $request->input('tipe', '0'); // Default: 0 (evaluasi kinerja)
        
        $skp_kontrak = \DB::table('skp_kontrak')->where('uid', $uid)->first();

        if(!$skp_kontrak){
            return response()->json([
                "success" => false,
                "message" => "Data tidak ditemukan"
            ], 404);
        }

        // --SKP
        $skp_tipe = RefSkpTipe::where('id', $skp_kontrak->skp_tipe_id)->first();
        $skp_tipe_deskripsi = $skp_tipe->deskripsi;
        $skp_tipe_nama = $skp_tipe->skp_tipe;
        
        // ---Header
        // Logo menggunakan URL eksternal dari CDN
        $logo_url = 'https://cdn.jsdelivr.net/gh/seiranoku/ukonstyles@main/images/favicon.png';
        $logo_html = '<img src="' . $logo_url . '" alt="Logo Untirta" style="width: 80px; height: 80px; display: block; margin: 0 auto 10px;">';
        
        $institusi = "UNIVERSITAS SULTAN AGENG TIRTAYASA";
        $judul_1 = ($tipe == '1') ? "SASARAN KINERJA PEGAWAI" : "EVALUASI KINERJA PEGAWAI";
        $judul_2 = "PENDEKATAN HASIL KERJA " . $skp_tipe_nama;
        $header = [
            "logo_html" => $logo_html,
            "institusi" => $institusi,
            "judul_1" => $judul_1,
            "judul_2" => $judul_2,
            "skp_tipe" => $skp_tipe_nama,
            "skp_tipe_deskripsi" => $skp_tipe_deskripsi
        ];
        
        // ---Periode
        $periodde_penilaian = $skp_kontrak->periode_awal . " - " . $skp_kontrak->periode_akhir;
        $tgl_awal = $skp_kontrak->periode_awal;
        $tgl_akhir = $skp_kontrak->periode_akhir;
        function tglIndo($tanggal) {
            $tgl = date('d', strtotime($tanggal));
            $bln = date('F', strtotime($tanggal));
            $thn = date('Y', strtotime($tanggal));
            return $tgl . ' ' . bulanIndo($bln) . ' ' . $thn;
        }
        $periode_penilaian_tgl_indo = tglIndo($skp_kontrak->periode_awal) . " - " . tglIndo($skp_kontrak->periode_akhir);
        $tahun_penilaian = $skp_kontrak->tahun;

        $periode = [
            "tgl_awal" => $tgl_awal,
            "tgl_akhir" => $tgl_akhir,
            "tgl_penilaian" => $periodde_penilaian,
            "tgl_penilaian_indo" => $periode_penilaian_tgl_indo
        ];
        
        // ---Pegawai
        $pegawai =[
            "nip" => $skp_kontrak->pegawai_nip, 
            "nama" => $skp_kontrak->pegawai_nama,
            "pangkat_golongan" => $skp_kontrak->pegawai_pangkat,
            "jabatan" => $skp_kontrak->pegawai_jabatan,
            "unit_kerja" => $skp_kontrak->pegawai_unit_kerja
        ];
        
        // ---Penilai
        $penilai =[
            "nip" => $skp_kontrak->penilai_nip, 
            "nama" => $skp_kontrak->penilai_nama,
            "pangkat_golongan" => $skp_kontrak->penilai_pangkat,
            "jabatan" => $skp_kontrak->penilai_jabatan,
            "unit_kerja" => $skp_kontrak->penilai_unit_kerja
        ];

        // --Hasil Kerja
        $kinerja = AktifitasKinerja::select('rhki_id','rhka_id')
            ->with([
                'rencanaHasilKerjaAtasan:id,rubrik_kinerja,kategori',
                'rencanaHasilKerjaItem:id,kegiatan,ukuran_keberhasilan,realisasi'
            ])
            ->where('nip', $skp_kontrak->pegawai_nip)
            ->where('tanggal_mulai', '>=', $skp_kontrak->periode_awal)
            ->where('tanggal_selesai', '<=', $skp_kontrak->periode_akhir)
            ->get();

        // Kelompokkan hasil kerja berdasarkan kategori
        $hasil_kerja_grouped = [
            'utama' => [],
            'tambahan' => []
        ];
        
        foreach ($kinerja as $item) {
            $kategori = $item->rencanaHasilKerjaAtasan->kategori ?? null;
            if ($kategori === 'utama') {
                $hasil_kerja_grouped['utama'][] = $item;
            } elseif ($kategori === 'tambahan') {
                $hasil_kerja_grouped['tambahan'][] = $item;
            }
        }

        // --Rating hasil kerja
        $rating_hasil_kerja = RefHasilKerja::where('kode', $skp_kontrak->rating_hasil_kerja)->first()->hasil_kerja;

        // --Perilaku kerja
        $perilaku_kerja_raw = PerilakuKerja::with('refPerilakuKerja')
            ->where('uid', $skp_kontrak->uid)
            ->get();
        
        // Ambil semua data ref_perilaku_kerja
        $ref_perilaku_kerja_all = RefPerilakuKerja::all();
        $ref_perilaku_kerja = $ref_perilaku_kerja_all->keyBy('kode');
        
        // Buat mapping ekspektasi_pimpinan berdasarkan kode
        $ekspektasi_map = [];
        foreach ($perilaku_kerja_raw as $item) {
            $ekspektasi_map[$item->perilaku_kerja_kode] = $item->ekspektasi_pimpinan;
        }
        
        // Kelompokkan perilaku kerja berdasarkan kategori utama
        $perilaku_kerja_grouped = [];
        
        // Filter parent (kode integer: 1, 2, 3, dst) dan child (kode decimal: 1.1, 1.2, 1.3, dst)
        foreach ($ref_perilaku_kerja_all as $item) {
            $kode = $item->kode;
            
            // Jika kode adalah integer (1, 2, 3, dst), ini adalah parent
            if (strpos($kode, '.') === false) {
                $perilaku_kerja_grouped[$kode] = [
                    'no' => $kode,
                    'kode' => $kode,
                    'perilaku_kerja' => $item->perilaku_kerja,
                    'ekspektasi_pimpinan' => $ekspektasi_map[$kode] ?? '',
                    'items' => []
                ];
            } else {
                // Jika kode adalah decimal (1.1, 1.2, dst), ini adalah child
                // Ambil parent kode (bagian sebelum titik)
                $parent_kode = explode('.', $kode)[0];
                
                // Tambahkan sebagai child dari parent
                if (isset($perilaku_kerja_grouped[$parent_kode])) {
                    $perilaku_kerja_grouped[$parent_kode]['items'][] = [
                        'kode' => $kode,
                        'perilaku_kerja' => $item->perilaku_kerja,
                    ];
                }
            }
        }
        
        // Convert to indexed array
        $perilaku_kerja = array_values($perilaku_kerja_grouped);

        // --Rating perilaku kerja
        $rating_perilaku_kerja = RefHasilKerja::where('kode', $skp_kontrak->rating_perilaku_kerja)->first()->hasil_kerja;

        // --Predikat kinerja
        $predikat_kinerja = $skp_kontrak->predikat_kinerja;

        // ---Generate HTML untuk PDF
        $html = '
        <div style="font-family: Arial, sans-serif; margin: 10px; font-size: 10px; line-height: 1.3;">
            <div style="text-align: center; margin-bottom: 5px;">
                ' . $header['logo_html'] . '
                <div style="margin: 2px 0; font-size: 13px; line-height: 1.2; font-weight: bold;">' . $header['institusi'] . '</div>
                <div style="margin: 1px 0; font-size: 11px; line-height: 1.2; font-weight: bold;">' . $header['judul_1'] . '</div>
                <div style="margin: 1px 0; font-size: 11px; line-height: 1.2; font-weight: bold;">' . $header['judul_2'] . '</div>
            </div>

            <table style="width: 100%; border-collapse: collapse; margin-bottom: 4px;">
                <tr>
                    <td style="width: 30%; padding: 2px; text-align: left; border: none;"><strong>Periode Penilaian</strong></td>
                    <td style="padding: 2px; text-align: left; border: none;">: ' . $periode['tgl_penilaian_indo'] . '</td>
                </tr>
            </table>

            <table style="width: 100%; border-collapse: collapse; margin-bottom: 4px; border: 1px solid #000;">
                <tr>
                    <td colspan="2" style="padding: 4px; text-align: center; font-weight: bold; border: 1px solid #000; background-color: #d0d0d0;">PEGAWAI YANG DINILAI</td>
                </tr>
                <tr>
                    <td style="width: 25%; padding: 3px 4px; text-align: left; line-height: 1.3; border: 1px solid #000;"><strong>Nama</strong></td>
                    <td style="padding: 3px 4px; text-align: left; line-height: 1.3; border: 1px solid #000;">' . $pegawai['nama'] . '</td>
                </tr>
                <tr>
                    <td style="width: 25%; padding: 3px 4px; text-align: left; line-height: 1.3; border: 1px solid #000;"><strong>NIP</strong></td>
                    <td style="padding: 3px 4px; text-align: left; line-height: 1.3; border: 1px solid #000;">' . $pegawai['nip'] . '</td>
                </tr>
                <tr>
                    <td style="width: 25%; padding: 3px 4px; text-align: left; line-height: 1.3; border: 1px solid #000;"><strong>Pangkat/Golongan</strong></td>
                    <td style="padding: 3px 4px; text-align: left; line-height: 1.3; border: 1px solid #000;">' . $pegawai['pangkat_golongan'] . '</td>
                </tr>
                <tr>
                    <td style="width: 25%; padding: 3px 4px; text-align: left; line-height: 1.3; border: 1px solid #000;"><strong>Jabatan</strong></td>
                    <td style="padding: 3px 4px; text-align: left; line-height: 1.3; border: 1px solid #000;">' . $pegawai['jabatan'] . '</td>
                </tr>
                <tr>
                    <td style="width: 25%; padding: 3px 4px; text-align: left; line-height: 1.3; border: 1px solid #000;"><strong>Unit Kerja</strong></td>
                    <td style="padding: 3px 4px; text-align: left; line-height: 1.3; border: 1px solid #000;">' . $pegawai['unit_kerja'] . '</td>
                </tr>
            </table>

            <table style="width: 100%; border-collapse: collapse; margin-bottom: 4px; border: 1px solid #000;">
                <tr>
                    <td colspan="2" style="padding: 4px; text-align: center; font-weight: bold; border: 1px solid #000; background-color: #d0d0d0;">PEJABAT PENILAI</td>
                </tr>
                <tr>
                    <td style="width: 25%; padding: 3px 4px; text-align: left; line-height: 1.3; border: 1px solid #000;"><strong>Nama</strong></td>
                    <td style="padding: 3px 4px; text-align: left; line-height: 1.3; border: 1px solid #000;">' . $penilai['nama'] . '</td>
                </tr>
                <tr>
                    <td style="width: 25%; padding: 3px 4px; text-align: left; line-height: 1.3; border: 1px solid #000;"><strong>NIP</strong></td>
                    <td style="padding: 3px 4px; text-align: left; line-height: 1.3; border: 1px solid #000;">' . $penilai['nip'] . '</td>
                </tr>
                <tr>
                    <td style="width: 25%; padding: 3px 4px; text-align: left; line-height: 1.3; border: 1px solid #000;"><strong>Pangkat/Golongan</strong></td>
                    <td style="padding: 3px 4px; text-align: left; line-height: 1.3; border: 1px solid #000;">' . $penilai['pangkat_golongan'] . '</td>
                </tr>
                <tr>
                    <td style="width: 25%; padding: 3px 4px; text-align: left; line-height: 1.3; border: 1px solid #000;"><strong>Jabatan</strong></td>
                    <td style="padding: 3px 4px; text-align: left; line-height: 1.3; border: 1px solid #000;">' . $penilai['jabatan'] . '</td>
                </tr>
                <tr>
                    <td style="width: 25%; padding: 3px 4px; text-align: left; line-height: 1.3; border: 1px solid #000;"><strong>Unit Kerja</strong></td>
                    <td style="padding: 3px 4px; text-align: left; line-height: 1.3; border: 1px solid #000;">' . $penilai['unit_kerja'] . '</td>
                </tr>
            </table>

            <table style="width: 100%; border-collapse: collapse; margin-bottom: 4px; border: 1px solid #000;">
                <tr>
                    <td colspan="' . ($tipe == '1' ? '2' : '4') . '" style="padding: 4px; text-align: center; font-weight: bold; border: 1px solid #000; background-color: #d0d0d0;">HASIL KERJA UTAMA</td>
                </tr>
                <tr>
                    <td style="width: 5%; padding: 3px 4px; text-align: center; line-height: 1.3; border: 1px solid #000; background-color: #e0e0e0; font-weight: bold;">No</td>
                    <td style="width: ' . ($tipe == '1' ? '95%' : '35%') . '; padding: 3px 4px; text-align: center; line-height: 1.3; border: 1px solid #000; background-color: #e0e0e0; font-weight: bold;">Kegiatan</td>';
                    
                if ($tipe != '1') {
                    $html .= '
                    <td style="width: 30%; padding: 3px 4px; text-align: center; line-height: 1.3; border: 1px solid #000; background-color: #e0e0e0; font-weight: bold;">Ukuran Keberhasilan</td>
                    <td style="width: 30%; padding: 3px 4px; text-align: center; line-height: 1.3; border: 1px solid #000; background-color: #e0e0e0; font-weight: bold;">Realisasi</td>';
                }
                
                $html .= '
                </tr>';
                
                $no = 1;
                foreach ($hasil_kerja_grouped['utama'] as $item) {
                    $kegiatan = $item->rencanaHasilKerjaItem->kegiatan ?? '-';
                    $ukuran = $item->rencanaHasilKerjaItem->ukuran_keberhasilan ?? '-';
                    $realisasi = $item->rencanaHasilKerjaItem->realisasi ?? '-';
                    
                    $html .= '
                <tr>
                    <td style="padding: 3px 4px; text-align: center; vertical-align: middle; line-height: 1.3; border: 1px solid #000;">' . $no++ . '</td>
                    <td style="padding: 3px 4px; text-align: left; vertical-align: middle; line-height: 1.3; border: 1px solid #000;">' . $kegiatan . '</td>';
                    
                    if ($tipe != '1') {
                        $html .= '
                    <td style="padding: 3px 4px; text-align: left; vertical-align: middle; line-height: 1.3; border: 1px solid #000;">' . $ukuran . '</td>
                    <td style="padding: 3px 4px; text-align: left; vertical-align: middle; line-height: 1.3; border: 1px solid #000;">' . $realisasi . '</td>';
                    }
                    
                    $html .= '
                </tr>';
                }
                
                if (empty($hasil_kerja_grouped['utama'])) {
                    $colspan = ($tipe == '1') ? '2' : '4';
                    $html .= '
                <tr>
                    <td colspan="' . $colspan . '" style="padding: 3px 4px; text-align: center; line-height: 1.3; border: 1px solid #000;">Tidak ada data</td>
                </tr>';
                }
                
                $html .= '
            </table>

            <table style="width: 100%; border-collapse: collapse; margin-bottom: 4px; border: 1px solid #000;">
                <tr>
                    <td colspan="' . ($tipe == '1' ? '2' : '4') . '" style="padding: 4px; text-align: center; font-weight: bold; border: 1px solid #000; background-color: #d0d0d0;">HASIL KERJA TAMBAHAN</td>
                </tr>
                <tr>
                    <td style="width: 5%; padding: 3px 4px; text-align: center; line-height: 1.3; border: 1px solid #000; background-color: #e0e0e0; font-weight: bold;">No</td>
                    <td style="width: ' . ($tipe == '1' ? '95%' : '35%') . '; padding: 3px 4px; text-align: center; line-height: 1.3; border: 1px solid #000; background-color: #e0e0e0; font-weight: bold;">Kegiatan</td>';
                    
                if ($tipe != '1') {
                    $html .= '
                    <td style="width: 30%; padding: 3px 4px; text-align: center; line-height: 1.3; border: 1px solid #000; background-color: #e0e0e0; font-weight: bold;">Ukuran Keberhasilan</td>
                    <td style="width: 30%; padding: 3px 4px; text-align: center; line-height: 1.3; border: 1px solid #000; background-color: #e0e0e0; font-weight: bold;">Realisasi</td>';
                }
                
                $html .= '
                </tr>';
                
                $no = 1;
                foreach ($hasil_kerja_grouped['tambahan'] as $item) {
                    $kegiatan = $item->rencanaHasilKerjaItem->kegiatan ?? '-';
                    $ukuran = $item->rencanaHasilKerjaItem->ukuran_keberhasilan ?? '-';
                    $realisasi = $item->rencanaHasilKerjaItem->realisasi ?? '-';
                    
                    $html .= '
                <tr>
                    <td style="padding: 3px 4px; text-align: center; vertical-align: middle; line-height: 1.3; border: 1px solid #000;">' . $no++ . '</td>
                    <td style="padding: 3px 4px; text-align: left; vertical-align: middle; line-height: 1.3; border: 1px solid #000;">' . $kegiatan . '</td>';
                    
                    if ($tipe != '1') {
                        $html .= '
                    <td style="padding: 3px 4px; text-align: left; vertical-align: middle; line-height: 1.3; border: 1px solid #000;">' . $ukuran . '</td>
                    <td style="padding: 3px 4px; text-align: left; vertical-align: middle; line-height: 1.3; border: 1px solid #000;">' . $realisasi . '</td>';
                    }
                    
                    $html .= '
                </tr>';
                }
                
                if (empty($hasil_kerja_grouped['tambahan'])) {
                    $colspan = ($tipe == '1') ? '2' : '4';
                    $html .= '
                <tr>
                    <td colspan="' . $colspan . '" style="padding: 3px 4px; text-align: center; line-height: 1.3; border: 1px solid #000;">Tidak ada data</td>
                </tr>';
                }
                
                $html .= '
            </table>

            ';
            
            // Rating Hasil Kerja hanya ditampilkan jika tipe != 1
            if ($tipe != '1') {
                $html .= '
            <table style="width: 100%; border-collapse: collapse; margin-bottom: 4px; border: 1px solid #000;">
                <tr>
                    <td style="width: 30%; padding: 3px 4px; text-align: left; line-height: 1.3; border: 1px solid #000; background-color: #e0e0e0;"><strong>Rating Hasil Kerja</strong></td>
                    <td style="padding: 3px 4px; text-align: left; line-height: 1.3; border: 1px solid #000; background-color: #e0e0e0;"><strong>' . $rating_hasil_kerja . '</strong></td>
                </tr>
            </table>';
            }
            
            $html .= '

            <table style="width: 100%; border-collapse: collapse; margin-bottom: 4px; border: 1px solid #000; table-layout: fixed;">
                <colgroup>
                    <col style="width: 35px;">
                    <col style="width: 45%;">
                    <col style="width: calc(55% - 35px);">
                </colgroup>
                <tr>
                    <td colspan="3" style="padding: 4px; text-align: center; font-weight: bold; border: 1px solid #000; background-color: #d0d0d0;">PERILAKU KERJA</td>
                </tr>
                <tr>
                    <td style="padding: 3px 4px; text-align: center; line-height: 1.3; border: 1px solid #000; background-color: #e0e0e0; font-weight: bold;">No</td>
                    <td style="padding: 3px 4px; text-align: center; line-height: 1.3; border: 1px solid #000; background-color: #e0e0e0; font-weight: bold;">Perilaku Kerja</td>
                    <td style="padding: 3px 4px; text-align: center; line-height: 1.3; border: 1px solid #000; background-color: #e0e0e0; font-weight: bold;">Ekspektasi Pimpinan</td>
                </tr>';
                
                foreach ($perilaku_kerja as $pk) {
                    // Hitung jumlah baris untuk rowspan (1 untuk parent + jumlah items)
                    $rowspan = 1 + count($pk['items']);
                    
                    // Baris pertama (parent) dengan rowspan
                    $html .= '<tr>';
                    $html .= '<td style="padding: 3px 4px; text-align: center; line-height: 1.3; border: 1px solid #000;"><strong>' . $pk['no'] . '</strong></td>';
                    $html .= '<td style="padding: 3px 4px; text-align: left; line-height: 1.3; border: 1px solid #000;"><strong>' . $pk['perilaku_kerja'] . '</strong></td>';
                    $html .= '<td rowspan="' . $rowspan . '" style="padding: 3px 4px; text-align: left; line-height: 1.3; border: 1px solid #000; vertical-align: top;">' . $pk['ekspektasi_pimpinan'] . '</td>';
                    $html .= '</tr>';
                    
                    // Tampilkan sub-items
                    if (!empty($pk['items'])) {
                        foreach ($pk['items'] as $item) {
                            $html .= '<tr>';
                            $html .= '<td style="padding: 3px 4px; text-align: center; line-height: 1.3; border: 1px solid #000;">' . $item['kode'] . '</td>';
                            $html .= '<td style="padding: 3px 4px; text-align: left; line-height: 1.3; border: 1px solid #000;">' . $item['perilaku_kerja'] . '</td>';
                            $html .= '</tr>';
                        }
                    }
                }
                
                $html .= '
            </table>';
            
            // Rating Perilaku Kerja dan Predikat Kinerja hanya ditampilkan jika tipe != 1
            if ($tipe != '1') {
                $html .= '

            <table style="width: 100%; border-collapse: collapse; margin-bottom: 4px; border: 1px solid #000;">
                <tr>
                    <td style="width: 30%; padding: 3px 4px; text-align: left; line-height: 1.3; border: 1px solid #000; background-color: #e0e0e0;"><strong>Rating Perilaku Kerja</strong></td>
                    <td style="padding: 3px 4px; text-align: left; line-height: 1.3; border: 1px solid #000; background-color: #e0e0e0;"><strong>' . $rating_perilaku_kerja . '</strong></td>
                </tr>
            </table>

            <table style="width: 100%; border-collapse: collapse; margin-bottom: 4px; border: 1px solid #000;">
                <tr>
                    <td style="width: 30%; padding: 3px 4px; text-align: left; line-height: 1.3; border: 1px solid #000; background-color: #e0e0e0;"><strong>Predikat Kinerja</strong></td>
                    <td style="padding: 3px 4px; text-align: left; line-height: 1.3; border: 1px solid #000; background-color: #e0e0e0;"><strong>' . $predikat_kinerja . '</strong></td>
                </tr>
            </table>';
            }
            
            $html .= '

            <table style="width: 100%; border-collapse: collapse; margin-top: 8px; margin-bottom: 4px; border: 1px solid #000;">
                <tr>
                    <td style="width: 50%; vertical-align: top; padding: 5px; border: 1px solid #000;">
                        <div style="text-align: center; font-size: 9px;">
                            <strong>PEGAWAI YANG DINILAI</strong>
                            <br><br><br>
                            <strong>' . $pegawai['nama'] . '</strong><br>
                            NIP. ' . $pegawai['nip'] . '
                        </div>
                    </td>
                    <td style="width: 50%; vertical-align: top; padding: 5px; border: 1px solid #000;">
                        <div style="text-align: center; font-size: 9px;">
                            <strong>PEJABAT PENILAI</strong>
                            <br><br><br>
                            <strong>' . $penilai['nama'] . '</strong><br>
                            NIP. ' . $penilai['nip'] . '
                        </div>
                    </td>
                </tr>
            </table>

            <table style="width: 100%; border-collapse: collapse; margin-bottom: 0; border: 1px solid #000;">
                <tr>
                    <td style="padding: 3px; font-size: 9px; border: 1px solid #000;">
                        <strong>Dicetak pada:</strong> ' . date('d F Y, H:i:s') . '<br>
                        <strong>Kode Dokumen:</strong> ' . $uid . '
                    </td>
                </tr>
            </table>

        </div>';
        
        // Generate PDF
        $pdf = Pdf::loadHTML($html);
        $pdf->setPaper('A4', 'portrait');
        
        // Set options untuk DomPDF
        $pdf->setOption('isRemoteEnabled', true);
        $pdf->setOption('isHtml5ParserEnabled', true);
        $pdf->setOption('chroot', public_path());
        
        // Nama file PDF
        $filename = 'Lembar_SKP_' . $pegawai['nip'] . '_' . $tahun_penilaian . '.pdf';
        
        // Return PDF berdasarkan parameter download
        if ($isDownload) {
            // Download PDF
            return $pdf->download($filename);
        } else {
            // Stream/tampilkan PDF di browser
            return $pdf->stream($filename);
        }
    }

}
