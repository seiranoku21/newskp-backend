<?php 
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Roles extends Model 
{
	

	/**
     * The table associated with the model.
     *
     * @var string
     */
	protected $table = 'roles';
	

	/**
     * The table primary key field
     *
     * @var string
     */
	protected $primaryKey = 'role_id';
	

	/**
     * Table fillable fields
     *
     * @var array
     */
	protected $fillable = ["role_name","role_sso_id"];
	

	/**
     * Set search query for the model
	 * @param \Illuminate\Database\Eloquent\Builder $query
	 * @param string $text
     */
	public static function search($query, $text){
		//search table record 
		$search_condition = '(
				role_id = ?  OR 
				role_name LIKE ? 
				role_sso_id = ?
		)';
		$search_params = [
			"$text","%$text%","$text"
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
			"role_id", 
			"role_name",
			"role_sso_id"
		];
	}
	

	/**
     * return exportList page fields of the model.
     * 
     * @return array
     */
	public static function exportListFields(){
		return [ 
			"role_id", 
			"role_name",
			"role_sso_id"
		];
	}
	

	/**
     * return view page fields of the model.
     * 
     * @return array
     */
	public static function viewFields(){
		return [ 
			"role_id", 
			"role_name",
			"role_sso_id"
		];
	}
	

	/**
     * return exportView page fields of the model.
     * 
     * @return array
     */
	public static function exportViewFields(){
		return [ 
			"role_id", 
			"role_name",
			"role_sso_id"
		];
	}
	

	/**
     * return edit page fields of the model.
     * 
     * @return array
     */
	public static function editFields(){
		return [ 
			"role_id", 
			"role_name",
			"role_sso_id"
		];
	}
	

	/**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
	public $timestamps = false;
}
