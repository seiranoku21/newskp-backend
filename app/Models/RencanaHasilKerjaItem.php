<?php 
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class RencanaHasilKerjaItem extends Model 
{
	

	/**
     * The table associated with the model.
     *
     * @var string
     */
	protected $table = 'rencana_hasil_kerja_item';
	

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
	protected $fillable = ["rhka_id",
							"portofolio_kinerja_uid",
							"nip","kegiatan",
							"aspek_kuantitas",
							"aspek_kualitas",
							"aspek_waktu",
							"ukuran_keberhasilan",
							"kategori",
							"realisasi",
							"updated_at",
							"created_at"
							];
	

	/**
     * Set search query for the model
	 * @param \Illuminate\Database\Eloquent\Builder $query
	 * @param string $text
     */
	public static function search($query, $text){
		//search table record 
		$search_condition = '(
				id LIKE ?  OR 
				kegiatan LIKE ? 
		)';
		$search_params = [
			"%$text%","%$text%"
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
			"id", 
			"rhka_id", 
			"portofolio_kinerja_uid", 
			"nip", 
			"kegiatan", 
			"aspek_kuantitas", 
			"aspek_kualitas", 
			"aspek_waktu",
			"ukuran_keberhasilan",
			"realisasi",
			"rubrik_kinerja",
			"kategori",
			"updated_at",
			"created_at"
		];
	}
	

	/**
     * return exportList page fields of the model.
     * 
     * @return array
     */
	public static function exportListFields(){
		return [ 
			"id", 
			"rhka_id", 
			"portofolio_kinerja_uid", 
			"nip", 
			"kegiatan", 
			"aspek_kuantitas", 
			"aspek_kualitas", 
			"aspek_waktu",
			"ukuran_keberhasilan",
			"realisasi",
			"rubrik_kinerja",
			"updated_at",
			"created_at"
		];
	}
	

	/**
     * return exportView page fields of the model.
     * 
     * @return array
     */
	public static function exportViewFields(){
		return [ 
		];
	}
	

	/**
     * return edit page fields of the model.
     * 
     * @return array
     */
	public static function editFields(){
		return [ 
			"rhka_id", 
			"portofolio_kinerja_uid", 
			"nip", 
			"kegiatan", 
			"aspek_kuantitas", 
			"aspek_kualitas", 
			"aspek_waktu", 
			"ukuran_keberhasilan",
			"realisasi",
			"updated_at",
			"created_at",
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
