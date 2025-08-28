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

	function list_rhki(Request $request){
		$portofolio_kinerja_uid = $request->portofolio_kinerja_uid;
		$data = \DB::table('rencana_hasil_kerja_item')
				->where('portofolio_kinerja_uid',$portofolio_kinerja_uid)
				->get();
		return $data;
	}

	function tambah_rhki(Request $request){
		// Validasi input
		$validated = $request->validate([
			'portofolio_kinerja_uid' => 'nullable|string',
			'rhka_id' => 'required|string',
			'nip' => 'required|string',
			'kegiatan' => 'nullable|string',
			'aspek_kuantitas' => 'nullable|string',
			'aspek_kualitas' => 'nullable|string',
			'aspek_waktu' => 'nullable|string',
			'ukuran_keberhasilan' => 'nullable|string',
			'realisasi' => 'nullable|string',
		]);

		try {
			$record = \App\Models\RencanaHasilKerjaItem::create($validated);
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

	function ubah_rhki(Request $request, $rec_id = null){
		// Validasi input
		$validated = $request->validate([
			'kegiatan' => 'sometimes|nullable|string',
			'aspek_kuantitas' => 'sometimes|nullable|string',
			'aspek_kualitas' => 'sometimes|nullable|string',
			'aspek_waktu' => 'sometimes|nullable|string',
			'ukuran_keberhasilan' => 'sometimes|nullable|string',
			'realisasi' => 'sometimes|nullable|string',
		]);

		try {
			if (!$rec_id) {
				return response()->json([
					'success' => false,
					'message' => 'ID data tidak ditemukan.',
				], 400);
			}

			$record = \App\Models\RencanaHasilKerjaItem::findOrFail($rec_id);

			// Hanya update field yang ada di input
			$updateData = [];
			foreach (['kegiatan', 'aspek_kuantitas', 'aspek_kualitas', 'aspek_waktu', 'ukuran_keberhasilan', 'realisasi'] as $field) {
				if (array_key_exists($field, $validated)) {
					$updateData[$field] = $validated[$field];
				}
			}

			if (empty($updateData)) {
				return response()->json([
					'success' => false,
					'message' => 'Tidak ada data yang diubah.',
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
