<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use DB;

class SimpegController extends Controller
{   
    function peg_aktif(Request $request){
        $nip = $request->nip;

        try {
            $response = Http::withHeaders([
                'simpeg2023' => 'Springu2023',
                'Content-Type' => 'application/json',
                'Connection' => 'Keep-Alive',
                'Accept' => 'application/json'
            ])->timeout(30)->get('https://simpeg.untirta.ac.id/berbagidata/all-pegawai2', [
                'nip' => $nip
            ]);

            if ($response->successful()) {
                $data = $response->json();
                // Get first item if response is array
                if (isset($data['data']) && is_array($data['data'])) {
                    return response()->json($data['data'][0] ?? null, 200);
                }
                return response()->json($data['data'] ?? null, 200);
            } else {
                return response()->json(['error' => 'Failed to fetch data'], $response->status());
            }
        } catch (\Exception $e) {
            return response()->json(['error' => 'Request failed: ' . $e->getMessage()], 500);
        }
   }

   
   function peg(Request $request){
        $nip = $request->nip;

        try {
            $response = Http::withHeaders([
                'simpeg2023' => 'Springu2023',
                'Content-Type' => 'application/json',
                'Connection' => 'Keep-Alive',
                'Accept' => 'application/json'
            ])->timeout(30)->get('https://simpeg.untirta.ac.id/berbagidata/pegawai', [
                'nip' => $nip
            ]);

            if ($response->successful()) {
                $data = $response->json();
                // Get first item if response is array
                if (isset($data['data']) && is_array($data['data'])) {
                    return response()->json($data['data'][0] ?? null, 200);
                }
                return response()->json($data['data'] ?? null, 200);
            } else {
                return response()->json(['error' => 'Failed to fetch data'], $response->status());
            }
        } catch (\Exception $e) {
            return response()->json(['error' => 'Request failed: ' . $e->getMessage()], 500);
        }
   }

   function jabatan(Request $request){
        $nip = $request->nip;

        try {
            $response = Http::withHeaders([
                'simpeg2023' => 'Springu2023',
                'Content-Type' => 'application/json',
                'Connection' => 'Keep-Alive',
                'Accept' => 'application/json'
            ])->timeout(30)->get('https://simpeg.untirta.ac.id/berbagidata/riwayat_jabatan', [
                'nip' => $nip
            ]);

            if ($response->successful()) {
                $data = $response->json();
                if (isset($data['data']) && is_array($data['data'])) {
                    $dataArray = $data['data'];
                    // Filter out items with empty tglSk
                    $validData = array_filter($dataArray, function($item) {
                        return !empty($item['tglSk'] ?? null);
                    });
                    
                    if (!empty($validData)) {
                        // Sort by tglSk in descending order
                        usort($validData, function($a, $b) {
                            return strtotime($b['tglSk'] ?? '1970-01-01') - strtotime($a['tglSk'] ?? '1970-01-01');
                        });
                        return response()->json($validData[0], 200);
                    }
                    return response()->json($dataArray[0] ?? null, 200);
                }
                return response()->json($data['data'] ?? null, 200);
            } else {
                return response()->json(['error' => 'Failed to fetch data'], $response->status());
            }
        } catch (\Exception $e) {
            return response()->json(['error' => 'Request failed: ' . $e->getMessage()], 500);
        }
    }

    function riwayat_jabatan(Request $request){
        $nip = $request->nip;

        try {
            $response = Http::withHeaders([
                'simpeg2023' => 'Springu2023',
                'Content-Type' => 'application/json',
                'Connection' => 'Keep-Alive',
                'Accept' => 'application/json'
            ])->timeout(30)->get('https://simpeg.untirta.ac.id/berbagidata/riwayat_jabatan', [
                'nip' => $nip
            ]);

            if ($response->successful()) {
                $data = $response->json();
                if (isset($data['data']) && is_array($data['data'])) {
                    return response()->json($data['data'], 200);
                }
                // If not array, wrap in array
                return response()->json([$data['data'] ?? null], 200);
            } else {
                return response()->json(['error' => 'Failed to fetch data'], $response->status());
            }
        } catch (\Exception $e) {
            return response()->json(['error' => 'Request failed: ' . $e->getMessage()], 500);
        }
    }

