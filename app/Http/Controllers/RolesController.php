<?php 
namespace App\Http\Controllers;
use App\Http\Controllers\Controller;
use App\Http\Requests\RolesAddRequest;
use App\Http\Requests\RolesEditRequest;
use App\Models\Roles;
use Illuminate\Http\Request;
use Exception;
class RolesController extends Controller
{
	

	/**
     * List table records
	 * @param  \Illuminate\Http\Request
     * @param string $fieldname //filter records by a table field
     * @param string $fieldvalue //filter value
     * @return \Illuminate\View\View
     */
	function index(Request $request, $fieldname = null , $fieldvalue = null){
		$query = Roles::query();
		if($request->search){
			$search = trim($request->search);
			Roles::search($query, $search);
		}
		$orderby = $request->orderby ?? "roles.role_id";
		$ordertype = $request->ordertype ?? "desc";
		$query->orderBy($orderby, $ordertype);
		if($fieldname){
			$query->where($fieldname , $fieldvalue); //filter by a single field name
		}
		$records = $this->paginate($query, Roles::listFields());
		return $this->respond($records);
	}
	

	/**
     * Select table record by ID
	 * @param string $rec_id
     * @return \Illuminate\View\View
     */
	function view($rec_id = null){
		$query = Roles::query();
		$record = $query->findOrFail($rec_id, Roles::viewFields());
		return $this->respond($record);
	}
	

	/**
     * Save form record to the table
     * @return \Illuminate\Http\Response
     */
	function add(RolesAddRequest $request){
		$modeldata = $request->validated();
		
		//save Roles record
		$record = Roles::create($modeldata);
		$rec_id = $record->role_id;
		return $this->respond($record);
	}
	

	/**
     * Update table record with form data
	 * @param string $rec_id //select record by table primary key
     * @return \Illuminate\View\View;
     */
	function edit(RolesEditRequest $request, $rec_id = null){
		$query = Roles::query();
		$record = $query->findOrFail($rec_id, Roles::editFields());
		if ($request->isMethod('post')) {
			$modeldata = $request->validated();
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
		$query = Roles::query();
		$query->whereIn("role_id", $arr_id);
		$query->delete();
		return $this->respond($arr_id);
	}
}
