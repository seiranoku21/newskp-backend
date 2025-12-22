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
        
        // Ambil semua parent (yang tidak memiliki parent)
        $parents = $ref_perilaku_kerja_all->whereNull('parent');
        
        foreach ($parents as $parent) {
            $parent_kode = $parent->kode;
            
            // Buat struktur parent
            $perilaku_kerja_grouped[$parent_kode] = [
                'no' => $parent_kode,
                'kode' => $parent_kode,
                'perilaku_kerja' => $parent->perilaku_kerja,
                'ekspektasi_pimpinan' => $ekspektasi_map[$parent_kode] ?? '',
                'items' => []
            ];
            
            // Ambil semua children dari parent ini
            $children = $ref_perilaku_kerja_all->where('parent', $parent_kode);
            
            foreach ($children as $child) {
                $perilaku_kerja_grouped[$parent_kode]['items'][] = [
                    'kode' => $child->kode,
                    'perilaku_kerja' => $child->perilaku_kerja,
                ];
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
        $logo = "https://skpv2.untirta.ac.id/images/favicon.png";
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
        
        // Ambil semua parent (yang tidak memiliki parent)
        $parents = $ref_perilaku_kerja_all->whereNull('parent');
        
        foreach ($parents as $parent) {
            $parent_kode = $parent->kode;
            
            // Buat struktur parent
            $perilaku_kerja_grouped[$parent_kode] = [
                'no' => $parent_kode,
                'kode' => $parent_kode,
                'perilaku_kerja' => $parent->perilaku_kerja,
                'ekspektasi_pimpinan' => $ekspektasi_map[$parent_kode] ?? '',
                'items' => []
            ];
            
            // Ambil semua children dari parent ini
            $children = $ref_perilaku_kerja_all->where('parent', $parent_kode);
            
            foreach ($children as $child) {
                $perilaku_kerja_grouped[$parent_kode]['items'][] = [
                    'kode' => $child->kode,
                    'perilaku_kerja' => $child->perilaku_kerja,
                ];
            }
        }
        
        // Convert to indexed array
        $perilaku_kerja = array_values($perilaku_kerja_grouped);

        // --Rating perilaku kerja
        $rating_perilaku_kerja = RefHasilKerja::where('kode', $skp_kontrak->rating_perilaku_kerja)->first()->hasil_kerja;

        // --Predikat kinerja
        $predikat_kinerja = $skp_kontrak->predikat_kinerja;

        // ---Respon HTML
        if($skp_kontrak){
            $html = '
            <!DOCTYPE html>
            <html lang="id">
            <head>
                <meta charset="UTF-8">
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <title>Lembar SKP - ' . $pegawai['nama'] . '</title>
                <style>
                    body {
                        font-family: Arial, sans-serif;
                        margin: 20px;
                        font-size: 12px;
                    }
                    .header {
                        text-align: center;
                        margin-bottom: 20px;
                    }
                    .header h2 {
                        margin: 5px 0;
                    }
                    table {
                        width: 100%;
                        border-collapse: collapse;
                        margin-bottom: 20px;
                    }
                    table, th, td {
                        border: 1px solid #000;
                    }
                    th, td {
                        padding: 8px;
                        text-align: left;
                    }
                    th {
                        background-color: #f0f0f0;
                        font-weight: bold;
                    }
                    .text-center {
                        text-align: center;
                    }
                    .text-right {
                        text-align: right;
                    }
                    .bg-gray {
                        background-color: #e0e0e0;
                    }
                    .section-title {
                        background-color: #d0d0d0;
                        font-weight: bold;
                        text-align: center;
                    }
                    .no-border {
                        border: none;
                    }
                    .info-table td {
                        border: none;
                    }
                </style>
            </head>
            <body>
                <div class="header">
                    <img src="' . $header['logo'] . '" alt="Logo Untirta" style="width: 100px; height: auto; margin-bottom: 10px;">
                    <h2>' . $header['institusi'] . '</h2>
                    <h3>' . $header['judul_1'] . '</h3>
                    <h3>' . $header['judul_2'] . '</h3>
                </div>

                <!-- Informasi Periode -->
                <table class="info-table">
                    <tr>
                        <td width="30%"><strong>Periode Penilaian</strong></td>
                        <td>: ' . $periode['tgl_penilaian_indo'] . '</td>
                    </tr>
                </table>

                <!-- Informasi Pegawai yang Dinilai -->
                <table>
                    <tr class="section-title">
                        <td colspan="2">PEGAWAI YANG DINILAI</td>
                    </tr>
                    <tr>
                        <td width="30%"><strong>Nama</strong></td>
                        <td>' . $pegawai['nama'] . '</td>
                    </tr>
                    <tr>
                        <td><strong>NIP</strong></td>
                        <td>' . $pegawai['nip'] . '</td>
                    </tr>
                    <tr>
                        <td><strong>Pangkat/Golongan</strong></td>
                        <td>' . $pegawai['pangkat_golongan'] . '</td>
                    </tr>
                    <tr>
                        <td><strong>Jabatan</strong></td>
                        <td>' . $pegawai['jabatan'] . '</td>
                    </tr>
                    <tr>
                        <td><strong>Unit Kerja</strong></td>
                        <td>' . $pegawai['unit_kerja'] . '</td>
                    </tr>
                </table>

                <!-- Informasi Pejabat Penilai -->
                <table>
                    <tr class="section-title">
                        <td colspan="2">PEJABAT PENILAI</td>
                    </tr>
                    <tr>
                        <td width="30%"><strong>Nama</strong></td>
                        <td>' . $penilai['nama'] . '</td>
                    </tr>
                    <tr>
                        <td><strong>NIP</strong></td>
                        <td>' . $penilai['nip'] . '</td>
                    </tr>
                    <tr>
                        <td><strong>Pangkat/Golongan</strong></td>
                        <td>' . $penilai['pangkat_golongan'] . '</td>
                    </tr>
                    <tr>
                        <td><strong>Jabatan</strong></td>
                        <td>' . $penilai['jabatan'] . '</td>
                    </tr>
                    <tr>
                        <td><strong>Unit Kerja</strong></td>
                        <td>' . $penilai['unit_kerja'] . '</td>
                    </tr>
                </table>

                <!-- Hasil Kerja Utama -->
                <table>
                    <tr class="section-title">
                        <td colspan="' . ($tipe == 1 ? '2' : '4') . '">HASIL KERJA UTAMA</td>
                    </tr>
                    <tr class="bg-gray text-center">';
                    
                    if ($tipe == 1) {
                        $html .= '
                        <th width="5%">No</th>
                        <th width="95%">Kegiatan</th>';
                    } else {
                        $html .= '
                        <th width="5%">No</th>
                        <th width="35%">Kegiatan</th>
                        <th width="30%">Ukuran Keberhasilan</th>
                        <th width="30%">Realisasi</th>';
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
                        <td class="text-center">' . $no++ . '</td>
                        <td>' . $kegiatan . '</td>
                    </tr>';
                        } else {
                            $html .= '
                    <tr>
                        <td class="text-center">' . $no++ . '</td>
                        <td>' . $kegiatan . '</td>
                        <td>' . $ukuran . '</td>
                        <td>' . $realisasi . '</td>
                    </tr>';
                        }
                    }
                    
                    if (empty($hasil_kerja_grouped['utama'])) {
                        $colspan = ($tipe == 1) ? '2' : '4';
                        $html .= '
                    <tr>
                        <td colspan="' . $colspan . '" class="text-center">Tidak ada data</td>
                    </tr>';
                    }
                    
                    $html .= '
                </table>

                <!-- Hasil Kerja Tambahan -->
                <table>
                    <tr class="section-title">
                        <td colspan="' . ($tipe == 1 ? '2' : '4') . '">HASIL KERJA TAMBAHAN</td>
                    </tr>
                    <tr class="bg-gray text-center">';
                    
                    if ($tipe == 1) {
                        $html .= '
                        <th width="5%">No</th>
                        <th width="95%">Kegiatan</th>';
                    } else {
                        $html .= '
                        <th width="5%">No</th>
                        <th width="35%">Kegiatan</th>
                        <th width="30%">Ukuran Keberhasilan</th>
                        <th width="30%">Realisasi</th>';
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
                        <td class="text-center">' . $no++ . '</td>
                        <td>' . $kegiatan . '</td>
                    </tr>';
                        } else {
                            $html .= '
                    <tr>
                        <td class="text-center">' . $no++ . '</td>
                        <td>' . $kegiatan . '</td>
                        <td>' . $ukuran . '</td>
                        <td>' . $realisasi . '</td>
                    </tr>';
                        }
                    }
                    
                    if (empty($hasil_kerja_grouped['tambahan'])) {
                        $colspan = ($tipe == 1) ? '2' : '4';
                        $html .= '
                    <tr>
                        <td colspan="' . $colspan . '" class="text-center">Tidak ada data</td>
                    </tr>';
                    }
                    
                    $html .= '
                </table>

                <!-- Rating Hasil Kerja -->';
                
                if ($tipe != 1) {
                    $html .= '
                <table>
                    <tr class="bg-gray">
                        <td width="30%"><strong>Rating Hasil Kerja</strong></td>
                        <td><strong>' . $rating_hasil_kerja . '</strong></td>
                    </tr>
                </table>';
                }
                
                $html .= '

                <!-- Perilaku Kerja -->
                <table>
                    <tr class="section-title">
                        <td colspan="3">PERILAKU KERJA</td>
                    </tr>
                    <tr class="bg-gray text-center">
                        <th width="5%">No</th>
                        <th width="50%">Perilaku Kerja</th>
                        <th width="45%">Ekspektasi Pimpinan</th>
                    </tr>';
                    
                    foreach ($perilaku_kerja as $pk) {
                        // Hitung jumlah baris untuk rowspan (1 untuk parent + jumlah items)
                        $rowspan = 1 + count($pk['items']);
                        
                        $html .= '
                    <tr>
                        <td class="text-center"><strong>' . $pk['no'] . '</strong></td>
                        <td><strong>' . $pk['perilaku_kerja'] . '</strong></td>
                        <td rowspan="' . $rowspan . '" style="vertical-align: top;">' . $pk['ekspektasi_pimpinan'] . '</td>
                    </tr>';
                        
                        // Tampilkan sub-items
                        if (!empty($pk['items'])) {
                            foreach ($pk['items'] as $item) {
                                $html .= '
                    <tr>
                        <td class="text-center">' . $item['kode'] . '</td>
                        <td>' . $item['perilaku_kerja'] . '</td>
                    </tr>';
                            }
                        }
                    }
                    
                    $html .= '
                </table>

                <!-- Rating Perilaku Kerja -->';
                
                if ($tipe != 1) {
                    $html .= '
                <table>
                    <tr class="bg-gray">
                        <td width="30%"><strong>Rating Perilaku Kerja</strong></td>
                        <td><strong>' . $rating_perilaku_kerja . '</strong></td>
                    </tr>
                </table>

                <!-- Predikat Kinerja -->
                <table>
                    <tr class="bg-gray">
                        <td width="30%"><strong>Predikat Kinerja</strong></td>
                        <td><strong>' . $predikat_kinerja . '</strong></td>
                    </tr>
                </table>';
                }
                
                $html .= '

                <!-- Tanda Tangan -->
                <table style="margin-top: 30px;">
                    <tr>
                        <td width="50%" style="vertical-align: top; padding: 20px;">
                            <div style="text-align: center;">
                                <strong>PEGAWAI YANG DINILAI</strong>
                                <br><br><br><br><br>
                                <strong>' . $pegawai['nama'] . '</strong><br>
                                NIP. ' . $pegawai['nip'] . '
                            </div>
                        </td>
                        <td width="50%" style="vertical-align: top; padding: 20px;">
                            <div style="text-align: center;">
                                <strong>PEJABAT PENILAI</strong>
                                <br><br><br><br><br>
                                <strong>' . $penilai['nama'] . '</strong><br>
                                NIP. ' . $penilai['nip'] . '
                            </div>
                        </td>
                    </tr>
                </table>

                <!-- Tanggal Pencetakan dan QR Code -->
                <table style="margin-top: 20px;">
                    <tr>
                        <td width="70%" style="padding: 10px;">
                            <strong>Dicetak pada:</strong> ' . date('d F Y, H:i:s') . '
                        </td>
                        <td width="30%" style="text-align: center; padding: 10px;">
                            <img src="https://api.qrserver.com/v1/create-qr-code/?size=100x100&data=' . urlencode($uid) . '" alt="QR Code" style="width: 100px; height: 100px;">
                            
                        </td>
                    </tr>
                </table>

            </body>
            </html>';
            
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
        // Logo menggunakan URL eksternal dari skpv2.untirta.ac.id
        $logo_url = 'https://skpv2.untirta.ac.id/images/favicon.png';
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
        
        // Ambil semua parent (yang tidak memiliki parent)
        $parents = $ref_perilaku_kerja_all->whereNull('parent');
        
        foreach ($parents as $parent) {
            $parent_kode = $parent->kode;
            
            // Buat struktur parent
            $perilaku_kerja_grouped[$parent_kode] = [
                'no' => $parent_kode,
                'kode' => $parent_kode,
                'perilaku_kerja' => $parent->perilaku_kerja,
                'ekspektasi_pimpinan' => $ekspektasi_map[$parent_kode] ?? '',
                'items' => []
            ];
            
            // Ambil semua children dari parent ini
            $children = $ref_perilaku_kerja_all->where('parent', $parent_kode);
            
            foreach ($children as $child) {
                $perilaku_kerja_grouped[$parent_kode]['items'][] = [
                    'kode' => $child->kode,
                    'perilaku_kerja' => $child->perilaku_kerja,
                ];
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
        <!DOCTYPE html>
        <html lang="id">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Lembar SKP - ' . $pegawai['nama'] . '</title>
            <style>
                body {
                    font-family: Arial, sans-serif;
                    margin: 10px;
                    font-size: 10px;
                    line-height: 1.3;
                }
                .header {
                    text-align: center;
                    margin-bottom: 5px;
                }
                .header img {
                    width: 60px;
                    height: 60px;
                    margin-bottom: 3px;
                }
                .header h2 {
                    margin: 2px 0;
                    font-size: 13px;
                    line-height: 1.2;
                }
                .header h3 {
                    margin: 1px 0;
                    font-size: 11px;
                    line-height: 1.2;
                }
                table {
                    width: 100%;
                    border-collapse: collapse;
                    margin-bottom: 4px;
                }
                table, th, td {
                    border: 1px solid #000;
                }
                th, td {
                    padding: 3px 4px;
                    text-align: left;
                    line-height: 1.3;
                }
                th {
                    background-color: #f0f0f0;
                    font-weight: bold;
                }
                .text-center {
                    text-align: center;
                }
                .text-right {
                    text-align: right;
                }
                .bg-gray {
                    background-color: #e0e0e0;
                }
                .section-title {
                    background-color: #d0d0d0;
                    font-weight: bold;
                    text-align: center;
                    padding: 4px !important;
                }
                .no-border {
                    border: none;
                }
                .info-table td {
                    border: none;
                    padding: 2px;
                }
            </style>
        </head>
        <body>
            <div class="header">
                ' . $header['logo_html'] . '
                <h2>' . $header['institusi'] . '</h2>
                <h3>' . $header['judul_1'] . '</h3>
                <h3>' . $header['judul_2'] . '</h3>
            </div>

            <!-- Informasi Periode -->
            <table class="info-table">
                <tr>
                    <td width="30%"><strong>Periode Penilaian</strong></td>
                    <td>: ' . $periode['tgl_penilaian_indo'] . '</td>
                </tr>
            </table>

            <!-- Informasi Pegawai yang Dinilai -->
            <table>
                <tr class="section-title">
                    <td colspan="2">PEGAWAI YANG DINILAI</td>
                </tr>
                <tr>
                    <td width="30%"><strong>Nama</strong></td>
                    <td>' . $pegawai['nama'] . '</td>
                </tr>
                <tr>
                    <td><strong>NIP</strong></td>
                    <td>' . $pegawai['nip'] . '</td>
                </tr>
                <tr>
                    <td><strong>Pangkat/Golongan</strong></td>
                    <td>' . $pegawai['pangkat_golongan'] . '</td>
                </tr>
                <tr>
                    <td><strong>Jabatan</strong></td>
                    <td>' . $pegawai['jabatan'] . '</td>
                </tr>
                <tr>
                    <td><strong>Unit Kerja</strong></td>
                    <td>' . $pegawai['unit_kerja'] . '</td>
                </tr>
            </table>

            <!-- Informasi Pejabat Penilai -->
            <table>
                <tr class="section-title">
                    <td colspan="2">PEJABAT PENILAI</td>
                </tr>
                <tr>
                    <td width="30%"><strong>Nama</strong></td>
                    <td>' . $penilai['nama'] . '</td>
                </tr>
                <tr>
                    <td><strong>NIP</strong></td>
                    <td>' . $penilai['nip'] . '</td>
                </tr>
                <tr>
                    <td><strong>Pangkat/Golongan</strong></td>
                    <td>' . $penilai['pangkat_golongan'] . '</td>
                </tr>
                <tr>
                    <td><strong>Jabatan</strong></td>
                    <td>' . $penilai['jabatan'] . '</td>
                </tr>
                <tr>
                    <td><strong>Unit Kerja</strong></td>
                    <td>' . $penilai['unit_kerja'] . '</td>
                </tr>
            </table>

            <!-- Hasil Kerja Utama -->
            <table>
                <tr class="section-title">
                    <td colspan="' . ($tipe == '1' ? '2' : '4') . '">HASIL KERJA UTAMA</td>
                </tr>
                <tr class="bg-gray text-center">
                    <th width="5%">No</th>
                    <th width="' . ($tipe == '1' ? '95%' : '35%') . '">Kegiatan</th>';
                    
                if ($tipe != '1') {
                    $html .= '
                    <th width="30%">Ukuran Keberhasilan</th>
                    <th width="30%">Realisasi</th>';
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
                    <td class="text-center">' . $no++ . '</td>
                    <td>' . $kegiatan . '</td>';
                    
                    if ($tipe != '1') {
                        $html .= '
                    <td>' . $ukuran . '</td>
                    <td>' . $realisasi . '</td>';
                    }
                    
                    $html .= '
                </tr>';
                }
                
                if (empty($hasil_kerja_grouped['utama'])) {
                    $colspan = ($tipe == '1') ? '2' : '4';
                    $html .= '
                <tr>
                    <td colspan="' . $colspan . '" class="text-center">Tidak ada data</td>
                </tr>';
                }
                
                $html .= '
            </table>

            <!-- Hasil Kerja Tambahan -->
            <table>
                <tr class="section-title">
                    <td colspan="' . ($tipe == '1' ? '2' : '4') . '">HASIL KERJA TAMBAHAN</td>
                </tr>
                <tr class="bg-gray text-center">
                    <th width="5%">No</th>
                    <th width="' . ($tipe == '1' ? '95%' : '35%') . '">Kegiatan</th>';
                    
                if ($tipe != '1') {
                    $html .= '
                    <th width="30%">Ukuran Keberhasilan</th>
                    <th width="30%">Realisasi</th>';
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
                    <td class="text-center">' . $no++ . '</td>
                    <td>' . $kegiatan . '</td>';
                    
                    if ($tipe != '1') {
                        $html .= '
                    <td>' . $ukuran . '</td>
                    <td>' . $realisasi . '</td>';
                    }
                    
                    $html .= '
                </tr>';
                }
                
                if (empty($hasil_kerja_grouped['tambahan'])) {
                    $colspan = ($tipe == '1') ? '2' : '4';
                    $html .= '
                <tr>
                    <td colspan="' . $colspan . '" class="text-center">Tidak ada data</td>
                </tr>';
                }
                
                $html .= '
            </table>

            ';
            
            // Rating Hasil Kerja hanya ditampilkan jika tipe != 1
            if ($tipe != '1') {
                $html .= '
            <!-- Rating Hasil Kerja -->
            <table>
                <tr class="bg-gray">
                    <td width="30%"><strong>Rating Hasil Kerja</strong></td>
                    <td><strong>' . $rating_hasil_kerja . '</strong></td>
                </tr>
            </table>';
            }
            
            $html .= '

            <!-- Perilaku Kerja -->
            <table>
                <tr class="section-title">
                    <td colspan="3">PERILAKU KERJA</td>
                </tr>
                <tr class="bg-gray text-center">
                    <th width="5%">No</th>
                    <th width="50%">Perilaku Kerja</th>
                    <th width="45%">Ekspektasi Pimpinan</th>
                </tr>';
                
                foreach ($perilaku_kerja as $pk) {
                    // Hitung jumlah baris untuk rowspan (1 untuk parent + jumlah items)
                    $rowspan = 1 + count($pk['items']);
                    
                    $html .= '
                <tr>
                    <td class="text-center"><strong>' . $pk['no'] . '</strong></td>
                    <td><strong>' . $pk['perilaku_kerja'] . '</strong></td>
                    <td rowspan="' . $rowspan . '" style="vertical-align: top;">' . $pk['ekspektasi_pimpinan'] . '</td>
                </tr>';
                    
                    // Tampilkan sub-items
                    if (!empty($pk['items'])) {
                        foreach ($pk['items'] as $item) {
                            $html .= '
                <tr>
                    <td class="text-center">' . $item['kode'] . '</td>
                    <td>' . $item['perilaku_kerja'] . '</td>
                </tr>';
                        }
                    }
                }
                
                $html .= '
            </table>';
            
            // Rating Perilaku Kerja dan Predikat Kinerja hanya ditampilkan jika tipe != 1
            if ($tipe != '1') {
                $html .= '

            <!-- Rating Perilaku Kerja -->
            <table>
                <tr class="bg-gray">
                    <td width="30%"><strong>Rating Perilaku Kerja</strong></td>
                    <td><strong>' . $rating_perilaku_kerja . '</strong></td>
                </tr>
            </table>

            <!-- Predikat Kinerja -->
            <table>
                <tr class="bg-gray">
                    <td width="30%"><strong>Predikat Kinerja</strong></td>
                    <td><strong>' . $predikat_kinerja . '</strong></td>
                </tr>
            </table>';
            }
            
            $html .= '

            <!-- Tanda Tangan -->
            <table style="margin-top: 8px; margin-bottom: 4px;">
                <tr>
                    <td width="50%" style="vertical-align: top; padding: 5px;">
                        <div style="text-align: center; font-size: 9px;">
                            <strong>PEGAWAI YANG DINILAI</strong>
                            <br><br><br>
                            <strong>' . $pegawai['nama'] . '</strong><br>
                            NIP. ' . $pegawai['nip'] . '
                        </div>
                    </td>
                    <td width="50%" style="vertical-align: top; padding: 5px;">
                        <div style="text-align: center; font-size: 9px;">
                            <strong>PEJABAT PENILAI</strong>
                            <br><br><br>
                            <strong>' . $penilai['nama'] . '</strong><br>
                            NIP. ' . $penilai['nip'] . '
                        </div>
                    </td>
                </tr>
            </table>

            <!-- Tanggal Pencetakan -->
            <table style="margin-bottom: 0;">
                <tr>
                    <td style="padding: 3px; font-size: 9px;">
                        <strong>Dicetak pada:</strong> ' . date('d F Y, H:i:s') . '<br>
                        <strong>Kode Dokumen:</strong> ' . $uid . '
                    </td>
                </tr>
            </table>

        </body>
        </html>';
        
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
