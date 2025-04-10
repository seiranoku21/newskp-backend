<?php 
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class SkpKontrak extends Model 
{
	

	/**
     * The table associated with the model.
     *
     * @var string
     */
	protected $table = 'skp_kontrak';
	

	/**
     * The table primary key field
     *
     * @var string
     */
	protected $primaryKey = 'id';
	

	/**
     * Table fillable fields
     *
     * @var array
     */
	protected $fillable = ["uid","tahun","skp_tipe_id","periode_id","periode_awal","periode_akhir","pegawai_nip","pegawai_email","pegawai_nama","pegawai_pangkat_id","pegawai_pangkat","pegawai_jabatan_id","pegawai_jabatan","pegawai_unit_kerja_id","pegawai_unit_kerja","penilai_nip","penilai_email","penilai_nama","penilai_pangkat_id","penilai_pangkat","penilai_jabatan_id","penilai_jabatan","penilai_unit_kerja_id","penilai_unit_kerja","status_id","status_vrf_id","portofolio_id","portofolio_uid","updated_at","created_at","poin","rating_hasil_kerja","rating_perilaku_kerja","predikat_kinerja"];
	

	/**
     * Set search query for the model
	 * @param \Illuminate\Database\Eloquent\Builder $query
	 * @param string $text
     */
	public static function search($query, $text){
		//search table record 
		$search_condition = '(
				ref_periode.periode LIKE ?  OR 
				skp_kontrak.pegawai_nip LIKE ?  OR 
				skp_kontrak.pegawai_nama LIKE ?  OR 
				skp_kontrak.penilai_nip LIKE ?  OR 
				skp_kontrak.penilai_nama LIKE ?  OR 
				ref_status.id LIKE ?  OR 
				ref_status.status LIKE ?  OR 
				ref_status_vrf.id LIKE ?  OR 
				ref_status_vrf.status_vrf LIKE ? 
		)';
		$search_params = [
			"%$text%","%$text%","%$text%","%$text%","%$text%","%$text%","%$text%","%$text%","%$text%"
		];
		//setting search conditions
		$query->whereRaw($search_condition, $search_params);
	}
	

	/**
     * return list page fields of the model.
     * 
     * @return array
     */
	public static function listFields(){
		return [ 
			"skp_kontrak.id AS id", 
			"skp_kontrak.tahun AS tahun",
			"skp_kontrak.uid AS uid", 
			"skp_kontrak.skp_tipe_id AS skp_tipe_id", 
			"ref_periode.periode AS refperiode_periode", 
			"skp_kontrak.periode_awal AS periode_awal", 
			"skp_kontrak.periode_akhir AS periode_akhir", 
			"skp_kontrak.pegawai_nip AS pegawai_nip", 
			"skp_kontrak.pegawai_nama AS pegawai_nama", 
			"skp_kontrak.pegawai_jabatan AS pegawai_jabatan",
			"skp_kontrak.pegawai_unit_kerja AS pegawai_unit_kerja", 
			"skp_kontrak.penilai_nip AS penilai_nip", 
			"skp_kontrak.penilai_nama AS penilai_nama", 
			"skp_kontrak.penilai_jabatan AS penilai_jabatan", 
			"skp_kontrak.penilai_unit_kerja AS penilai_unit_kerja",
			"skp_kontrak.status_id AS status_id", 
			"skp_kontrak.status_vrf_id AS status_vrf_id", 
			"ref_status.id AS refstatus_id", 
			"ref_status.status AS refstatus_status", 
			"ref_status_vrf.id AS refstatusvrf_id", 
			"ref_status_vrf.status_vrf AS refstatusvrf_status_vrf", 
			"skp_kontrak.periode_id AS periode_id", 
			"ref_periode.id AS refperiode_id",
			"skp_kontrak.portofolio_id",
			"skp_kontrak.portofolio_uid",
			"skp_kontrak.poin",
			"skp_kontrak.rating_hasil_kerja",
			"skp_kontrak.rating_perilaku_kerja",
			"skp_kontrak.predikat_kinerja",
			"skp_kontrak.updated_at",
			"skp_kontrak.created_at"
		];
	}
	

	/**
     * return exportList page fields of the model.
     * 
     * @return array
     */
	public static function exportListFields(){
		return [ 
			"skp_kontrak.id AS id", 
			"skp_kontrak.uid AS uid", 
			"skp_kontrak.tahun AS tahun",
			"skp_kontrak.skp_tipe_id AS skp_tipe_id", 
			"ref_periode.periode AS refperiode_periode", 
			"skp_kontrak.periode_awal AS periode_awal", 
			"skp_kontrak.periode_akhir AS periode_akhir", 
			"skp_kontrak.pegawai_nip AS pegawai_nip", 
			"skp_kontrak.pegawai_nama AS pegawai_nama", 
			"skp_kontrak.pegawai_unit_kerja AS pegawai_unit_kerja", 
			"skp_kontrak.penilai_nip AS penilai_nip", 
			"skp_kontrak.penilai_nama AS penilai_nama", 
			"skp_kontrak.penilai_jabatan AS penilai_jabatan", 
			"skp_kontrak.status_id AS status_id", 
			"skp_kontrak.status_vrf_id AS status_vrf_id", 
			"ref_status.id AS refstatus_id", 
			"ref_status.status AS refstatus_status", 
			"ref_status_vrf.id AS refstatusvrf_id", 
			"ref_status_vrf.status_vrf AS refstatusvrf_status_vrf", 
			"skp_kontrak.periode_id AS periode_id", 
			"ref_periode.id AS refperiode_id",
			"skp_kontrak.portofolio_id",
			"skp_kontrak.portofolio_uid",
			"skp_kontrak.poin",
			"skp_kontrak.rating_hasil_kerja",
			"skp_kontrak.rating_perilaku_kerja",
			"skp_kontrak.predikat_kinerja",
			"skp_kontrak.updated_at",
			"skp_kontrak.created_at"
		];
	}
	

	/**
     * return view page fields of the model.
     * 
     * @return array
     */
	public static function viewFields(){
		return [ 
			"skp_kontrak.id AS id", 
			"skp_kontrak.uid AS uid", 
			"skp_kontrak.tahun AS tahun",
			"skp_kontrak.periode_awal AS periode_awal", 
			"skp_kontrak.periode_akhir AS periode_akhir", 
			"skp_kontrak.pegawai_nip AS pegawai_nip", 
			"skp_kontrak.pegawai_email AS pegawai_email", 
			"skp_kontrak.pegawai_nama AS pegawai_nama", 
			"skp_kontrak.skp_tipe_id AS skp_tipe_id", 
			"skp_kontrak.penilai_nip AS penilai_nip", 
			"skp_kontrak.penilai_email AS penilai_email", 
			"skp_kontrak.penilai_nama AS penilai_nama", 
			"skp_kontrak.pegawai_pangkat_id AS pegawai_pangkat_id", 
			"skp_kontrak.pegawai_pangkat AS pegawai_pangkat", 
			"skp_kontrak.pegawai_jabatan_id AS pegawai_jabatan_id", 
			"skp_kontrak.pegawai_jabatan AS pegawai_jabatan", 
			"skp_kontrak.pegawai_unit_kerja_id AS pegawai_unit_kerja_id", 
			"skp_kontrak.pegawai_unit_kerja AS pegawai_unit_kerja", 
			"skp_kontrak.penilai_pangkat_id AS penilai_pangkat_id", 
			"skp_kontrak.penilai_pangkat AS penilai_pangkat", 
			"skp_kontrak.penilai_jabatan_id AS penilai_jabatan_id", 
			"skp_kontrak.penilai_jabatan AS penilai_jabatan", 
			"skp_kontrak.penilai_unit_kerja_id AS penilai_unit_kerja_id", 
			"skp_kontrak.penilai_unit_kerja AS penilai_unit_kerja", 
			"skp_kontrak.periode_id AS periode_id", 
			"skp_kontrak.status_id AS status_id", 
			"skp_kontrak.status_vrf_id AS status_vrf_id", 
			"ref_status.id AS refstatus_id", 
			"ref_status.status AS refstatus_status", 
			"ref_status_vrf.id AS refstatusvrf_id", 
			"ref_status_vrf.status_vrf AS refstatusvrf_status_vrf", 
			"ref_periode.id AS refperiode_id", 
			"ref_periode.periode AS refperiode_periode",
			"skp_kontrak.portofolio_id",
			"skp_kontrak.portofolio_uid",
			"skp_kontrak.poin",
			"skp_kontrak.rating_hasil_kerja",
			"skp_kontrak.rating_perilaku_kerja",
			"skp_kontrak.predikat_kinerja",
			"skp_kontrak.updated_at",
			"skp_kontrak.created_at"
		];
	}
	/**
     * return exportView page fields of the model.
     * 
     * @return array
     */
	public static function exportViewFields(){
		return [ 
			"skp_kontrak.id AS id", 
			"skp_kontrak.uid AS uid", 
			"skp_kontrak.tahun AS tahun",
			"skp_kontrak.periode_awal AS periode_awal", 
			"skp_kontrak.periode_akhir AS periode_akhir", 
			"skp_kontrak.pegawai_nip AS pegawai_nip", 
			"skp_kontrak.pegawai_email AS pegawai_email", 
			"skp_kontrak.pegawai_nama AS pegawai_nama", 
			"skp_kontrak.skp_tipe_id AS skp_tipe_id", 
			"skp_kontrak.penilai_nip AS penilai_nip", 
			"skp_kontrak.penilai_email AS penilai_email", 
			"skp_kontrak.penilai_nama AS penilai_nama", 
			"skp_kontrak.pegawai_pangkat_id AS pegawai_pangkat_id", 
			"skp_kontrak.pegawai_pangkat AS pegawai_pangkat", 
			"skp_kontrak.pegawai_jabatan_id AS pegawai_jabatan_id", 
			"skp_kontrak.pegawai_jabatan AS pegawai_jabatan", 
			"skp_kontrak.pegawai_unit_kerja_id AS pegawai_unit_kerja_id", 
			"skp_kontrak.pegawai_unit_kerja AS pegawai_unit_kerja", 
			"skp_kontrak.penilai_pangkat_id AS penilai_pangkat_id", 
			"skp_kontrak.penilai_pangkat AS penilai_pangkat", 
			"skp_kontrak.penilai_jabatan_id AS penilai_jabatan_id", 
			"skp_kontrak.penilai_jabatan AS penilai_jabatan", 
			"skp_kontrak.penilai_unit_kerja_id AS penilai_unit_kerja_id", 
			"skp_kontrak.penilai_unit_kerja AS penilai_unit_kerja", 
			"skp_kontrak.periode_id AS periode_id", 
			"skp_kontrak.status_id AS status_id", 
			"skp_kontrak.status_vrf_id AS status_vrf_id", 
			"ref_status.id AS refstatus_id", 
			"ref_status.status AS refstatus_status", 
			"ref_status_vrf.id AS refstatusvrf_id", 
			"ref_status_vrf.status_vrf AS refstatusvrf_status_vrf", 
			"ref_periode.id AS refperiode_id", 
			"ref_periode.periode AS refperiode_periode",
			"skp_kontrak.portofolio_id",
			"skp_kontrak.portofolio_uid",
			"skp_kontrak.poin",
			"skp_kontrak.rating_hasil_kerja",
			"skp_kontrak.rating_perilaku_kerja",
			"skp_kontrak.predikat_kinerja",
			"skp_kontrak.updated_at",
			"skp_kontrak.created_at"
		];
	}
	

	/**
     * return edit page fields of the model.
     * 
     * @return array
     */
	public static function editFields(){
		return [ 
			"skp_kontrak.uid", 
			"skp_kontrak.tahun",
			"skp_kontrak.skp_tipe_id", 
			"skp_kontrak.periode_id", 
			"skp_kontrak.periode_awal", 
			"skp_kontrak.periode_akhir", 
			"skp_kontrak.penilai_nip", 
			"skp_kontrak.penilai_email", 
			"skp_kontrak.penilai_nama", 
			"skp_kontrak.penilai_pangkat_id", 
			"skp_kontrak.penilai_pangkat", 
			"skp_kontrak.penilai_jabatan_id", 
			"skp_kontrak.penilai_jabatan", 
			"skp_kontrak.penilai_unit_kerja_id", 
			"skp_kontrak.penilai_unit_kerja", 
			"skp_kontrak.status_id", 
			"skp_kontrak.id" ,
			"skp_kontrak.portofolio_id",
			"skp_kontrak.portofolio_uid",
			"skp_kontrak.poin",
			"skp_kontrak.rating_hasil_kerja",
			"skp_kontrak.rating_perilaku_kerja",
			"skp_kontrak.predikat_kinerja",
			"skp_kontrak.pegawai_nip",
			"skp_kontrak.updated_at",
			"skp_kontrak.created_at"
		];
	}
	

	/**
     * return status page fields of the model.
     * 
     * @return array
     */
	public static function statusFields(){
		return [ 
			"status_id", 
			"id" 
		];
	}
	

	/**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
	public $timestamps = true;
}