    function get_pegawai(Request $request) {
        $nama = $request->nama;
        $nip = $request->nip;
        
        try {
            $response = Http::withHeaders([
                'simpeg2023' => 'Springu2023',
                'Content-Type' => 'application/json',
                'Connection' => 'Keep-Alive',
                'Accept' => 'application/json'
            ])->timeout(30)->get('https://simpeg.untirta.ac.id/berbagidata/pegawai', [
                'nama' => $nama,
                'nip' => $nip
            ]);

            if ($response->successful()) {
                $data = $response->json();
                if (isset($data['data']) && is_array($data['data'])) {
                    // Filter results to only include matches on nama field
                    $filteredData = array_filter($data['data'], function($item) use ($nama) {
                        return stripos($item['namaPegawai'] ?? '', $nama) !== false;
                    });
                    return response()->json(array_values($filteredData), 200);
                }
                // If single result, check if it matches search
                if (isset($data['data']['namaPegawai']) && stripos($data['data']['namaPegawai'], $nama) !== false) {
                    return response()->json([$data['data']], 200);
                }
                return response()->json([], 200);
            } else {
                return response()->json(['error' => 'Failed to fetch data'], $response->status());
            }
        } catch (\Exception $e) {
            return response()->json(['error' => 'Request failed: ' . $e->getMessage()], 500);
        }
    }

    function simpeg_login(Request $request){
        $username = $request->username;
        $password = $request->password;

        try {
            $response = Http::withHeaders([
                'simpeg2023' => 'Springu2023',
                'Connection' => 'Keep-Alive',
                'Accept' => 'application/json'
            ])->timeout(30)->asForm()->post('https://simpeg.untirta.ac.id/berbagidata/login', [
                'username' => $username,
                'password' => $password
            ]);

            if ($response->successful()) {
                return response()->json($response->json(), 200);
            } else {
                return response()->json(['error' => 'Failed to login'], $response->status());
            }
        } catch (\Exception $e) {
            return response()->json(['error' => 'Request failed: ' . $e->getMessage()], 500);
        }
    }

