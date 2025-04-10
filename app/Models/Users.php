<?php 
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;
class Users extends Authenticatable 
{
	use Notifiable, HasApiTokens;
	

	/**
     * The table associated with the model.
     *
     * @var string
     */
	protected $table = 'users';
	

	/**
     * The table primary key field
     *
     * @var string
     */
	protected $primaryKey = 'user_id';
	

	/**
     * Table fillable fields
     *
     * @var array
     */
	protected $fillable = ["username","password","telp","photo","email","prodi_id","user_role_id","name_info"];
	/**
     * Table fields which are not included in select statement
     *
     * @var array
     */
	protected $hidden = ['password', 'token'];
	

	/**
     * Set search query for the model
	 * @param \Illuminate\Database\Eloquent\Builder $query
	 * @param string $text
     */
	public static function search($query, $text){
		//search table record 
		$search_condition = '(
				user_id LIKE ?  OR 
				username LIKE ?  OR 
				telp LIKE ?  OR 
				email LIKE ?  OR 
				name_info LIKE ? 
		)';
		$search_params = [
			"%$text%","%$text%","%$text%","%$text%","%$text%"
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
			"user_id", 
			"username", 
			"telp", 
			"photo", 
			"email", 
			"prodi_id", 
			"user_role_id", 
			"name_info" 
		];
	}
	

	/**
     * return exportList page fields of the model.
     * 
     * @return array
     */
	public static function exportListFields(){
		return [ 
			"user_id", 
			"username", 
			"telp", 
			"photo", 
			"email", 
			"prodi_id", 
			"user_role_id", 
			"name_info" 
		];
	}
	

	/**
     * return view page fields of the model.
     * 
     * @return array
     */
	public static function viewFields(){
		return [ 
			"user_id", 
			"username", 
			"telp", 
			"email", 
			"prodi_id", 
			"user_role_id", 
			"name_info" 
		];
	}
	

	/**
     * return exportView page fields of the model.
     * 
     * @return array
     */
	public static function exportViewFields(){
		return [ 
			"user_id", 
			"username", 
			"telp", 
			"email", 
			"prodi_id", 
			"user_role_id", 
			"name_info" 
		];
	}
	

	/**
     * return accountedit page fields of the model.
     * 
     * @return array
     */
	public static function accounteditFields(){
		return [ 
			"user_id", 
			"username", 
			"telp", 
			"photo", 
			"prodi_id", 
			"user_role_id", 
			"name_info" 
		];
	}
	

	/**
     * return accountview page fields of the model.
     * 
     * @return array
     */
	public static function accountviewFields(){
		return [ 
			"user_id", 
			"username", 
			"telp", 
			"email", 
			"prodi_id", 
			"user_role_id", 
			"name_info" 
		];
	}
	

	/**
     * return exportAccountview page fields of the model.
     * 
     * @return array
     */
	public static function exportAccountviewFields(){
		return [ 
			"user_id", 
			"username", 
			"telp", 
			"email", 
			"prodi_id", 
			"user_role_id", 
			"name_info" 
		];
	}
	

	/**
     * return edit page fields of the model.
     * 
     * @return array
     */
	public static function editFields(){
		return [ 
			"user_id", 
			"username", 
			"telp", 
			"photo", 
			"prodi_id", 
			"user_role_id", 
			"name_info" 
		];
	}
	

	/**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
	public $timestamps = false;
	

	/**
     * Get current user name
     * @return string
     */
	public function UserName(){
		return $this->username;
	}
	

	/**
     * Get current user id
     * @return string
     */
	public function UserId(){
		return $this->user_id;
	}
	public function UserEmail(){
		return $this->email;
	}
	public function UserPhoto(){
		return $this->photo;
	}
	public function UserRole(){
		return $this->user_role_id;
	}
	
	private $roleNames = [];
	private $userPages = [];
	
	/**
	* Get the permissions of the user.
	*/
	public function permissions(){
		return $this->hasMany(Permissions::class, 'role_id', 'user_role_id');
	}
	
	/**
	* Get the roles of the user.
	*/
	public function roles(){
		return $this->hasMany(Roles::class, 'role_id', 'user_role_id');
	}
	
	/**
	* set user role
	*/
	public function assignRole($roleName){
		$roleId = Roles::select('role_id')->where('role_name', $roleName)->value('role_id');
		$this->user_role_id = $roleId;
		$this->save();
	}
	
	/**
     * return list of pages user can access
     * @return array
     */
	public function getUserPages(){
		if(empty($this->userPages)){ // ensure we make db query once
			$this->userPages = $this->permissions()->pluck('permission')->toArray();
		}
		return $this->userPages;
	}
	
	/**
     * return user role names
     * @return array
     */
	public function getRoleNames(){
		if(empty($this->roleNames)){// ensure we make db query once
			$this->roleNames = $this->roles()->pluck('role_name')->toArray();
		}
		return $this->roleNames;
	}
	
	/**
     * check if user has a role
     * @return bool
    */
	public function hasRole($arrRoles){
		if(!is_array($arrRoles)){
			$arrRoles = [$arrRoles];
		}
		$userRoles = $this->getRoleNames();
		if(array_intersect($userRoles, $arrRoles)){
			return true;
		}
		return false;
	}
	
	/**
     * check if user can access page
     * @return bool
     */
	public function canAccess($path){
		$userPages = $this->getUserPages();
		return in_array($path, $userPages);
	}
}
