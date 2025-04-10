<?php 
namespace App\Http\Controllers;
use App\Http\Controllers\Controller;
use App\Http\Requests\RencanaHasilKerjaAtasanAddRequest;
use App\Http\Requests\RencanaHasilKerjaAtasanEditRequest;
use App\Models\RencanaHasilKerjaAtasan;
use Illuminate\Http\Request;
use Exception;
class RencanaHasilKerjaAtasanController extends Controller
{
	

	/**
     * List table records
	 * @param  \Illuminate\Http\Request
     * @param string $fieldname //filter records by a table field
     * @param string $fieldvalue //filter value
     * @return \Illuminate\View\View
     */
	function index(Request $request, $fieldname = null , $fieldvalue = null){
		$query = RencanaHasilKerjaAtasan::query();
		$query->where("rencana_hasil_kerja_atasan.nip", "=", $request->user_nip);
		$query->where("rencana_hasil_kerja_atasan.portofolio_kinerja_uid", "=", $request->poki_uid);
		if($request->search){
			$search = trim($request->search);
			RencanaHasilKerjaAtasan::search($query, $search);
		}
		if($request->orderby){
			$orderby = $request->orderby;
			$ordertype = ($request->ordertype ? $request->ordertype : "desc");
			$query->orderBy($orderby, $ordertype);
		}
		else{
			$query->orderBy("rencana_hasil_kerja_atasan.id", "ASC");
		}
		if($fieldname){
			$query->where($fieldname , $fieldvalue); //filter by a single field name
		}
		$records = $this->paginate($query, RencanaHasilKerjaAtasan::listFields());
		return $this->respond($records);
	}
	

	/**
     * Insert multiple rows into the database table
     * @return \Illuminate\Http\Response
     */
	function add(RencanaHasilKerjaAtasanAddRequest $request){
		$postdata = $request->all();
		$records = [];
		foreach($postdata as &$modeldata){
			$record = RencanaHasilKerjaAtasan::create($modeldata);
			$records[] = $record;
		}
		return $this->respond($records);
	}
	

	/**
     * Update table record with form data
	 * @param string $rec_id //select record by table primary key
     * @return \Illuminate\View\View;
     */
	function edit(RencanaHasilKerjaAtasanEditRequest $request, $rec_id = null){
		$query = RencanaHasilKerjaAtasan::query();
		$record = $query->findOrFail($rec_id, RencanaHasilKerjaAtasan::editFields());
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
		$query = RencanaHasilKerjaAtasan::query();
		$query->whereIn("id", $arr_id);
		$query->delete();
		return $this->respond($arr_id);
	}
}
