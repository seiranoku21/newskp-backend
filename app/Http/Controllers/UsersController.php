<?php 
namespace App\Http\Controllers;
use App\Http\Controllers\Controller;
use App\Http\Requests\UsersAccountEditRequest;
use App\Http\Requests\UsersAddRequest;
use App\Http\Requests\UsersEditRequest;
use App\Models\Users;
use Illuminate\Http\Request;
use Exception;
class UsersController extends Controller
{
	

	/**
     * List table records
	 * @param  \Illuminate\Http\Request
     * @param string $fieldname //filter records by a table field
     * @param string $fieldvalue //filter value
     * @return \Illuminate\View\View
     */
	function index(Request $request, $fieldname = null , $fieldvalue = null){
		$query = Users::query();
		if($request->search){
			$search = trim($request->search);
			Users::search($query, $search);
		}
		$orderby = $request->orderby ?? "users.user_id";
		$ordertype = $request->ordertype ?? "desc";
		$query->orderBy($orderby, $ordertype);
		$query->where("users.user_id", auth()->user()->user_id);
		if($fieldname){
			$query->where($fieldname , $fieldvalue); //filter by a single field name
		}
		$records = $this->paginate($query, Users::listFields());
		return $this->respond($records);
	}
	

	/**
     * Select table record by ID
	 * @param string $rec_id
     * @return \Illuminate\View\View
     */
	function view($rec_id = null){
		$query = Users::query();
		$query->where("users.user_id", auth()->user()->user_id);
		$record = $query->findOrFail($rec_id, Users::viewFields());
		return $this->respond($record);
	}
	

	/**
     * Save form record to the table
     * @return \Illuminate\Http\Response
     */
	function add(UsersAddRequest $request){
		$modeldata = $request->validated();
		
		if( array_key_exists("photo", $modeldata) ){
			//move uploaded file from temp directory to destination directory
			$fileInfo = $this->moveUploadedFiles($modeldata['photo'], "photo");
			$modeldata['photo'] = $fileInfo['filepath'];
		}
		$modeldata['password'] = bcrypt($modeldata['password']);
		
		//save Users record
		$record = Users::create($modeldata);
		$record->assignRole("Admin"); //set default role for user
		$rec_id = $record->user_id;
		return $this->respond($record);
	}
	

	/**
     * Update table record with form data
	 * @param string $rec_id //select record by table primary key
     * @return \Illuminate\View\View;
     */
	function edit(UsersEditRequest $request, $rec_id = null){
		$query = Users::query();
		$query->where("users.user_id", auth()->user()->user_id);
		$record = $query->findOrFail($rec_id, Users::editFields());
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
	

	/**
     * Delete record from the database
	 * Support multi delete by separating record id by comma.
	 * @param  \Illuminate\Http\Request
	 * @param string $rec_id //can be separated by comma 
     * @return \Illuminate\Http\Response
     */
	function delete(Request $request, $rec_id = null){
		$arr_id = explode(",", $rec_id);
		$query = Users::query();
		$query->where("users.user_id", auth()->user()->user_id);
		$query->whereIn("user_id", $arr_id);
		$query->delete();
		return $this->respond($arr_id);
	}
}
