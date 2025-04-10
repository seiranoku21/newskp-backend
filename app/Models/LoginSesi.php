<?php 
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class LoginSesi extends Model 
{
	

	/**
     * The table associated with the model.
     *
     * @var string
     */
	protected $table = 'login_sesi';
	

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
	protected $fillable = ["uid","nip","email","nama","token","login_tgl","login_expired","login_sumber","user_role_id"];
	

	/**
     * Set search query for the model
	 * @param \Illuminate\Database\Eloquent\Builder $query
	 * @param string $text
     */
	public static function search($query, $text){
		//search table record 
		$search_condition = '(
				id LIKE ?  OR 
				uid LIKE ?  OR 
				nip LIKE ?  OR 
				email LIKE ?  OR 
				nama LIKE ?  OR 
				token LIKE ?  OR 
				login_sumber LIKE ? 
		)';
		$search_params = [
			"%$text%","%$text%","%$text%","%$text%","%$text%","%$text%","%$text%"
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
			"uid", 
			"nip", 
			"email", 
			"nama", 
			"token", 
			"login_tgl", 
			"login_expired", 
			"login_sumber", 
			"user_role_id" 
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
			"uid", 
			"nip", 
			"email", 
			"nama", 
			"token", 
			"login_tgl", 
			"login_expired", 
			"login_sumber", 
			"user_role_id" 
		];
	}
	

	/**
     * return view page fields of the model.
     * 
     * @return array
     */
	public static function viewFields(){
		return [ 
			"id", 
			"uid", 
			"nip", 
			"email", 
			"nama", 
			"token", 
			"login_tgl", 
			"login_expired", 
			"login_sumber", 
			"user_role_id" 
		];
	}
	

	/**
     * return exportView page fields of the model.
     * 
     * @return array
     */
	public static function exportViewFields(){
		return [ 
			"id", 
			"uid", 
			"nip", 
			"email", 
			"nama", 
			"token", 
			"login_tgl", 
			"login_expired", 
			"login_sumber", 
			"user_role_id" 
		];
	}
	

	/**
     * return edit page fields of the model.
     * 
     * @return array
     */
	public static function editFields(){
		return [ 
			"id", 
			"uid", 
			"nip", 
			"email", 
			"nama", 
			"token", 
			"login_tgl", 
			"login_expired", 
			"login_sumber", 
			"user_role_id" 
		];
	}
	

	/**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
	public $timestamps = false;
}
