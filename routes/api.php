<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/
// api routes that need auth

Route::middleware(['auth:api', 'rbac'])->group(function () {

    // ---INTEGRASI

    Route::get('portofolio', 'AglobalController@get_portofolio');
    Route::get('rubrik_kegiatan_rhki', 'AglobalController@rubrik_kegiatan_rhki');	
    Route::get('aktifitas', 'AglobalController@get_aktifitas');	
    Route::post('aktifitas/add', 'AktifitasKinerjaController@tambah_aktifitas');	
    Route::match(['post','put','patch'], 'aktifitas/edit/{rec_id}', 'AktifitasKinerjaController@edit'); 	
    Route::any('aktifitas/delete/{rec_id}', 'AktifitasKinerjaController@delete');

    Route::get('list_ajuan_skp', 'AglobalController@list_ajuan_skp');
    Route::post('ajuan_skp/add', 'SkpKontrakController@tambah_ajuan');
    Route::any('ajuan_skp/delete/{rec_id}', 'SkpKontrakController@delete');

    // ---RHKA 
    Route::get('kinerja_rhka', 'RencanaHasilKerjaAtasanController@list_rhka');
    Route::post('kinerja_rhka/add', 'RencanaHasilKerjaAtasanController@tambah_rhka');	
    Route::any('kinerja_rhka/edit/{rec_id}', 'RencanaHasilKerjaAtasanController@ubah_rhka');	
    Route::any('kinerja_rhka/delete/{rec_id}', 'RencanaHasilKerjaAtasanController@delete');	
    
    // ---RHKI
    Route::get('kinerja_rhkI', 'RencanaHasilKerjaItemController@list_rhki');
    Route::post('kinerja_rhki/add', 'RencanaHasilKerjaItemController@tambah_rhki');	
    Route::any('kinerja_rhki/edit/{rec_id}', 'RencanaHasilKerjaItemController@ubah_rhki');	
    Route::any('kinerja_rhki/delete/{rec_id}', 'RencanaHasilKerjaItemController@delete');	

    


    // ---END INTEGRASI


/* routes for LoginSesi Controller  */	
	Route::get('login_sesi/', 'LoginSesiController@index');
	Route::get('login_sesi/index', 'LoginSesiController@index');
	Route::get('login_sesi/index/{filter?}/{filtervalue?}', 'LoginSesiController@index');	
	Route::get('login_sesi/view/{rec_id}', 'LoginSesiController@view');	
	Route::post('login_sesi/add', 'LoginSesiController@add');	
	Route::any('login_sesi/edit/{rec_id}', 'LoginSesiController@edit');	
	Route::any('login_sesi/delete/{rec_id}', 'LoginSesiController@delete');

/* routes for Permissions Controller  */	
	Route::get('permissions/', 'PermissionsController@index');
	Route::get('permissions/index', 'PermissionsController@index');
	Route::get('permissions/index/{filter?}/{filtervalue?}', 'PermissionsController@index');	
	Route::get('permissions/view/{rec_id}', 'PermissionsController@view');	
	Route::post('permissions/add', 'PermissionsController@add');	
	Route::any('permissions/edit/{rec_id}', 'PermissionsController@edit');	
	Route::any('permissions/delete/{rec_id}', 'PermissionsController@delete');

/* routes for RefHasilKerja Controller  */	
	// Route::get('ref_hasil_kerja/', 'RefHasilKerjaController@index');
	// Route::get('ref_hasil_kerja/index', 'RefHasilKerjaController@index');
	// Route::get('ref_hasil_kerja/index/{filter?}/{filtervalue?}', 'RefHasilKerjaController@index');	
	// Route::get('ref_hasil_kerja/view/{rec_id}', 'RefHasilKerjaController@view');	
	// Route::post('ref_hasil_kerja/add', 'RefHasilKerjaController@add');	
	// Route::any('ref_hasil_kerja/edit/{rec_id}', 'RefHasilKerjaController@edit');	
	// Route::any('ref_hasil_kerja/delete/{rec_id}', 'RefHasilKerjaController@delete');

/* routes for RefPredikat Controller  */	
	Route::get('ref_predikat/', 'RefPredikatController@index');
	Route::get('ref_predikat/index', 'RefPredikatController@index');
	Route::get('ref_predikat/index/{filter?}/{filtervalue?}', 'RefPredikatController@index');	
	Route::get('ref_predikat/view/{rec_id}', 'RefPredikatController@view');	
	Route::post('ref_predikat/add', 'RefPredikatController@add');	
	Route::any('ref_predikat/edit/{rec_id}', 'RefPredikatController@edit');	
	Route::any('ref_predikat/delete/{rec_id}', 'RefPredikatController@delete');

/* routes for RefSkpTipe Controller  */	
	Route::get('ref_skp_tipe/', 'RefSkpTipeController@index');
	Route::get('ref_skp_tipe/index', 'RefSkpTipeController@index');
	Route::get('ref_skp_tipe/index/{filter?}/{filtervalue?}', 'RefSkpTipeController@index');	
	Route::get('ref_skp_tipe/view/{rec_id}', 'RefSkpTipeController@view');	
	Route::post('ref_skp_tipe/add', 'RefSkpTipeController@add');	
	Route::any('ref_skp_tipe/edit/{rec_id}', 'RefSkpTipeController@edit');	
	Route::any('ref_skp_tipe/delete/{rec_id}', 'RefSkpTipeController@delete');

/* routes for Roles Controller  */	
	Route::get('roles/', 'RolesController@index');
	Route::get('roles/index', 'RolesController@index');
	Route::get('roles/index/{filter?}/{filtervalue?}', 'RolesController@index');	
	Route::get('roles/view/{rec_id}', 'RolesController@view');	
	Route::post('roles/add', 'RolesController@add');	
	Route::any('roles/edit/{rec_id}', 'RolesController@edit');	
	Route::any('roles/delete/{rec_id}', 'RolesController@delete');

// /* routes for SkpKontrak Controller  */	
// 	Route::get('skp_kontrak/', 'SkpKontrakController@index');
// 	Route::get('skp_kontrak/index', 'SkpKontrakController@index');
// 	Route::get('skp_kontrak/index/{filter?}/{filtervalue?}', 'SkpKontrakController@index');	
// 	Route::get('skp_kontrak/view/{rec_id}', 'SkpKontrakController@view');	
// 	Route::post('skp_kontrak/add', 'SkpKontrakController@add');	
// 	Route::any('skp_kontrak/edit/{rec_id}', 'SkpKontrakController@edit');	
// 	Route::any('skp_kontrak/delete/{rec_id}', 'SkpKontrakController@delete');

/* routes for Users Controller  */	
	Route::get('users/', 'UsersController@index');
	Route::get('users/index', 'UsersController@index');
	Route::get('users/index/{filter?}/{filtervalue?}', 'UsersController@index');	
	Route::get('users/view/{rec_id}', 'UsersController@view');	
	Route::any('account/edit', 'AccountController@edit');	
	Route::get('account', 'AccountController@index');	
	Route::get('account/currentuserdata', 'AccountController@currentuserdata');	
	Route::post('users/add', 'UsersController@add');	
	Route::any('users/edit/{rec_id}', 'UsersController@edit');	
	Route::any('users/delete/{rec_id}', 'UsersController@delete');

});

