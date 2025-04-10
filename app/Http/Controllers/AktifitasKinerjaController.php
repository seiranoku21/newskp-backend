<?php 
namespace App\Http\Controllers;
use App\Http\Controllers\Controller;
use App\Http\Requests\AktifitasKinerjaAddRequest;
use App\Http\Requests\AktifitasKinerjaEditRequest;
use App\Http\Requests\AktifitasKinerjaEditVrfRequest;
use App\Models\AktifitasKinerja;
use Illuminate\Http\Request;
use Exception;
class AktifitasKinerjaController extends Controller
{
	

	/**
     * List table records
	 * @param  \Illuminate\Http\Request
     * @param string $fieldname //filter records by a table field
     * @param string $fieldvalue //filter value
     * @return \Illuminate\View\View
     */
	function index(Request $request, $fieldname = null , $fieldvalue = null){
		$query = AktifitasKinerja::query();
		$query->where("aktifitas_kinerja.nip", "=", $request->user_nip);
		$query->leftJoin("rencana_hasil_kerja_item", "aktifitas_kinerja.rhki_id", "=", "rencana_hasil_kerja_item.id");
		$query->select("aktifitas_kinerja.*", "rencana_hasil_kerja_item.kegiatan as kegiatan");

		// Filter jika ada request rentang waktu
		if($request->tanggal_mulai && $request->tanggal_selesai) {
			$query->whereBetween('aktifitas_kinerja.tanggal_mulai', [
				$request->tanggal_mulai,
				$request->tanggal_selesai
			]);
		}

		if($request->search){
			$search = trim($request->search);
			AktifitasKinerja::search($query, $search);
		}
		$orderby = $request->orderby ?? "aktifitas_kinerja.tanggal_mulai";
		$ordertype = $request->ordertype ?? "desc";
		$query->orderBy($orderby, $ordertype);
		if($fieldname){
			$query->where($fieldname , $fieldvalue); //filter by a single field name
		}
		$records = $this->paginate($query, AktifitasKinerja::listFields());
		return $this->respond($records);
	}
	

	/**
     * Select table record by ID
	 * @param string $rec_id
     * @return \Illuminate\View\View
     */
	function view($rec_id = null){
		$query = AktifitasKinerja::query();
		$record = $query->findOrFail($rec_id, AktifitasKinerja::viewFields());
		return $this->respond($record);
	}
	

	/**
     * Save form record to the table
     * @return \Illuminate\Http\Response
     */
	function add(AktifitasKinerjaAddRequest $request){
		$modeldata = $request->validated();
		
		if( array_key_exists("dokumen", $modeldata) ){
			//move uploaded file from temp directory to destination directory
			$fileInfo = $this->moveUploadedFiles($modeldata['dokumen'], "dokumen");
			$modeldata['dokumen'] = $fileInfo['filepath'];
		}
		
		if( array_key_exists("gambar", $modeldata) ){
			//move uploaded file from temp directory to destination directory
			$fileInfo = $this->moveUploadedFiles($modeldata['gambar'], "gambar");
			$modeldata['gambar'] = $fileInfo['filepath'];
		}
		
		//save AktifitasKinerja record
		$record = AktifitasKinerja::create($modeldata);
		$rec_id = $record->id;
		return $this->respond($record);
	}
	

	/**
     * Update table record with form data
	 * @param string $rec_id //select record by table primary key
     * @return \Illuminate\View\View;
     */
	function edit(AktifitasKinerjaEditRequest $request, $rec_id = null){
		$query = AktifitasKinerja::query();
		$record = $query->findOrFail($rec_id, AktifitasKinerja::editFields());
		if ($request->isMethod('post')) {
			$modeldata = $request->validated();
		
		if( array_key_exists("dokumen", $modeldata) ){
			//move uploaded file from temp directory to destination directory
			$fileInfo = $this->moveUploadedFiles($modeldata['dokumen'], "dokumen");
			$modeldata['dokumen'] = $fileInfo['filepath'];
		}
		
		if( array_key_exists("gambar", $modeldata) ){
			//move uploaded file from temp directory to destination directory
			$fileInfo = $this->moveUploadedFiles($modeldata['gambar'], "gambar");
			$modeldata['gambar'] = $fileInfo['filepath'];
		}
			$record->update($modeldata);
		}
		return $this->respond($record);
	}

	// Untuk Verifikasi lebih dari 1 ( Array )
	function editvrf(AktifitasKinerjaEditVrfRequest $request, $rec_id = null){
		$arr_id = explode(",", $rec_id);
		$query = AktifitasKinerja::query();
		$query->whereIn("id", $arr_id);
		$records = $query->get(AktifitasKinerja::editvrfFields());

		// $xmodel = new AglobalController();
		// $total_poin = $xmodel->get_poin_aktifitas($request->nip, $request->tanggal_mulai, $request->tanggal_selesai);

		if ($request->isMethod('post')) {
			$modeldata = $request->validated();
			foreach($records as $record) {
				$record->update($modeldata);
			}

			// Update the 'poin' field in the 'skp_kontrak' table
			// DB::table('skp_kontrak')
			// 	->where('nip', $request->nip)
			// 	->whereBetween('tanggal_mulai', [$request->tanggal_mulai, $request->tanggal_selesai])
			// 	->update(['poin' => $total_poin]);
		}
		return $this->respond($records);
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
		$query = AktifitasKinerja::query();
		$query->whereIn("id", $arr_id);
		$query->delete();
		return $this->respond($arr_id);
	}

}
