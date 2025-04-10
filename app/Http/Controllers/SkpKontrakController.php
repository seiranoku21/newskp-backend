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

	// function view($rec_id = null){
		
	// 	$query = SkpKontrak::query();
	// 	$query->leftJoin("ref_status", "skp_kontrak.status_id", "=", "ref_status.id");
	// 	$query->join("ref_status_vrf", "skp_kontrak.status_vrf_id", "=", "ref_status_vrf.id");
	// 	$query->join("ref_periode", "skp_kontrak.periode_id", "=", "ref_periode.id");
	// 	$record = $query->where(function($query) use ($rec_id) {
	// 		$query->where('skp_kontrak.id', $rec_id)
	// 			->orWhere('skp_kontrak.uid', $rec_id);
	// 	})->firstOrFail(SkpKontrak::viewFields());
	// 	return $this->respond($record);
	// }

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
}
