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
        
        if ($request->isMethod('post') || $request->isMethod('put') || $request->isMethod('patch')) {
            // Cek apakah aktifitas sudah dinilai (poin > 0)
            if ($record->poin > 0) {
                return response()->json([
                    'success' => false,
                    'message' => "Aktifitas ini tidak dapat diubah karena sudah mendapat penilaian (Poin: {$record->poin})"
                ], 403);
            }
            
            $modeldata = $request->validated();

            // Support direct multipart file uploads for PUT/PATCH/POST
            if ($request->hasFile('dokumen')) {
                $file = $request->file('dokumen');
                $filename = uniqid('dokumen_') . '.' . $file->getClientOriginalExtension();
                $destinationPath = public_path('uploads/files/');
                if (!file_exists($destinationPath)) {
                    mkdir($destinationPath, 0777, true);
                }
                $file->move($destinationPath, $filename);
                $modeldata['dokumen'] = 'uploads/files/' . $filename;
            } elseif (array_key_exists('dokumen', $modeldata) && !empty($modeldata['dokumen'])) {
                // Move uploaded file from temp directory to destination directory (legacy flow)
                $fileInfo = $this->moveUploadedFiles($modeldata['dokumen'], 'dokumen');
                $modeldata['dokumen'] = $fileInfo['filepath'];
            }

            if ($request->hasFile('gambar')) {
                $file = $request->file('gambar');
                $filename = uniqid('gambar_') . '.' . $file->getClientOriginalExtension();
                $destinationPath = public_path('uploads/files/gambar/');
                if (!file_exists($destinationPath)) {
                    mkdir($destinationPath, 0777, true);
                }
                $file->move($destinationPath, $filename);
                $modeldata['gambar'] = 'uploads/files/gambar/' . $filename;
            } elseif (array_key_exists('gambar', $modeldata) && !empty($modeldata['gambar'])) {
                // Move uploaded file from temp directory to destination directory (legacy flow)
                $fileInfo = $this->moveUploadedFiles($modeldata['gambar'], 'gambar');
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
		
		// Cek apakah ada aktifitas yang sudah dinilai (poin > 0)
		$recordsWithPoin = AktifitasKinerja::whereIn("id", $arr_id)
			->where('poin', '>', 0)
			->get();
			
		if ($recordsWithPoin->count() > 0) {
			$poinList = $recordsWithPoin->pluck('poin')->join(', ');
			return response()->json([
				'success' => false,
				'message' => "Tidak dapat menghapus aktifitas yang sudah dinilai (Poin: {$poinList})"
			], 403);
		}
		
		// Jika lolos validasi, hapus record
		$query = AktifitasKinerja::query();
		$query->whereIn("id", $arr_id);
		$query->delete();
		return $this->respond($arr_id);
	}

	function tambah_aktifitas(Request $request){
		// Validasi input
		$validated = $request->validate([
			'nip' => 'required|string',
			'rhki_id' => 'required|integer',
			'tanggal_mulai' => 'required|date',
			'tanggal_selesai' => 'nullable|date',
			'jumlah' => 'required|numeric',
			'satuan' => 'nullable|string',
			'gambar' => 'nullable|file|mimes:jpg,jpeg,png|max:3072', // 3MB
			'dokumen' => 'nullable|file|mimes:pdf|max:3072', // 3MB
			'tautan' => 'nullable|string',
			'portofolio_kinerja_uid' => 'nullable'
		]);

		// Ambil rhka_id dan portofolio_kinerja_uid dari rhki_id
		$rhka_id = \DB::table('rencana_hasil_kerja_item')->where('id', $validated['rhki_id'])->value('rhka_id');
		$portofolio_kinerja_uid_from_rhki = \DB::table('rencana_hasil_kerja_item')->where('id', $validated['rhki_id'])->value('portofolio_kinerja_uid');

		// Gunakan portofolio_kinerja_uid dari input jika ada, jika tidak dari rhki_id
		$portofolio_kinerja_uid = $validated['portofolio_kinerja_uid'] ?? $portofolio_kinerja_uid_from_rhki;

		// Ambil tahun jika portofolio_kinerja_uid tersedia, jika tidak null
		$tahun = null;
		if (!empty($portofolio_kinerja_uid)) {
			$tahun = \DB::table('portofolio_kinerja')->where('uid', $portofolio_kinerja_uid)->value('tahun');
		}

		$data = [
			'nip' => $validated['nip'],
			'rhki_id' => $validated['rhki_id'],
			'rhka_id' => $rhka_id,
			'portofolio_kinerja_uid' => $portofolio_kinerja_uid,
			'tahun' => $tahun,
			'tanggal_mulai' => $validated['tanggal_mulai'],
			'tanggal_selesai' => $validated['tanggal_selesai'] ?? null,
			'jumlah' => $validated['jumlah'],
			'satuan' => $validated['satuan'] ?? null,
			'gambar' => null,
			'dokumen' => null,
			'tautan' => $validated['tautan'] ?? null,
		];

		// Proses upload gambar jika ada
		if ($request->hasFile('gambar')) {
			$file = $request->file('gambar');
			$filename = uniqid('gambar_') . '.' . $file->getClientOriginalExtension();
			$destinationPath = public_path('uploads/files/gambar/');
			if (!file_exists($destinationPath)) {
				mkdir($destinationPath, 0777, true);
			}
			$file->move($destinationPath, $filename);
			$data['gambar'] = 'uploads/files/gambar/' . $filename;
		}

		// Proses upload dokumen jika ada
		if ($request->hasFile('dokumen')) {
			$file = $request->file('dokumen');
			$filename = uniqid('dokumen_') . '.' . $file->getClientOriginalExtension();
			$destinationPath = public_path('uploads/files/');
			if (!file_exists($destinationPath)) {
				mkdir($destinationPath, 0777, true);
			}
			$file->move($destinationPath, $filename);
			$data['dokumen'] = 'uploads/files/' . $filename;
		}

		// Insert ke database
		$aktifitas = \App\Models\AktifitasKinerja::create($data);

		return response()->json([
			'success' => true,
			'message' => 'Data aktifitas kinerja berhasil ditambahkan',
			'data' => $aktifitas
		], 201);
	}

}
