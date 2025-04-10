<?php 
namespace App\Http\Controllers;
use App\Http\Controllers\Controller;
use App\Http\Requests\PerilakuKerjaAddRequest;
use App\Http\Requests\PerilakuKerjaEditRequest;
use App\Models\PerilakuKerja;
use Illuminate\Http\Request;
use Exception;
class PerilakuKerjaController extends Controller
{
	

	/**
     * List table records
	 * @param  \Illuminate\Http\Request
     * @param string $fieldname //filter records by a table field
     * @param string $fieldvalue //filter value
     * @return \Illuminate\View\View
     */
	function index(Request $request, $fieldname = null , $fieldvalue = null){
		$query = PerilakuKerja::query();
		if($request->search){
			$search = trim($request->search);
			PerilakuKerja::search($query, $search);
		}
		if($request->orderby){
			$orderby = $request->orderby;
			$ordertype = ($request->ordertype ? $request->ordertype : "desc");
			$query->orderBy($orderby, $ordertype);
		}
		else{
			$query->orderBy("perilaku_kerja.id", "ASC");
		}
		if($fieldname){
			$query->where($fieldname , $fieldvalue); //filter by a single field name
		}
		$records = $this->paginate($query, PerilakuKerja::listFields());
		return $this->respond($records);
	}
	

	/**
     * Save form record to the table
     * @return \Illuminate\Http\Response
     */
	function add(PerilakuKerjaAddRequest $request){
		$modeldata = $request->validated();
		
		$arr_kode = explode(",", $modeldata['perilaku_kerja_kode']);

		$records = [];
		foreach($arr_kode as $kode) {
			$data = [
				'uid' => $modeldata['uid'],
				'perilaku_kerja_kode' => $kode,
				'ekspektasi_pimpinan' => $modeldata['ekspektasi_pimpinan']
			];
			$record = PerilakuKerja::create($data);
			$records[] = $record;
		}
		return $this->respond($records);
	}

	/**
     * Update table record with form data
	 * @param string $rec_id //select record by table primary key
     * @return \Illuminate\View\View;
     */
	function edit(PerilakuKerjaEditRequest $request, $rec_id = null){
		$query = PerilakuKerja::query();
		$record = $query->findOrFail($rec_id, PerilakuKerja::editFields());
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
		$query = PerilakuKerja::query();
		$query->whereIn("id", $arr_id);
		$query->delete();
		return $this->respond($arr_id);
	}
}
