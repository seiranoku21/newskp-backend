<?php 
namespace App\Http\Controllers;
use App\Http\Controllers\Controller;
use App\Http\Requests\SkpKontrakAddRequest;
use App\Http\Requests\SkpKontrakEditRequest;
use App\Http\Requests\SkpKontrakStatusRequest;
use App\Models\SkpKontrak;
use Illuminate\Http\Request;
use Exception;
class SkpKontrakController extends Controller
{
	
	function index(Request $request, $fieldname = null , $fieldvalue = null){
		$query = SkpKontrak::query();
		$query->where("skp_kontrak.pegawai_nip", "=", $request->user_nip);
		if($request->search){
			$search = trim($request->search);
			SkpKontrak::search($query, $search);
		}
		$query->leftJoin("ref_status", "skp_kontrak.status_id", "=", "ref_status.id");
		$query->join("ref_status_vrf", "skp_kontrak.status_vrf_id", "=", "ref_status_vrf.id");
		$query->join("ref_periode", "skp_kontrak.periode_id", "=", "ref_periode.id");
		$orderby = $request->orderby ?? "skp_kontrak.id";
		$ordertype = $request->ordertype ?? "desc";
		$query->orderBy($orderby, $ordertype);
		if($fieldname){
			$query->where($fieldname , $fieldvalue); //filter by a single field name
		}
		$records = $this->paginate($query, SkpKontrak::listFields());
		return $this->respond($records);
	}
	
	function view($rec_id = null){
		
		$query = SkpKontrak::query();
		$query->leftJoin("ref_status", "skp_kontrak.status_id", "=", "ref_status.id");
		$query->join("ref_status_vrf", "skp_kontrak.status_vrf_id", "=", "ref_status_vrf.id");
		$query->join("ref_periode", "skp_kontrak.periode_id", "=", "ref_periode.id");
		$record = $query->where('skp_kontrak.uid', $rec_id)->firstOrFail(SkpKontrak::viewFields());
		return $this->respond($record);
	}

	function add(SkpKontrakAddRequest $request){
		$modeldata = $request->validated();
		$modeldata['uid'] = guidv4();
		$modeldata['status_id'] = "1";
		$modeldata['status_vrf_id'] = "1";
		//save SkpKontrak record
		$record = SkpKontrak::create($modeldata);
		$rec_id = $record->id;
		return $this->respond($record);
	}
	
	function edit(SkpKontrakEditRequest $request, $rec_id = null){
		$query = SkpKontrak::query();
		$record = $query->findOrFail($rec_id, SkpKontrak::editFields());
		if ($request->isMethod('post')) {
			$modeldata = $request->validated();
			$record->update($modeldata);
		}
		return $this->respond($record);
	}

	function delete(Request $request, $rec_id = null){
		$arr_id = explode(",", $rec_id);
		$query = SkpKontrak::query();
		$query->whereIn("id", $arr_id);
		$query->delete();
		return $this->respond($arr_id);
	}
	
	function status(SkpKontrakStatusRequest $request, $rec_id = null){
		$query = SkpKontrak::query();
		$record = $query->findOrFail($rec_id, SkpKontrak::statusFields());
		if ($request->isMethod('post')) {
			$modeldata = $request->validated();
			$record->update($modeldata);
		}
		return $this->respond($record);
	}

