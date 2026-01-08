<?php 
namespace App\Http\Controllers;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\Users;
use App\Http\Requests\UsersAccountEditRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;
use App\Models\permissions;
use Exception;
/**
 * Account Page Controller
 * @category  Controller
 */
class AccountController extends Controller{
	
	function index(Request $request){
		$rec_id = Auth::id();
		$query = Users::query();
		$allowedRoles = auth()->user()->hasRole(["admin"]);
		if(!$allowedRoles){
			//check if user is the owner of the record.
			$query->where("users.username", auth()->user()->username);
		}
		// $query->where("users.user_id", auth()->user()->user_id);
		$record = $query->findOrFail($rec_id, Users::accountviewFields());
		return $this->respond($record);
	}
	
	/**
     * Update user account data
     * @return \Illuminate\View\View;
     */
	function edit(UsersAccountEditRequest $request){
		$rec_id = Auth::id();
		$query = Users::query();
		$query->where("users.user_id", auth()->user()->user_id);
		$record = $query->findOrFail($rec_id, Users::accounteditFields());

		if ($request->isMethod('post')) {
			$modeldata = $request->validated();
		
			if( array_key_exists("photo", $modeldata) ){
				//move uploaded file from temp directory to destination directory
				$fileInfo = $this->moveUploadedFiles($modeldata['photo'], "photo");
				$modeldata['photo'] = $fileInfo['filepath'];
			}
			$record->update($modeldata);
		}
		return $this->respond($record);
	}
	// Akses Halaman Jika Login Local
	function currentuserdata(Request $request){

			// For local login
			$user = auth()->user();
			$userPages = $user->getUserPages();
			$userRoleName = $user->getRoleNames();
			$rolesMenu = $user->getRolesMenu();

			$data = [
				"user" => $user,
				"pages" => $userPages,
				"roles" => $userRoleName,
				"roles_menu" => $rolesMenu
			];
		
		return $this->respond($data);
	}

	// Akses Halaman jika login dgn SSO
	function currentuserdata_sso(Request $request){
		
		$get_nip = $request->user_nip;
		$get_user_name = $request->user_name;
		$get_user_email = $request->user_email;
		$get_name_info = $request->name_info;
		$get_user_role_id = (int)$request->role_id;
		$get_role_name = [$request->role_name];

		$user_detail = 
			[
				"user_id" => $get_nip,
				"username" => $get_user_name,
				"email" => $get_user_email,
				"name_info" => $get_name_info,
				"user_role_id" => $get_user_role_id,
			]
		;

		$user_sso = $user_detail;

		$data_akses = DB::table('permissions')->where('role_id',$get_user_role_id)->pluck('permission')->toArray();
		$userPages = $data_akses ; 

		$data_role = DB::table('roles')->where('role_id',$get_user_role_id)->pluck('role_name')->toArray();
		$userRoleName = $data_role;
		
		$data_sso = [
			"user" => $user_sso,
			"pages" => $userPages,
			"roles" => $userRoleName
		];
		return $this->respond($data_sso);
	}

	// Cek Role Lokal & Role SSO
	function sso_role(Request $request){

		$get_sso_group= $request->sso_group;

		$data = DB::table('roles')
					->select('role_id')
					->where('role_sso_id','=',$get_sso_group)
					->first();

		return $this->respond($data);

	}

	// ---SIMPEG LOGIN AUTH
	
	// Cek Role Lokal & Role SPL (Simpeg Login)
	function spl_role(Request $request){

		$get_spl_id= $request->spl_id;

		$data = DB::table('roles')
					->select('role_id')
					->where('role_simpeg_id','=',$get_spl_id)
					->first();

		return $this->respond($data);

	}

	// Akses Halaman jika login dgn SSO
	function currentuserdata_spl(Request $request){
		
		$get_nip = $request->user_nip;
		$get_user_name = $request->user_name;
		$get_user_email = $request->user_email;
		$get_name_info = $request->name_info;
		$get_user_role_id = (int)$request->role_id;
		$get_role_name = [$request->role_name];

		$user_detail = 
			[
				"user_id" => $get_nip,
				"username" => $get_user_name,
				"email" => $get_user_email,
				"name_info" => $get_name_info,
				"user_role_id" => $get_user_role_id,
			]
		;

		$user_spl = $user_detail;

		$data_akses = DB::table('permissions')->where('role_id',$get_user_role_id)->pluck('permission')->toArray();
		$userPages = $data_akses ; 

		$data_role = DB::table('roles')->where('role_id',$get_user_role_id)->pluck('role_name')->toArray();
		$userRoleName = $data_role;
		
		$data_spl = [
			"user" => $user_spl,
			"pages" => $userPages,
			"roles" => $userRoleName
		];
		return $this->respond($data_spl);
	}

    // Get Photo from Sikita API
    function get_photo(Request $request) {
        $email = $request->email;
        
        $client = new \GuzzleHttp\Client();
        
        try {
            $response = $client->request('GET', "https://api-sikita.untirta.ac.id/apiv2/get_pegawai?email={$email}", [
                'headers' => [
                    'api-key' => 'SikitaUntirtaJawara'
                ]
            ]);
			
            $data = json_decode($response->getBody(), true);
            return $this->respond($data);
            
        } catch (\Exception $e) {
            return $this->reject("Failed to fetch photo: " . $e->getMessage(), 500);
        }
    }


}
