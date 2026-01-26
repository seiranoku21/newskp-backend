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
                // Only return selected columns
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

                    // Jenis Pegawai 
                    $id_jns_pegawai = null;
                    $jenisPegawai = $item['jenisPegawai'] ?? null;

                    if ($jenisPegawai == 'Dosen') {
                        $id_jns_pegawai = '99eaad80-f677-4f64-8a85-cbb5d7e01f32';
                    } elseif ($jenisPegawai == 'Dosen DT') {
                        $id_jns_pegawai = '99eaad91-2ce9-4a99-8d80-1c351f69cb81';
                    } elseif ($jenisPegawai == 'Tenaga Kependidikan') {
                        $id_jns_pegawai = "99eaad7b-b9c5-48eb-b902-e147913138ff";
                    } elseif ($jenisPegawai == 'Dosen Luar Biasa') {
                        $id_jns_pegawai = "a005e42b-f497-4ef7-97e5-4dec0b9eb23e";
                    }

                    // ID Kategori Pegawai
                    $kategoriPegawai = $item['kategoriPegawai'] ?? null;
                    $id_kat_pegawai = match (true) {
                        // PNS
                        ($jenisPegawai == 'Dosen' || $jenisPegawai == 'Dosen DT') && $kategoriPegawai == 'PNS' => '6a278f97-a0f9-4ff9-83ce-021673cf0541',
                        $jenisPegawai == 'Tenaga Kependidikan' && $kategoriPegawai == 'PNS' => '962e8132-dc7c-4773-9ed3-de541786db82',
                        // CPNS
                        ($jenisPegawai == 'Dosen' || $jenisPegawai == 'Dosen DT') && $kategoriPegawai == 'CPNS' => '38f8f789-4e4d-400e-a921-27a1c381e333',
                        $jenisPegawai == 'Tenaga Kependidikan' && $kategoriPegawai == 'CPNS' => '2fee17dd-6799-4f72-902f-9a6c5a2c540f',
                        // BLU
                        ($jenisPegawai == 'Dosen' || $jenisPegawai == 'Dosen DT') && $kategoriPegawai == 'BLU' => '04f685e5-8bff-4657-aac2-74551a948f7d',
                        $jenisPegawai == 'Tenaga Kependidikan' && $kategoriPegawai == 'BLU' => 'c0aa3763-003e-49e0-989e-bb7e22df60d9',
                        // PPPK
                        ($jenisPegawai == 'Dosen' || $jenisPegawai == 'Dosen DT') && $kategoriPegawai == 'PPPK' => '99ff2746-4a48-4712-9092-ace854d8ace2',
                        $jenisPegawai == 'Tenaga Kependidikan' && $kategoriPegawai == 'PPPK' => '99ff276f-e223-4de1-959b-0c296723bb32',
                        // PKWT
                        ($jenisPegawai == 'Dosen' || $jenisPegawai == 'Dosen DT') && $kategoriPegawai == 'PKWT' => '8210d999-c11d-4c5d-bac7-5574d03ce5ed',
                        $jenisPegawai == 'Tenaga Kependidikan' && $kategoriPegawai == 'PKWT' => 'd3dff2c5-1353-4c01-8d50-95eb3df963d5',
                        // Honorer, Non BLU, Outsourcing
                        ($jenisPegawai == 'Dosen' || $jenisPegawai == 'Dosen DT') && in_array($kategoriPegawai, ['Honorer', 'Non BLU', 'Outsourcing']) => 'e54422ab-0c17-439a-b2ad-9fa894739cb2',
                        $jenisPegawai == 'Tenaga Kependidikan' && in_array($kategoriPegawai, ['Honorer', 'Non BLU', 'Outsourcing']) => '9a0bcb50-4ab5-4115-9b8d-b92e6386cfd5',
                       
                        default => null,
                    };

                    return [
                        'id_pegawai'                => $item['kdPegawai'] ?? null,
                        'id_user'                   => $item['email'] ?? null,
                        'nama'                      => $item['namaPegawai'] ?? null,
                        'nip'                       => $item['nip'] ?? null,
                        'nip_lama'                  => $item['nipLama'] ?? null,
                        'nama_gelar'                => $nama_gelar,
                        'jabatan_aktif'             => $jabatan_aktif,
                        'jabatan_fungsional'        => $item['jabatanFungsional'] ?? null,
                        'pangkat'                   => $item['pangkat'] ?? null,
                        'golongan'                  => $item['golongan'] ?? null,
                        'jenis_pegawai'             => $item['jenisPegawai'] ?? null,
                        'id_jns_pegawai'            => $id_jns_pegawai,
                        'jabatan_struktural'        => $item['jabatanStruktural'] ?? null,
                        'id_jabatan_fungsional'     => $item['kdJabatanfungsional'] ?? null,
                        'id_jabatan_struktural'     => $item['kdJabatanStruktural'] ?? null,
                        'id_unit'                   => null,
                        'nm_unit'                   => $item['namaUnitkerja'] ?? null,
                        'id_kat_pegawai'            => $id_kat_pegawai,
                        'nm_kat_pegawai'            => $item['kategoriPegawai'] ?? null,
                        'email'                     => $item['email'] ?? null,
                        'id_sts_pegawai'            => $id_sts,
                        'nm_sts_pegawai'            => $item['statusPegawai'] ?? null,
                        'id_unit_simpeg'            => $item['kdUnitKerja'] ?? null,
                        'id_unit_sister'            => null,
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