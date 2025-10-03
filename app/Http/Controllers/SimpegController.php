<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use DB;

class SimpegController extends Controller
{   
    function peg_aktif(Request $request){
        $nip = $request->nip;

        $curl = curl_init();
        curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://simpeg.untirta.ac.id/berbagidata/all-pegawai2?nip='.$nip,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'GET',
        CURLOPT_HTTPHEADER => array(
            'simpeg2023: Springu2023',
            'Content-Type: application/json',
            'Connection: Keep-Alive',
            'Accept: application/json'
        ),
        ));
       
        $response = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        if ($httpCode == 200) {
            $decodedResponse = json_decode($response);
            if (json_last_error() === JSON_ERROR_NONE) {
                // Get first item if response is array
                if (is_array($decodedResponse->data)) {
                    return response()->json($decodedResponse->data[0] ?? null, 200);
                }
                return response()->json($decodedResponse->data, 200);
            } else {
                return response()->json(['error' => 'Invalid JSON response from server'], 500);
            }
        } else {
            return response()->json(['error' => 'Failed to fetch data'], $httpCode);
        }
   }

   
   function peg(Request $request){
        $nip = $request->nip;
        $curl = curl_init();
        curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://simpeg.untirta.ac.id/berbagidata/pegawai?nip='.$nip,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'GET',
        CURLOPT_HTTPHEADER => array(
            'simpeg2023: Springu2023',
            'Content-Type: application/json',
            'Connection: Keep-Alive',
            'Accept: application/json'
        ),
        ));
       
        $response = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        if ($httpCode == 200) {
            $decodedResponse = json_decode($response);
            if (json_last_error() === JSON_ERROR_NONE) {
                // Get first item if response is array
                if (is_array($decodedResponse->data)) {
                    return response()->json($decodedResponse->data[0] ?? null, 200);
                }
                return response()->json($decodedResponse->data, 200);
            } else {
                return response()->json(['error' => 'Invalid JSON response from server'], 500);
            }
        } else {
            return response()->json(['error' => 'Failed to fetch data'], $httpCode);
        }
   }

   function jabatan(Request $request){
        $nip = $request->nip;
        $curl = curl_init();
        curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://simpeg.untirta.ac.id/berbagidata/riwayat_jabatan?nip='.$nip,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'GET',
        CURLOPT_HTTPHEADER => array(
            'simpeg2023: Springu2023',
            'Content-Type: application/json',
            'Connection: Keep-Alive',
            'Accept: application/json'
        ),
    ));
   
    $response = curl_exec($curl);
    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);

    if ($httpCode == 200) {
        $decodedResponse = json_decode($response);
        if (json_last_error() === JSON_ERROR_NONE) {
            if (is_array($decodedResponse->data)) {
                $data = $decodedResponse->data;
                // Filter out items with empty tglSk
                $validData = array_filter($data, function($item) {
                    return !empty($item->tglSk);
                });
                
                if (!empty($validData)) {
                    // Sort by tglSk in descending order
                    usort($validData, function($a, $b) {
                        return strtotime($b->tglSk) - strtotime($a->tglSk);
                    });
                    return response()->json($validData[0], 200);
                }
                return response()->json($data[0] ?? null, 200);
            }
            return response()->json($decodedResponse->data, 200);
        } else {
            return response()->json(['error' => 'Invalid JSON response from server'], 500);
        }
    } else {
        return response()->json(['error' => 'Failed to fetch data'], $httpCode);
    }
}

    function riwayat_jabatan(Request $request){
        $nip = $request->nip;
        $curl = curl_init();
        curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://simpeg.untirta.ac.id/berbagidata/riwayat_jabatan?nip='.$nip,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'GET',
        CURLOPT_HTTPHEADER => array(
            'simpeg2023: Springu2023',
            'Content-Type: application/json',
            'Connection: Keep-Alive',
            'Accept: application/json'
        ),
        ));
    
        $response = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        if ($httpCode == 200) {
            $decodedResponse = json_decode($response);
            if (json_last_error() === JSON_ERROR_NONE) {
                if (is_array($decodedResponse->data)) {
                    return response()->json($decodedResponse->data, 200);
                }
                // If not array, wrap in array
                return response()->json([$decodedResponse->data], 200);
            } else {
                return response()->json(['error' => 'Invalid JSON response from server'], 500);
            }
        } else {
            return response()->json(['error' => 'Failed to fetch data'], $httpCode);
        }
    }

    function get_pegawai(Request $request) {
        $nama = $request->nama;
        $nip = $request->nip;
        // URL encode the nama parameter
        $encodedNama = urlencode($nama);
        
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://simpeg.untirta.ac.id/berbagidata/pegawai?nama='.$encodedNama.'&nip='.$nip,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => array(
                'simpeg2023: Springu2023',
                'Content-Type: application/json',
                'Connection: Keep-Alive',
                'Accept: application/json'
            ),
        ));

        $response = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        if ($httpCode == 200) {
            $decodedResponse = json_decode($response);
            if (json_last_error() === JSON_ERROR_NONE) {
                if (is_array($decodedResponse->data)) {
                    // Filter results to only include matches on nama field
                    $filteredData = array_filter($decodedResponse->data, function($item) use ($nama) {
                        return stripos($item->namaPegawai, $nama) !== false;
                    });
                    return response()->json(array_values($filteredData), 200);
                }
                // If single result, check if it matches search
                if (stripos($decodedResponse->data->namaPegawai, $nama) !== false) {
                    return response()->json([$decodedResponse->data], 200);
                }
                return response()->json([], 200);
            } else {
                return response()->json(['error' => 'Invalid JSON response from server'], 500);
            }
        } else {
            return response()->json(['error' => 'Failed to fetch data'], $httpCode);
        }
    }

    function simpeg_login(Request $request){
        $username = $request->username;
        $password = $request->password;
        $curl = curl_init();

        // Create form data
        $postData = array(
            'username' => $username,
            'password' => $password
        );

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://simpeg.untirta.ac.id/berbagidata/login',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => $postData,
            CURLOPT_HTTPHEADER => array(
                'simpeg2023: Springu2023',
                'Connection: Keep-Alive',
                'Accept: application/json'
            ),
        ));

        $response = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        if ($httpCode == 200) {
            return response()->json(json_decode($response), 200);
        } else {
            return response()->json(['error' => 'Failed to login'], $httpCode);
        }
    }

    function spg_pegawai(Request $request){
        $nip = $request->nip;
        $curl = curl_init();
        $id_sts = "d390b650-bf5e-454b-846f-efb3510f89a6"; // Aktif

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://simpeg.untirta.ac.id/berbagidata/all-pegawai2?nip='.$nip,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => array(
                'simpeg2023: Springu2023',
                'Content-Type: application/json',
                'Connection: Keep-Alive',
                'Accept: application/json'
            ),
        ));
       
        $response = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        if ($httpCode == 200) {
            $decodedResponse = json_decode($response);
            if (json_last_error() === JSON_ERROR_NONE) {
                $data = [];
                if (is_array($decodedResponse->data)) {
                    $data = $decodedResponse->data;
                } elseif (!empty($decodedResponse->data)) {
                    $data = [$decodedResponse->data];
                }
                // Only return selected columns
                $data = array_map(function($item) use ($id_sts) {
                    $gelarDepan = isset($item->gelarDepan) && $item->gelarDepan ? trim($item->gelarDepan) : '';
                    $nama = isset($item->namaPegawai) ? trim($item->namaPegawai) : '';
                    $gelarBelakang = isset($item->gelarBelakang) && $item->gelarBelakang ? trim($item->gelarBelakang) : '';
                    $nama_gelar = $nama;
                    if ($gelarDepan !== '') {
                        $nama_gelar = $gelarDepan . '. ' . $nama_gelar;
                    }
                    if ($gelarBelakang !== '') {
                        $nama_gelar = $nama_gelar . ', ' . $gelarBelakang;
                    }

                    $jabatanStruktural = isset($item->jabatanStruktural) && $item->jabatanStruktural ? trim($item->jabatanStruktural) : '';
                    $jabatanFungsional = isset($item->jabatanFungsional) && $item->jabatanFungsional ? trim($item->jabatanFungsional) : '';
                    
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

                    if ($item->jenisPegawai == 'Dosen') {
                        $id_jns_pegawai = '99eaad80-f677-4f64-8a85-cbb5d7e01f32';
                    } elseif ($item->jenisPegawai == 'Dosen DT') {
                        $id_jns_pegawai = '99eaad91-2ce9-4a99-8d80-1c351f69cb81';
                    } elseif ($item->jenisPegawai == 'Tenaga Kependidikan') {
                        $id_jns_pegawai = "99eaad7b-b9c5-48eb-b902-e147913138ff";
                    } elseif ($item->jenisPegawai == 'Dosen Luar Biasa') {
                        $id_jns_pegawai = "a005e42b-f497-4ef7-97e5-4dec0b9eb23e";
                    }

                    

                    // ID Kategori Pegawai
                    $id_kat_pegawai = match (true) {
                        // PNS
                        ($item->jenisPegawai == 'Dosen' || $item->jenisPegawai == 'Dosen DT') && $item->kategoriPegawai == 'PNS' => '6a278f97-a0f9-4ff9-83ce-021673cf0541',
                        $item->jenisPegawai == 'Tenaga Kependidikan' && $item->kategoriPegawai == 'PNS' => '962e8132-dc7c-4773-9ed3-de541786db82',
                        // CPNS
                        ($item->jenisPegawai == 'Dosen' || $item->jenisPegawai == 'Dosen DT') && $item->kategoriPegawai == 'CPNS' => '38f8f789-4e4d-400e-a921-27a1c381e333',
                        $item->jenisPegawai == 'Tenaga Kependidikan' && $item->kategoriPegawai == 'CPNS' => '2fee17dd-6799-4f72-902f-9a6c5a2c540f',
                        // BLU
                        ($item->jenisPegawai == 'Dosen' || $item->jenisPegawai == 'Dosen DT') && $item->kategoriPegawai == 'BLU' => '04f685e5-8bff-4657-aac2-74551a948f7d',
                        $item->jenisPegawai == 'Tenaga Kependidikan' && $item->kategoriPegawai == 'BLU' => 'c0aa3763-003e-49e0-989e-bb7e22df60d9',
                        // PPPK
                        ($item->jenisPegawai == 'Dosen' || $item->jenisPegawai == 'Dosen DT') && $item->kategoriPegawai == 'PPPK' => '99ff2746-4a48-4712-9092-ace854d8ace2',
                        $item->jenisPegawai == 'Tenaga Kependidikan' && $item->kategoriPegawai == 'PPPK' => '99ff276f-e223-4de1-959b-0c296723bb32',
                        // PKWT
                        ($item->jenisPegawai == 'Dosen' || $item->jenisPegawai == 'Dosen DT') && $item->kategoriPegawai == 'PKWT' => '8210d999-c11d-4c5d-bac7-5574d03ce5ed',
                        $item->jenisPegawai == 'Tenaga Kependidikan' && $item->kategoriPegawai == 'PKWT' => 'd3dff2c5-1353-4c01-8d50-95eb3df963d5',
                        // Honorer, Non BLU, Outsourcing
                        ($item->jenisPegawai == 'Dosen' || $item->jenisPegawai == 'Dosen DT') && in_array($item->kategoriPegawai, ['Honorer', 'Non BLU', 'Outsourcing']) => 'e54422ab-0c17-439a-b2ad-9fa894739cb2',
                        $item->jenisPegawai == 'Tenaga Kependidikan' && in_array($item->kategoriPegawai, ['Honorer', 'Non BLU', 'Outsourcing']) => '9a0bcb50-4ab5-4115-9b8d-b92e6386cfd5',
                       
                        default => null,
                    };

                    return [
                        'id_pegawai'                => $item->kdPegawai ?? null,
                        'id_user'                   => $item->email ?? null,
                        'nama'                      => $item->namaPegawai ?? null,
                        'nip'                       => $item->nip ?? null,
                        'nip_lama'                  => $item->nipLama ?? null,
                        'nama_gelar'                => $nama_gelar,
                        'jabatan_aktif'             => $jabatan_aktif,
                        'jabatan_fungsional'        => $item->jabatanFungsional ?? null,
                        'pangkat'                   => $item->pangkat ?? null,
                        'golongan'                  => $item->golongan ?? null,
                        'jenis_pegawai'             => $item->jenisPegawai ?? null,
                        'id_jns_pegawai'            => $id_jns_pegawai,
                        'jabatan_struktural'        => $item->jabatanStruktural ?? null,
                        'id_jabatan_fungsional'     => $item->kdJabatanfungsional ?? null,
                        'id_jabatan_struktural'     => $item->kdJabatanStruktural ?? null,
                        'id_unit'                   => null,
                        'nm_unit'                   => $item->namaUnitkerja ?? null,
                        'id_kat_pegawai'            => $id_kat_pegawai,
                        'nm_kat_pegawai'            => $item->kategoriPegawai ?? null,
                        'email'                     => $item->email ?? null,
                        'id_sts_pegawai'            => $id_sts,
                        'nm_sts_pegawai'            => $item->statusPegawai ?? null,
                        'id_unit_simpeg'            => $item->kdUnitKerja ?? null,
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
                    'message' => 'Invalid JSON response from server',
                    'data' => []
                ], 500);
            }
        } else {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch data',
                'data' => []
            ], $httpCode);
        }
    }
}