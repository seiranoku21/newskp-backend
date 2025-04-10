<?php 
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class AktifitasKinerja extends Model 
{
	

	/**
     * The table associated with the model.
     *
     * @var string
     */
	protected $table = 'aktifitas_kinerja';
	

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
	protected $fillable = ["rhki_id",
						   "rhka_id",
						   "portofolio_kinerja_uid",
						   "nip",
						   "tanggal_mulai",
						   "tanggal_selesai",
						   "tahun",
						   "jumlah",
						   "satuan",
						   "dokumen",
						   "gambar",
						   "rating_hasil_kerja",
						   "poin",
						   "created_at",
						   "updated_at"
						  ];
	

	/**
     * Set search query for the model
	 * @param \Illuminate\Database\Eloquent\Builder $query
	 * @param string $text
     */
	public static function search($query, $text){
		//search table record 
		$search_condition = '(
				portofolio_kinerja_uid LIKE ?  OR 
				nip LIKE ? 
		)';
		$search_params = [
			"%$text%","%$text%"
		];
		//setting search conditions
		$query->whereRaw($search_condition, $search_params);
	}
	

	public static function listFields(){
		return [ 
			"id", 
			"rhki_id", 
			"rhka_id", 
			"portofolio_kinerja_uid", 
			"nip", 
			"tanggal_mulai", 
			"tanggal_selesai", 
			"tahun", 
			"jumlah", 
			"satuan", 
			"dokumen", 
			"gambar", 
			"rating_hasil_kerja",
			"poin",
			"updated_at", 
			"created_at" ,
			"kegiatan"
		];
	}
	
	public static function exportListFields(){
		return [ 
			"id", 
			"rhki_id", 
			"rhka_id", 
			"portofolio_kinerja_uid", 
			"nip", 
			"tanggal_mulai", 
			"tanggal_selesai", 
			"tahun", 
			"jumlah", 
			"satuan", 
			"dokumen", 
			"gambar", 
			"rating_hasil_kerja",
			"poin",
			"updated_at", 
			"created_at" 
		];
	}
	

	public static function viewFields(){
		return [ 
			"id", 
			"rhki_id", 
			"rhka_id", 
			"portofolio_kinerja_uid", 
			"nip", 
			"tanggal_mulai", 
			"tanggal_selesai", 
			"tahun", 
			"jumlah", 
			"satuan", 
			"dokumen", 
			"gambar", 
			"rating_hasil_kerja",
			"poin",
			"updated_at", 
			"created_at" 
		];
	}
	

	public static function exportViewFields(){
		return [ 
			"id", 
			"rhki_id", 
			"rhka_id", 
			"portofolio_kinerja_uid", 
			"nip", 
			"tanggal_mulai", 
			"tanggal_selesai", 
			"tahun", 
			"jumlah", 
			"satuan", 
			"dokumen", 
			"gambar", 
			"rating_hasil_kerja",
			"poin",
			"updated_at", 
			"created_at" 
		];
	}
	

	public static function editFields(){
		return [ 
			"tanggal_mulai", 
			"tanggal_selesai", 
			"jumlah", 
			"satuan", 
			"dokumen", 
			"gambar", 
			"rating_hasil_kerja",
			"poin",
			"id" 
		];
	}

	public static function editvrfFields(){
		return [ 
			"rating_hasil_kerja",
			"poin",
			"id"
		];
	}
	
	public $timestamps = true;
}