    function spg_pegawai(Request $request){
        $nip = $request->nip;
        $id_sts = "d390b650-bf5e-454b-846f-efb3510f89a6"; // Aktif

        try {
            $response = Http::withHeaders([
                'simpeg2023' => 'Springu2023',
                'Content-Type' => 'application/json',
                'Connection' => 'Keep-Alive',
                'Accept' => 'application/json'
            ])->timeout(30)->get('https://simpeg.untirta.ac.id/berbagidata/all-pegawai2', [
                'nip' => $nip
            ]);

            if ($response->successful()) {
                $responseData = $response->json();
                $data = [];
                if (isset($responseData['data']) && is_array($responseData['data'])) {
                    $data = $responseData['data'];
                } elseif (isset($responseData['data']) && !empty($responseData['data'])) {
                    $data = [$responseData['data']];
                }

                // Fetch pangkat_id from the external API for each nip found
                $data = array_map(function($item) use ($id_sts) {
                    $gelarDepan = isset($item['gelarDepan']) && $item['gelarDepan'] ? trim($item['gelarDepan']) : '';
                    $nama = isset($item['namaPegawai']) ? trim($item['namaPegawai']) : '';
                    $gelarBelakang = isset($item['gelarBelakang']) && $item['gelarBelakang'] ? trim($item['gelarBelakang']) : '';
                    $nama_gelar = $nama;
                    if ($gelarDepan !== '') {
                        $nama_gelar = $gelarDepan . '. ' . $nama_gelar;
                    }
                    if ($gelarBelakang !== '') {
                        $nama_gelar = $nama_gelar . ', ' . $gelarBelakang;
                    }

                    $jabatanStruktural = isset($item['jabatanStruktural']) && $item['jabatanStruktural'] ? trim($item['jabatanStruktural']) : '';
                    $jabatanFungsional = isset($item['jabatanFungsional']) && $item['jabatanFungsional'] ? trim($item['jabatanFungsional']) : '';
                    
                    // Jabatan Aktif
                    if ($jabatanStruktural === 'Belum Memiliki Jabatan Struktural') {
                        $jabatan_aktif = $jabatanFungsional;
                    } elseif ($jabatanStruktural !== '' && $jabatanFungsional !== '') {
                        $jabatan_aktif = $jabatanStruktural . ' (' . $jabatanFungsional . ')';
                    } elseif ($jabatanStruktural !== '') {
                        $jabatan_aktif = $jabatanStruktural;
                    } elseif ($jabatanFungsional !== '') {
                        $jabatan_aktif = $jabatanFungsional;
                    } else {
                        $jabatan_aktif = '';
                    }

                    // ambil id_kat_pegawai (masih sesuai original)
                    $id_kat_pegawai = null;
                    // -- kode di sini bisa diaktifkan kalau ingin mapping kategori pegawai seperti di komentar sebelumnya

                    // Fetch pangkat_id dari endpoint pegawai?nip=
                    $nipValue = $item['nip'] ?? null;
                    $pangkat_id = null;
                    $pangkat = null;
                    if ($nipValue) {
                        try {
                            $pangkatResponse = Http::withHeaders([
                                'simpeg2023' => 'Springu2023',
                                'Content-Type' => 'application/json',
                                'Connection' => 'Keep-Alive',
                                'Accept' => 'application/json'
                            ])->timeout(20)->get('https://simpeg.untirta.ac.id/berbagidata/pegawai', [
                                'nip' => $nipValue
                            ]);

                            if ($pangkatResponse->successful()) {
                                $pangkatData = $pangkatResponse->json();
                                // Asumsi format: { "status"... "data": { ... "pangkat_id": "xxxxx", "pangkat": "xxx" ... } }
                                if (isset($pangkatData['data']['pangkat_id'])) {
                                    $pangkat_id = $pangkatData['data']['pangkat_id'];
                                } 
                                // fallback jika data['data'] langsung id
                                elseif (isset($pangkatData['data'][0]['pangkat_id'])) {
                                    $pangkat_id = $pangkatData['data'][0]['pangkat_id'];
                                }
                                // ambil juga pangkat jika ada
                                if (isset($pangkatData['data']['pangkat'])) {
                                    $pangkat = $pangkatData['data']['pangkat'];
                                } elseif (isset($pangkatData['data'][0]['pangkat'])) {
                                    $pangkat = $pangkatData['data'][0]['pangkat'];
                                }
                            }
                        } catch (\Exception $e) {
                            $pangkat_id = null;
                            $pangkat = null;
                        }
                    }

                    // Fetch kat_jabatan, id_kat_jabatan, no_sk, tgl_sk dari riwayat_jabatan (filter status = 1)
                    $kat_jabatan = null;
                    $id_kat_jabatan = null;
                    $no_sk = null;
                    $tgl_sk = null;
                    if ($nipValue) {
                        try {
                            $riwayatJabatanResponse = Http::withHeaders([
                                'simpeg2023' => 'Springu2023',
                                'Content-Type' => 'application/json',
                                'Connection' => 'Keep-Alive',
                                'Accept' => 'application/json'
                            ])->timeout(20)->get('https://simpeg.untirta.ac.id/berbagidata/riwayat_jabatan', [
                                'nip' => $nipValue
                            ]);

                            if ($riwayatJabatanResponse->successful()) {
                                $riwayatData = $riwayatJabatanResponse->json();
                                $items = $riwayatData['data'] ?? [];
                                if (!is_array($items)) {
                                    $items = $items ? [$items] : [];
                                }
                                foreach ($items as $rj) {
                                    $sts = $rj['status'] ?? null;
                                    if ((string)$sts === '1') {
                                        $kat_jabatan = $rj['katJabatan'] ?? null;
                                        $id_kat_jabatan = $rj['katJabatan_id'] ?? null;
                                        $no_sk = $rj['skJabatan'] ?? null;
                                        $tgl_sk = $rj['tglSk'] ?? null;
                                        break;
                                    }
                                }
                            }
                        } catch (\Exception $e) {
                            $kat_jabatan = null;
                            $id_kat_jabatan = null;
                            $no_sk = null;
                            $tgl_sk = null;
                        }
                    }

                    return [
                        'id_pegawai'                => $item['kdPegawai'] ?? null,
                        'id_user'                   => $item['email'] ?? null,                    
                        'nama'                      => $item['namaPegawai'] ?? null,
                        'nip'                       => $item['nip'] ?? null,
                        'nip_lama'                  => $item['nipLama'] ?? null,
                        'nama_gelar'                => $nama_gelar,
                        'jabatan_aktif'             => $jabatan_aktif,
                        'jabatan_fungsional'        => $item['jabatanFungsional'] ?? null,
                        'id_jabatan_fungsional'     => $item['kdJabatanfungsional'] ?? null,
                        'jabatan_struktural'        => $item['jabatanStruktural'] ?? null,
                        'id_jabatan_struktural'     => $item['kdJabatanstruktural'] ?? 0,
                        'golongan'                  => $item['golongan'] ?? null,
                        'jenis_pegawai'             => $item['jenisPegawai'] ?? null,
                        'id_jns_pegawai'            => $item['idJenisPegawai'] ?? null,
                        'nm_unit'                   => $item['namaUnitkerja'] ?? null,
                        'id_unit'                   => $item['kdUnitKerja'],
                        'id_homebase'               => $item['kdHomebase'] ?? null,
                        'homebase'                  => $item['namaHomebase'] ?? null,
                        'id_kat_pegawai'            => $id_kat_pegawai,
                        'nm_kat_pegawai'            => $item['kategoriPegawai'] ?? null,
                        'email'                     => $item['email'] ?? null,
                        'id_sts_pegawai'            => $id_sts,
                        'nm_sts_pegawai'            => $item['statusPegawai'] ?? null,
                        'id_unit_simpeg'            => $item['kdUnitKerja'] ?? null,
                        'id_unit_sister'            => $item['idSDM'] ?? null,
                        'pangkat_id'                => $pangkat_id,
                        'pangkat'                   => isset($pangkat) && isset($item['golongan']) && $item['golongan'] !== null
                                                        ? $pangkat . ' ( ' . $item['golongan'] . ' )'
                                                        : $pangkat,
                        'kat_jabatan'               => $kat_jabatan,
                        'id_kat_jabatan'            => $id_kat_jabatan,
                        'no_sk'                     => $no_sk,
                        'tgl_sk'                    => $tgl_sk,
                        'created_at'                => now(),
                        'updated_at'                => now(),
                    ];
                }, $data);

                return response()->json([
                    'status' => 'success',
                    'data' => $data
                ], 200);
            } else {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed to fetch data',
                    'data' => []
                ], $response->status());
            }
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Request failed: ' . $e->getMessage(),
                'data' => []
            ], 500);
        }
    }

    function spg_jabatan(Request $request){
        try {
            $responses = Http::pool(fn ($pool) => [
                $pool->withHeaders([
                    'simpeg2023' => 'Springu2023',
                    'Content-Type' => 'application/json',
                    'Connection' => 'Keep-Alive',
                    'Accept' => 'application/json'
                ])->timeout(30)->get('https://simpeg.untirta.ac.id/berbagidata/jabStruktural'),
                $pool->withHeaders([
                    'simpeg2023' => 'Springu2023',
                    'Content-Type' => 'application/json',
                    'Connection' => 'Keep-Alive',
                    'Accept' => 'application/json'
                ])->timeout(30)->get('https://simpeg.untirta.ac.id/berbagidata/jabNonstruktural'),
            ]);

            if ($responses[0]->successful() && $responses[1]->successful()) {
                $data1 = $responses[0]->json();
                $data2 = $responses[1]->json();
                $data = [];

                // Process struktural jabatan
                if (isset($data1['data']) && is_array($data1['data'])) {
                    $data = array_map(function($item) {
                        $JJ = $item['jenisJabatan'] ?? null;
                        $KJ = $item['kategoriJabatan'] ?? null;

                        $id_jns_jabatan = null;
                        if ($JJ == 0 && $KJ == 1) {
                            $id_jns_jabatan = '4452ced0-8006-455d-a053-1edc5136d433';
                        } elseif ($JJ == 0 && $KJ == 2) {
                            $id_jns_jabatan = '3c186b9d-0fe5-4d9d-ade0-43e465f8a533';
                        } elseif ($JJ == 1 && $KJ == 1) {
                            $id_jns_jabatan = '92f51eaf-ad52-4215-ae7b-4c5ab1594366';
                        } elseif ($JJ == 1 && $KJ == 2) {
                            $id_jns_jabatan = '3c186b9d-0fe5-4d9d-ade0-43e465f8a533';
                        } elseif ($JJ == 2 && $KJ == 2) {
                            $id_jns_jabatan = '9b2cbbee-22f6-40fe-8923-c80eef4e88ae';
                        } elseif ($JJ == 3 && $KJ == 2) {
                            $id_jns_jabatan = '3c186b9d-0fe5-4d9d-ade0-43e465f8a533';
                        }

                        return [
                            'id_ref_jabatan' => $item['kodeData'] ?? null,
                            'id_jns_jabatan' => $id_jns_jabatan,
                            'nm_ref_jabatan' => $item['namaJabatan'] ?? null,
                            'angka_kredit' => 0,
                            'kode' => $item['kodeData'] ?? null,
                            'is_show' => $item['statusJabatan'] ?? null,
                            'id_old' => null,
                            'grade' => $item['gradeJabatan'] ?? 0
                        ];
                    }, $data1['data']);
                }

                // Process nonstruktural jabatan
                if (isset($data2['data']) && is_array($data2['data'])) {
                    $nonstrukturalData = array_map(function($item) {
                        $JJ = $item['jenisJabatan'] ?? null;
                        $KJ = $item['kategoriJabatan'] ?? null;

                        $id_jns_jabatan = null;
                        if ($JJ == 0 && $KJ == 1) {
                            $id_jns_jabatan = '4452ced0-8006-455d-a053-1edc5136d433';
                        } elseif ($JJ == 0 && $KJ == 2) {
                            $id_jns_jabatan = '3c186b9d-0fe5-4d9d-ade0-43e465f8a533';
                        } elseif ($JJ == 1 && $KJ == 1) {
                            $id_jns_jabatan = '92f51eaf-ad52-4215-ae7b-4c5ab1594366';
                        } elseif ($JJ == 1 && $KJ == 2) {
                            $id_jns_jabatan = '3c186b9d-0fe5-4d9d-ade0-43e465f8a533';
                        } elseif ($JJ == 2 && $KJ == 2) {
                            $id_jns_jabatan = '9b2cbbee-22f6-40fe-8923-c80eef4e88ae';
                        } elseif ($JJ == 3 && $KJ == 2) {
                            $id_jns_jabatan = '3c186b9d-0fe5-4d9d-ade0-43e465f8a533';
                        }

                        return [
                            'id_ref_jabatan' => $item['kodeData'] ?? null,
                            'id_jns_jabatan' => $id_jns_jabatan,
                            'nm_ref_jabatan' => $item['namaJabatan'] ?? null,
                            'angka_kredit' => 0,
                            'kode' => $item['kodeData'] ?? null,
                            'is_show' => $item['statusJabatan'] ?? null,
                            'id_old' => null,
                            'grade' => $item['gradeJabatan'] ?? 0
                        ];
                    }, $data2['data']);

                    $data = array_merge($data, $nonstrukturalData);
                }

                return response()->json([
                    'status' => 'success',
                    'data' => $data
                ], 200);
            } else {
                $status = $responses[0]->successful() ? $responses[1]->status() : $responses[0]->status();
                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed to fetch data',
                    'data' => []
                ], $status);
            }
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Request failed: ' . $e->getMessage(),
                'data' => []
            ], 500);
        }
    }

    function spg_presensi(Request $request){
        $nip = $request->nip;
        $bulan = $request->bulan;
        $tahun = $request->tahun;

        try {
            $response = Http::withHeaders([
                'simpeg2023' => 'Springu2023',
                'Content-Type' => 'application/json',
                'Connection' => 'Keep-Alive',
                'Accept' => 'application/json'
            ])->timeout(30)->get('https://simpeg.untirta.ac.id/berbagidata/rekapKehadiran', [
                'nip' => $nip,
                'bulan' => $bulan,
                'tahun' => $tahun
            ]);

            if ($response->successful()) {
                $responseData = $response->json();
                
                // Check if the response itself is an array of objects (not wrapped in 'data' key)
                if (is_array($responseData) && !isset($responseData['data'])) {
                    $filteredData = array_map(function($item) use ($request) {
                        return [
                            'bulan' => $item['bulanData'] ?? null,
                            'tahun' => $item['tahunData'] ?? null,
                            'id_pegawai' => $item['kode'] ?? null,
                            'nip' => $item['nip'] ?? null,
                            'nama' => $item['nama_peg'] ?? null,
                            'jml_hadir' => $item['hadir'] ?? null,
                            'id_periode' => $request->id_periode ?? null,
                            'semester' => $request->semester ?? null,
                        ];
                    }, $responseData);
                    return response()->json([
                        'status' => 'success',
                        'data' => $filteredData
                    ], 200);
                }
                
                // Otherwise, check if it has a 'data' key
                if (isset($responseData['data'])) {
                    $dataToProcess = [];
                    if (is_array($responseData['data'])) {
                        $dataToProcess = $responseData['data'];
                    } else {
                        $dataToProcess = [$responseData['data']];
                    }
                    $filteredData = array_map(function($item) use ($request) {
                        return [
                            'bulan' => $item['bulanData'] ?? null,
                            'tahun' => $item['tahunData'] ?? null,
                            'id_pegawai' => $item['kode'] ?? null,
                            'nip' => $item['nip'] ?? null,
                            'nama' => $item['nama_peg'] ?? null,
                            'jml_hadir' => $item['hadir'] ?? null,
                            'id_periode' => $request->id_periode ?? null,
                            'semester' => $request->semester ?? null,
                        ];
                    }, $dataToProcess);
                    return response()->json([
                        'status' => 'success',
                        'data' => $filteredData
                    ], 200);
                }
                
                // If 'data' is not set, or is not an array/object, return an empty array
                return response()->json([
                    'status' => 'success',
                    'data' => []
                ], 200);
            } else {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed to fetch data',
                    'data' => []
                ], $response->status());
            }
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Request failed: ' . $e->getMessage(),
                'data' => []
            ], 500);
        }
    }   

}