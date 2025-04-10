<?php 
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class PortofolioKinerja extends Model 
{
	
	protected $table = 'portofolio_kinerja';
	protected $primaryKey = 'id';
	protected $fillable = ["uid",
							"nip",
							"email",
							"nama",
							"jabatan_id",
							"jabatan",
							"unit_kerja_id",
							"unit_kerja",
							"pangkat_id",
							"pangkat",
							"no_sk",
							"tgl_sk",
							"homebase",
							"homebase_id",
							"kat_jabatan",
							"kat_jabatan_id",
							"level_pegawai",
							"status_kerja",
							"tahun",
							"jabatan_struktural",
							"jabatan_fungsional",
							"jabatan_struktural_id",
							"jabatan_fungsional_id"
						];
	
	public static function search($query, $text){
		$search_condition = '(
				id LIKE ?  OR 
				uid LIKE ?  OR 
				nip LIKE ?  OR 
				email LIKE ?  OR 
				nama LIKE ?  OR 
				jabatan LIKE ?  OR 
				unit_kerja LIKE ?  OR 
				no_sk LIKE ? 
		)';
		$search_params = [
			"%$text%","%$text%","%$text%","%$text%","%$text%","%$text%","%$text%","%$text%"
		];
		$query->whereRaw($search_condition, $search_params);
	}
	
	public static function listFields(){
		return [ 
			"id", 
			"uid", 
			"nip", 
			"email", 
			"nama", 
			"jabatan_id", 
			"jabatan", 
			"unit_kerja_id", 
			"unit_kerja", 
			"pangkat_id", 
			"pangkat", 
			"no_sk",
			"tgl_sk",
			"homebase",
			"homebase_id",
			"kat_jabatan",
			"kat_jabatan_id",
			"level_pegawai",
			"status_kerja",
			"tahun",
			"jabatan_struktural",
			"jabatan_fungsional",
			"jabatan_struktural_id",
			"jabatan_fungsional_id"
		];
	}
	
	public static function exportListFields(){
		return [ 
			"id", 
			"uid", 
			"nip", 
			"email", 
			"nama", 
			"jabatan_id", 
			"jabatan", 
			"unit_kerja_id", 
			"unit_kerja", 
			"pangkat_id", 
			"pangkat", 
			"no_sk",
			"tgl_sk",
			"homebase",
			"homebase_id",
			"kat_jabatan",
			"kat_jabatan_id",
			"level_pegawai",
			"status_kerja",
			"tahun",
			"jabatan_struktural",
			"jabatan_fungsional",
			"jabatan_struktural_id",
			"jabatan_fungsional_id"
		];
	}
	
	public static function viewFields(){
		return [ 
			"id", 
			"uid", 
			"nip", 
			"email", 
			"nama", 
			"jabatan_id", 
			"jabatan", 
			"unit_kerja_id", 
			"unit_kerja", 
			"pangkat_id", 
			"pangkat", 
			"no_sk",
			"tgl_sk",
			"homebase",
			"homebase_id",
			"kat_jabatan",
			"kat_jabatan_id",
			"level_pegawai",
			"status_kerja",
			"tahun",
			"jabatan_struktural",
			"jabatan_fungsional",
			"jabatan_struktural_id",
			"jabatan_fungsional_id"
		];
	}
	
	public static function exportViewFields(){
		return [ 
			"id", 
			"uid", 
			"nip", 
			"email", 
			"nama", 
			"jabatan_id", 
			"jabatan", 
			"unit_kerja_id", 
			"unit_kerja", 
			"pangkat_id", 
			"pangkat", 
			"no_sk" ,
			"tgl_sk",
			"homebase",
			"homebase_id",
			"kat_jabatan",
			"kat_jabatan_id",
			"level_pegawai",
			"status_kerja",
			"tahun",
			"jabatan_struktural",
			"jabatan_fungsional",
			"jabatan_struktural_id",
			"jabatan_fungsional_id"
		];
	}
	
	public static function editFields(){
		return [ 
			"id",
			"jabatan_id", 
			"jabatan", 
			"jabatan_struktural",
			"jabatan_fungsional",
			"jabatan_struktural_id",
			"jabatan_fungsional_id",
			"unit_kerja_id",
			"unit_kerja",
			"homebase_id",
			"homebase"
		];
	}
	
	public $timestamps = false;
}