// Create a new middleware group that checks for valid tokens
// Remove the middleware group for api.key

    // Move all the routes previously inside the api.key middleware group
    Route::get('home', 'HomeController@index');
    Route::get('get_photo', 'AccountController@get_photo');

    /* routes for FileUpload Controller  */	
    Route::post('fileuploader/upload/{fieldname}', 'FileUploaderController@upload');
    Route::post('fileuploader/s3upload/{fieldname}', 'FileUploaderController@s3upload');
    Route::post('fileuploader/remove_temp_file', 'FileUploaderController@remove_temp_file');

    /* ---SKP Kontrak--- */	
    Route::get('skp_kontrak/', 'SkpKontrakController@index');
    Route::get('skp_kontrak/index', 'SkpKontrakController@index');
    Route::get('skp_kontrak/index/{filter?}/{filtervalue?}', 'SkpKontrakController@index');	
    Route::get('skp_kontrak/view/{rec_id}', 'SkpKontrakController@view');	
    Route::post('skp_kontrak/add', 'SkpKontrakController@add');	
    Route::any('skp_kontrak/edit/{rec_id}', 'SkpKontrakController@edit');	
    Route::any('skp_kontrak/delete/{rec_id}', 'SkpKontrakController@delete');
    Route::any('skp_kontrak/status/{rec_id}', 'SkpKontrakController@status');

    // ---Portofolio Kinerja---
    Route::get('porto_kinerja/', 'PortofolioKinerjaController@index');
    Route::get('porto_kinerja/index', 'PortofolioKinerjaController@index');
    Route::get('porto_kinerja/index/{filter?}/{filtervalue?}', 'PortofolioKinerjaController@index');	
    Route::get('porto_kinerja/view/{rec_id}', 'PortofolioKinerjaController@view');	
    Route::post('porto_kinerja/add', 'PortofolioKinerjaController@add');	
    Route::any('porto_kinerja/edit/{rec_id}', 'PortofolioKinerjaController@edit');	
    Route::any('porto_kinerja/delete/{rec_id}', 'PortofolioKinerjaController@delete');

    // ---Rencana Hasil Kerja Atasan---
    Route::get('rhka/', 'RencanaHasilKerjaAtasanController@index');
    Route::get('rhka/index', 'RencanaHasilKerjaAtasanController@index');
    Route::get('rhka/index/{filter?}/{filtervalue?}', 'RencanaHasilKerjaAtasanController@index');	
    Route::post('rhka/add', 'RencanaHasilKerjaAtasanController@add');	
    Route::any('rhka/edit/{rec_id}', 'RencanaHasilKerjaAtasanController@edit');	
    Route::any('rhka/editfield/{rec_id}', 'RencanaHasilKerjaAtasanController@editfield');	
    Route::any('rhka/delete/{rec_id}', 'RencanaHasilKerjaAtasanController@delete');

    /* ---Rencana Hasil Kerja--- */	
    Route::get('rhk/', 'RencanaHasilKerjaItemController@index');
    Route::get('rhk/index', 'RencanaHasilKerjaItemController@index');
    Route::get('rhk/index/{filter?}/{filtervalue?}', 'RencanaHasilKerjaItemController@index');	
    Route::post('rhk/add', 'RencanaHasilKerjaItemController@add');	
    Route::any('rhk/edit/{rec_id}', 'RencanaHasilKerjaItemController@edit');	
    Route::any('rhk/editfield/{rec_id}', 'RencanaHasilKerjaItemController@editfield');	
    Route::any('rhk/delete/{rec_id}', 'RencanaHasilKerjaItemController@delete');

    /* routes for AktifitasKinerja Controller  */	
	Route::get('aktifitas_kinerja/', 'AktifitasKinerjaController@index');
	Route::get('aktifitas_kinerja/index', 'AktifitasKinerjaController@index');
	Route::get('aktifitas_kinerja/index/{filter?}/{filtervalue?}', 'AktifitasKinerjaController@index');	
	Route::get('aktifitas_kinerja/view/{rec_id}', 'AktifitasKinerjaController@view');	
	Route::post('aktifitas_kinerja/add', 'AktifitasKinerjaController@add');	
	Route::any('aktifitas_kinerja/edit/{rec_id}', 'AktifitasKinerjaController@edit');	
	Route::any('aktifitas_kinerja/delete/{rec_id}', 'AktifitasKinerjaController@delete');
    Route::any('aktifitas_kinerja/editvrf/{rec_id}', 'AktifitasKinerjaController@editvrf');

    /* routes for PerilakuKerja Controller  */	
	Route::get('perilaku_kerja/', 'PerilakuKerjaController@index');
	Route::get('perilaku_kerja/index', 'PerilakuKerjaController@index');
	Route::get('perilaku_kerja/index/{filter?}/{filtervalue?}', 'PerilakuKerjaController@index');	
	Route::post('perilaku_kerja/add', 'PerilakuKerjaController@add');	
	Route::any('perilaku_kerja/edit/{rec_id}', 'PerilakuKerjaController@edit');	
	Route::any('perilaku_kerja/delete/{rec_id}', 'PerilakuKerjaController@delete');


    // AGLOBAL
    Route::get('get_pegawai_by_nip/{nip}', 'AglobalController@get_pegawai_by_nip');
    Route::get('get_jabatan_by_nip/{nip}', 'AglobalController@get_jabatan_by_nip');
    Route::get('get_riwayat_jabatan_by_nip/{nip}', 'AglobalController@get_riwayat_jabatan_by_nip');
    Route::get('get_ekspektasi_pimpinan', 'AglobalController@get_ekspektasi_pimpinan');
    Route::get('periode_rentang_bln/{periode_id}', 'AglobalController@periode_rentang_bln');
    Route::get('ref_perilaku_kerja', 'AglobalController@ref_perilaku_kerja');
    Route::get('ref_skp_tipe/{skp_id}', 'AglobalController@ref_skp_tipe');
    Route::get('get_portofolio_by_nip/{nip}', 'AglobalController@get_portofolio_by_nip');
    Route::get('get_portofolio_by_id/{id}', 'AglobalController@get_portofolio_by_id');
    Route::get('get_aktifitas', 'AglobalController@get_aktifitas');	
    Route::get('ref_hasil_kerja/{kode}', 'AglobalController@ref_hasil_kerja');
    // ---PERILAKU KERJA---
    Route::post('tambah_perilaku_kerja_template', 'AglobalController@tambah_perilaku_kerja_template');
    Route::post('tambah_perilaku_kerja_template_blank', 'AglobalController@tambah_perilaku_kerja_template_blank');
    Route::any('ubah_perilaku_kerja', 'AglobalController@ubah_perilaku_kerja');
    Route::get('cek_perilaku_kerja_template', 'AglobalController@cek_perilaku_kerja_template');
    // ---KONTRAK---
    Route::get('get_skp_kontrak', 'AglobalController@get_skp_kontrak');
    // ---END KONTRAK

    // ---VERIFIKASI---
    Route::get('is_vrf_skp', 'AglobalController@is_vrf_skp');
    Route::get('vrf_listing', 'AglobalController@vrf_listing');
    Route::get('get_poin_aktifitas', 'AglobalController@get_poin_aktifitas');
    Route::get('rating_hasil_kerja_aktifitas', 'AglobalController@rating_hasil_kerja_aktifitas');
    Route::any('ubah_skp_kontrak_vrf', 'AglobalController@ubah_skp_kontrak_vrf');
    Route::any('ubah_perilaku_kerja_vrf', 'AglobalController@ubah_perilaku_kerja_vrf');
    Route::any('ubah_predikat_kinerja', 'AglobalController@ubah_predikat_kinerja');
    Route::get('get_rating_kinerja', 'AglobalController@get_rating');
    Route::any('ubah_status_vrf', 'AglobalController@ubah_status_vrf');
    Route::get('is_vrf_skp_data', 'AglobalController@is_vrf_skp_data');
    // ---END VERIFIKASI---

    // ---ADMIN---
    Route::get('admin/listing_vrf', 'AglobalController@listing_vrf');
    // ---END ADMIN---      


