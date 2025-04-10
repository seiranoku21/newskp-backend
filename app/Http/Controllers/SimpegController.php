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



      
}
