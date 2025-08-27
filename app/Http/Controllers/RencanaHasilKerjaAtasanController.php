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

	function list_rhka(Request $request){
		$portofolio_kinerja_uid = $request->portofolio_kinerja_uid;
		$data = \DB::table('rencana_hasil_kerja_atasan')
				->where('portofolio_kinerja_uid',$portofolio_kinerja_uid)
				->get();
		return $data;
	}

 	function tambah_rhka(Request $request){
		// Validasi input
		$validated = $request->validate([
			'portofolio_kinerja_uid' => 'required|string',
			'nip' => 'required|string',
			'rubrik_kinerja' => 'required|string',
			'kategori' => 'required|in:utama,tambahan',
		]);

		try {
			$record = \App\Models\RencanaHasilKerjaAtasan::create($validated);
			return response()->json([
				'success' => true,
				'message' => 'Data berhasil ditambahkan',
				'data' => $record
			], 201);
		} catch (\Exception $e) {
			return response()->json([
				'success' => false,
				'message' => 'Gagal menambahkan data: ' . $e->getMessage(),
			], 500);
		}
	}

	function ubah_rhka(Request $request, $rec_id = null){
		// Validasi input
		$validated = $request->validate([
			'rubrik_kinerja' => 'sometimes|required|string',
			'kategori' => 'sometimes|required|in:utama,tambahan',
		]);

		try {
			if (!$rec_id) {
				return response()->json([
					'success' => false,
					'message' => 'ID data tidak ditemukan.',
				], 400);
			}

			$record = \App\Models\RencanaHasilKerjaAtasan::findOrFail($rec_id);

			// Hanya update rubrik_kinerja dan/atau kategori jika ada di input
			$updateData = [];
			if (array_key_exists('rubrik_kinerja', $validated)) {
				$updateData['rubrik_kinerja'] = $validated['rubrik_kinerja'];
			}
			if (array_key_exists('kategori', $validated)) {
				$updateData['kategori'] = $validated['kategori'];
			}

			if (empty($updateData)) {
				return response()->json([
					'success' => false,
					'message' => 'Tidak ada data yang diubah. Hanya rubrik_kinerja atau kategori yang dapat diubah.',
				], 400);
			}

			$record->update($updateData);

			return response()->json([
				'success' => true,
				'message' => 'Data berhasil diubah',
				'data' => $record
			], 200);
		} catch (\Exception $e) {
			return response()->json([
				'success' => false,
				'message' => 'Gagal mengubah data: ' . $e->getMessage(),
			], 500);
		}
	}

}