// Keep authentication routes outside the middleware
Route::post('auth/login', 'AuthController@login');
Route::post('auth/sso_login', 'AuthController@sso_login');
Route::post('auth/sso_logout', 'AuthController@sso_logout');
Route::post('auth/sso_refresh_token', 'AuthController@sso_refresh_token');
Route::post('auth/sso_userinfo', 'AuthController@sso_userinfo');
Route::get('login', 'AuthController@login')->name('login');

 // ---Shared
 Route::get('components_data/user_role_id_option_list/{arg1?}', 'Components_dataController@user_role_id_option_list');	
 Route::get('components_data/skp_tipe_id_option_list/{arg1?}', 'Components_dataController@skp_tipe_id_option_list');	
 Route::get('components_data/users_username_exist/{arg1?}', 'Components_dataController@users_username_exist');	
 Route::get('components_data/users_email_exist/{arg1?}', 'Components_dataController@users_email_exist');
 Route::get('components_data/periode_id_option_list/{arg1?}', 'Components_dataController@periode_id_option_list');
 Route::get('components_data/status_id_option_list/{arg1?}', 'Components_dataController@status_id_option_list');
 Route::get('skp_tipe_deskripsi/{arg1?}', 'Components_dataController@skp_tipe_deskripsi');
 Route::get('components_data/portofolio_id_option_list/{arg1?}', 'Components_dataController@portofolio_id_option_list');
 Route::get('components_data/rhki_id_option_list/{arg1?}', 'Components_dataController@rhki_id_option_list');	
 Route::get('components_data/rhka_id_option_list/{arg1?}', 'Components_dataController@rhka_id_option_list');	
 Route::get('components_data/portofolio_kinerja_uid_option_list/{arg1?}', 'Components_dataController@portofolio_kinerja_uid_option_list');	
 Route::get('components_data/satuan_option_list/{arg1?}', 'Components_dataController@satuan_option_list');

 // SSO & SIMPEG
 Route::get('simpeg_pegawai','SimpegController@peg');
 Route::get('simpeg_jabatan','SimpegController@jabatan');
 Route::get('simpeg_riwayat_jabatan','SimpegController@riwayat_jabatan');
 Route::get('account/currentuserdata_sso', 'AccountController@currentuserdata_sso');	
 Route::get('account/currentuserdata_spl', 'AccountController@currentuserdata_spl');
 Route::get('sso_role', 'AccountController@sso_role');	
 Route::get('spl_role', 'AccountController@spl_role');
 Route::get('get_pegawai', 'SimpegController@get_pegawai');
 Route::post('simpeg_login', 'SimpegController@simpeg_login');
 Route::get('simpeg_pegawai_aktif', 'SimpegController@peg_aktif');

 /* routes for FileUpload Controller  */	
Route::post('fileuploader/upload/{fieldname}', 'FileUploaderController@upload');
Route::post('fileuploader/s3upload/{fieldname}', 'FileUploaderController@s3upload');
Route::post('fileuploader/remove_temp_file', 'FileUploaderController@remove_temp_file');

//  ---REFERENSI
Route::get('ref_periode', 'AglobalController@ref_periode');
Route::get('ref_satuan', 'AglobalController@ref_satuan');
Route::get('ref_hasilkerja', 'AglobalController@ref_hasil_kerja');
Route::get('ref_predikat', 'AglobalController@ref_predikat');
Route::get('ref_status', 'AglobalController@ref_status');
Route::get('ref_status_vrf', 'AglobalController@ref_status_vrf');
Route::get('ref_tipe_skp', 'AglobalController@ref_tipe_skp');

// ---LAPORAN ---DATA REPORT
Route::get('lap_aktifitas', 'AglobalController@laporan_aktifitas');

// ---HTML OUTPUT
Route::get('portofolio_html', 'AglobalController@get_portofolio_html');
Route::get('lap_aktifitas_html', 'AglobalController@laporan_aktifitas_html');