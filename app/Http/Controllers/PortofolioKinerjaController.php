<?php 
namespace App\Http\Controllers;
use App\Http\Controllers\Controller;
use App\Http\Requests\PortofolioKinerjaAddRequest;
use App\Http\Requests\PortofolioKinerjaEditRequest;
use App\Models\PortofolioKinerja;
use Illuminate\Http\Request;
use Exception;
class PortofolioKinerjaController extends Controller
{
	

	/**
     * List table records
	 * @param  \Illuminate\Http\Request
     * @param string $fieldname //filter records by a table field
     * @param string $fieldvalue //filter value
     * @return \Illuminate\View\View
     */
	function index(Request $request, $fieldname = null , $fieldvalue = null){
		$query = PortofolioKinerja::query();
		$query->where("portofolio_kinerja.nip", "=", $request->nip);
		if($request->search){
			$search = trim($request->search);
			PortofolioKinerja::search($query, $search);
		}
		$orderby = $request->orderby ?? "portofolio_kinerja.id";
		$ordertype = $request->ordertype ?? "desc";
		$query->orderBy($orderby, $ordertype);
		if($fieldname){
			$query->where($fieldname , $fieldvalue); //filter by a single field name
		}
		$records = $this->paginate($query, PortofolioKinerja::listFields());
		return $this->respond($records);
	}
	

	/**
     * Select table record by ID
	 * @param string $rec_id
     * @return \Illuminate\View\View
     */
	function view($rec_id = null){
		$query = PortofolioKinerja::query();
		$record = $query->findOrFail($rec_id, PortofolioKinerja::viewFields());
		return $this->respond($record);
	}
	

	/**
     * Save form record to the table
     * Unik berdasarkan: nip, tahun, jabatan, unit_kerja, status_kerja.
     * @return \Illuminate\Http\Response
     */
	function add(PortofolioKinerjaAddRequest $request){
		$modeldata = $request->validated();
		$modeldata['uid'] = guidv4();

		// Pengecekan duplikat: indexes (nip, tahun, jabatan, unit_kerja, status_kerja) harus unik
		$exists = PortofolioKinerja::query()
			->where('nip', $modeldata['nip'] ?? '')
			->where('tahun', $modeldata['tahun'] ?? '')
			->where('jabatan', $modeldata['jabatan'] ?? '')
			->where('unit_kerja', $modeldata['unit_kerja'] ?? '')
			->where('status_kerja', $modeldata['status_kerja'] ?? '')
			->exists();
		if ($exists) {
			return response()->json([
				'message' => 'Portofolio Sudah Dibuat Sebelumnya dengan Tahun, Jabatan, Unit Kerja serta Status kerja yang sama.',
			], 422);
		}
		
		//save PortofolioKinerja record
		$record = PortofolioKinerja::create($modeldata);
		$rec_id = $record->id;
		return $this->respond($record);
	}
	

	/**
     * Update table record with form data
	 * @param string $rec_id //select record by table primary key
     * @return \Illuminate\View\View;
     */
	function edit(PortofolioKinerjaEditRequest $request, $rec_id = null){
		$query = PortofolioKinerja::query();
		$record = $query->findOrFail($rec_id, array_merge(PortofolioKinerja::editFields(), ['nip', 'tahun', 'status_kerja']));
		if ($request->isMethod('post')) {
			$modeldata = $request->validated();
			$nip = $record->nip;
			$tahun = $record->tahun;
			$jabatan = $modeldata['jabatan'] ?? $record->jabatan;
			$unit_kerja = $modeldata['unit_kerja'] ?? $record->unit_kerja;
			$status_kerja = $record->status_kerja;
			$exists = PortofolioKinerja::query()
				->where('nip', $nip)
				->where('tahun', $tahun)
				->where('jabatan', $jabatan ?? '')
				->where('unit_kerja', $unit_kerja ?? '')
				->where('status_kerja', $status_kerja ?? '')
				->where('id', '!=', $rec_id)
				->exists();
			if ($exists) {
				return response()->json([
					'message' => 'Portofolio Sudah Dibuat Sebelumnya dengan Tahun, Jabatan, Unit Kerja serta Status kerja yang sama.',
				], 422);
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
		$query = PortofolioKinerja::query();
		$query->whereIn("id", $arr_id);
		$query->delete();
		return $this->respond($arr_id);
	}
}