	function tambah_ajuan(Request $request){
		// Params
		$portofolio_id	 = $request->portofolio_id;
		$periode_id		 = $request->periode_id;
		$penilai_nip	 = $request->penilai_nip;

		// Cek portofolio_id di tabel portofolio_kinerja
		$portofolio_exists = \DB::table('portofolio_kinerja')->where('id', $portofolio_id)->exists();
		if (!$portofolio_exists) {
			return response()->json([
				'success' => false,
				'message' => 'Portofolio ID tidak ditemukan !'
			], 404);
		}

		// Tabel Data
		$tab_portofolio  = \DB::table('portofolio_kinerja')->where('id', $portofolio_id);
		$tab_periode 	 = \DB::table('ref_periode')->where('id', $periode_id);
		// Value dari Tabel Data
		$nip = $tab_portofolio->value('nip');
		$tahun = $tab_portofolio->value('tahun');
		$periode = $tab_periode->value('periode');
		$bln_mulai = $tab_periode->value('bln_mulai');
		$bln_selesai = $tab_periode->value('bln_selesai');
		$periode_mulai = date('Y-m-d', strtotime($tahun . '-' . $bln_mulai. '-01'));
		$periode_selesai = date('Y-m-d', strtotime($tahun . '-' . $bln_selesai . '-' . date('t', strtotime($tahun . '-' .$bln_selesai. '-01'))));
		// Pegawai
		$pegawai_email = $tab_portofolio->value('email');
		$pegawai_nama = $tab_portofolio->value('nama');
		$pegawai_pangkat_id = $tab_portofolio->value('pangkat_id');
		$pegawai_pangkat = $tab_portofolio->value('pangkat');
		$pegawai_jabatan_id = $tab_portofolio->value('jabatan_struktural_id');
		$pegawai_jabatan = $tab_portofolio->value('jabatan_struktural');
		$pegawai_jabatan_fungsional_id = $tab_portofolio->value('jabatan_fungsional_id');
		$pegawai_jabatan_fungsional = $tab_portofolio->value('jabatan_fungsional');
		$pegawai_jab_all = '';
			// Cek apakah jabatan struktural ada (tidak 0 dan tidak null)
			$has_struktural = !empty($pegawai_jabatan_id) && $pegawai_jabatan_id != 0;
			// Cek apakah jabatan fungsional ada (tidak 0 dan tidak null)
			$has_fungsional = !empty($pegawai_jabatan_fungsional_id) && $pegawai_jabatan_fungsional_id != 0;

			if ($has_struktural && $has_fungsional) {
				$pegawai_jab_all = "{$pegawai_jabatan} (Struktural) / {$pegawai_jabatan_fungsional} (Fungsional)";
			} elseif ($has_struktural) {
				$pegawai_jab_all = $pegawai_jabatan;
			} elseif ($has_fungsional) {
				$pegawai_jab_all = $pegawai_jabatan_fungsional;
			}
		$pegawai_unit_kerja_id = $tab_portofolio->value('unit_kerja_id');
		$pegawai_unit_kerja = $tab_portofolio->value('unit_kerja');
		// Penilai
		$penilai_nama = null;
		if ($penilai_nip) {
			// Pake local Controller
			$requestSimpeg = new \Illuminate\Http\Request();
			$requestSimpeg->replace(['nip' => $penilai_nip]);
			$simpegController = app(\App\Http\Controllers\SimpegController::class);
			$response = $simpegController->get_pegawai($requestSimpeg);
			$data = $response->getData(true);

			if (is_array($data) && count($data) > 0) {
				$pegawai = $data[0];
				$namaPegawai = isset($pegawai['namaPegawai']) ? $pegawai['namaPegawai'] : '';
				$gelarDepan = isset($pegawai['gelarDepan']) ? trim($pegawai['gelarDepan']) : '';
				$gelarBelakang = isset($pegawai['gelarBelakang']) ? trim($pegawai['gelarBelakang']) : '';

				if (!empty($gelarDepan) && !empty($gelarBelakang)) {
					$penilai_nama = $gelarDepan . ' ' . $namaPegawai . ', ' . $gelarBelakang;
				}
				elseif (!empty($gelarDepan) && empty($gelarBelakang)) {
					$penilai_nama = $gelarDepan . ' ' . $namaPegawai ;
				}
				elseif (empty($gelarDepan) && !empty($gelarBelakang)) {
					$penilai_nama = $namaPegawai . ', ' . $gelarBelakang;
				}
				else {
					$penilai_nama = $namaPegawai;
				}

				$penilai_email = isset($pegawai['emailPegawai']) ? $pegawai['emailPegawai'] : '';
				$penilai_pangkat_id = isset($pegawai['pangkat_id']) ? $pegawai['pangkat_id'] : '';
				$penilai_pangkat = isset($pegawai['pangkat']) ? $pegawai['pangkat'] : '';
				$penilai_jabatan_id = isset($pegawai['jabatan_id']) ? $pegawai['jabatan_id'] : '';
				$penilai_jabatan = isset($pegawai['jabatan']) ? $pegawai['jabatan'] : '';
				$penilai_unit_kerja_id = isset($pegawai['unitKerja_id']) ? $pegawai['unitKerja_id'] : '';
				$penilai_unit_kerja = isset($pegawai['unitKerja']) ? $pegawai['unitKerja'] : '';
			}
		}

		// Otomatis
		$uid = \Illuminate\Support\Str::uuid()->toString();
		
		$data = [
			'portofolio_id' 		=> $portofolio_id,
			'uid'           		=> $uid,
			'tahun'					=> $tahun,
			'skp_tipe_id'   		=> $request->input('skp_tipe_id'),
			'periode_id'    		=> $request->input('periode_id'),
			'penilai_nip'   		=> $request->input('penilai_nip'),
			'periode_awal'			=> $periode_mulai,
			'periode_akhir' 		=> $periode_selesai,
			// Pegawai
			'pegawai_nip'   		=> $nip,
			'pegawai_email' 		=> $pegawai_email,
			'pegawai_nama'			=> $pegawai_nama,
			'pegawai_pangkat_id' 	=> $pegawai_pangkat_id,
			'pegawai_pangkat' 		=> $pegawai_pangkat,
			'pegawai_jabatan_id'	=> $pegawai_jabatan_id,
			'pegawai_jabatan'		=> $pegawai_jab_all,
			'pegawai_unit_kerja_id'	=> $pegawai_unit_kerja_id,
			'pegawai_unit_kerja'	=> $pegawai_unit_kerja,
			// Atasan Penilai
			'penilai_nip'			=> $penilai_nip,
			'penilai_nama'			=> $penilai_nama,
			'penilai_email'			=> $penilai_email,
			'penilai_pangkat_id'	=> $penilai_pangkat_id,
			'penilai_pangkat'		=> $penilai_pangkat,
			'penilai_jabatan_id'	=> $penilai_jabatan_id,
			'penilai_jabatan'		=> $penilai_jabatan,
			'penilai_unit_kerja_id' => $penilai_unit_kerja_id,
			'penilai_unit_kerja' 	=> $penilai_unit_kerja,
			// Status
			'status_id'				=> 2
		];
		// Insert ke tabel skp_kontrak
		$id = \DB::table('skp_kontrak')->insertGetId($data);
		return response()->json([
			'success' => true,
			'id' => $id,
			'uid' => $uid,
			'data' => $data
		]);
	}

}
