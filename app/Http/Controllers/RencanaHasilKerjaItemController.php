<?php 
namespace App\Http\Controllers;
use App\Http\Controllers\Controller;
use App\Http\Requests\RencanaHasilKerjaItemAddRequest;
use App\Http\Requests\RencanaHasilKerjaItemEditRequest;
use App\Models\RencanaHasilKerjaItem;
use Illuminate\Http\Request;
use Exception;
class RencanaHasilKerjaItemController extends Controller
{
	

	/**
     * List table records
	 * @param  \Illuminate\Http\Request
     * @param string $fieldname //filter records by a table field
     * @param string $fieldvalue //filter value
     * @return \Illuminate\View\View
     */
	function index(Request $request, $fieldname = null , $fieldvalue = null){
		$query = RencanaHasilKerjaItem::query();
		$query->where("rencana_hasil_kerja_item.nip", "=", $request->user_nip);
		$query->where("rencana_hasil_kerja_item.portofolio_kinerja_uid", "=", $request->poki_uid);
		$query->join("rencana_hasil_kerja_atasan", "rencana_hasil_kerja_item.rhka_id", "=", "rencana_hasil_kerja_atasan.id");
		$query->select("rencana_hasil_kerja_item.*", "rencana_hasil_kerja_atasan.rubrik_kinerja as rubrik_kinerja", "rencana_hasil_kerja_atasan.kategori as kategori");
		if($request->search){
			$search = trim($request->search);
			RencanaHasilKerjaItem::search($query, $search);
		}
		if($request->orderby){
			$orderby = $request->orderby;
			$ordertype = ($request->ordertype ? $request->ordertype : "desc");
			$query->orderBy($orderby, $ordertype);
		}
		else{
			$query->orderBy("rencana_hasil_kerja_item.id", "ASC");
		}
		if($fieldname){
			$query->where($fieldname , $fieldvalue); //filter by a single field name
		}
		$records = $this->paginate($query, RencanaHasilKerjaItem::listFields());
		return $this->respond($records);
	}
	

	/**
     * Save form record to the table
     * @return \Illuminate\Http\Response
     */
	function add(RencanaHasilKerjaItemAddRequest $request){
		$modeldata = $request->validated();
		
		//save RencanaHasilKerjaItem record
		$record = RencanaHasilKerjaItem::create($modeldata);
		$rec_id = $record->id;
		return $this->respond($record);
	}
	

	/**
     * Update table record with form data
	 * @param string $rec_id //select record by table primary key
     * @return \Illuminate\View\View;
     */
	function edit(RencanaHasilKerjaItemEditRequest $request, $rec_id = null){
		$query = RencanaHasilKerjaItem::query();
		$record = $query->findOrFail($rec_id, RencanaHasilKerjaItem::editFields());
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
		$query = RencanaHasilKerjaItem::query();
		$query->whereIn("id", $arr_id);
		$query->delete();
		return $this->respond($arr_id);
	}
}
