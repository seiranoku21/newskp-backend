<?php 
namespace App\Http\Controllers;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
/**
 * Components Data Contoller
 * Use for getting values from the database for page components
 * Support raw query builder
 * @category Controller
 */
class Components_dataController extends Controller{
	public function __construct()
    {
        // $this->middleware('auth:api', ['except' => []]);
    }

	function user_role_id_option_list(Request $request){
		$sqltext = "SELECT role_id as value, role_name as label FROM roles";
		$query_params = [];
		$arr = DB::select($sqltext, $query_params);
		return $arr;
	}

	function skp_tipe_id_option_list(Request $request){
		$sqltext = "SELECT id as value, CONCAT('[',id,'] - ',skp_tipe) as label FROM ref_skp_tipe";
		$query_params = [];
		$arr = DB::select($sqltext, $query_params);
		return $arr;
	}
	function skp_tipe_deskripsi(Request $request, $id){
		$sqltext = "SELECT deskripsi FROM ref_skp_tipe WHERE id = ?";
		$query_params = [$id];
		$arr = DB::select($sqltext, $query_params);
		if(count($arr) > 0){
			return $arr[0]->deskripsi;
		}
		return "";
	}

	function users_username_exist(Request $request, $value){
		$exist = DB::table('users')->where('username', $value)->value('username');   
		if($exist){
			return "true";
		}
		return "false";
	}

	function users_email_exist(Request $request, $value){
		$exist = DB::table('users')->where('email', $value)->value('email');   
		if($exist){
			return "true";
		}
		return "false";
	}
	
	function periode_id_option_list(Request $request){
		$sqltext = "SELECT id as value, CONCAT('[',id,'] - ',periode,' (',rentang,')') as label FROM ref_periode";
		$query_params = [];
		$arr = DB::select($sqltext, $query_params);
		return $arr;
	}

	function status_id_option_list(Request $request){
		$sqltext = "SELECT id as value, CONCAT('[',id,'] - ',status) as label FROM ref_status";
		$query_params = [];
		$arr = DB::select($sqltext, $query_params);
		return $arr;
	}

	function portofolio_id_option_list(Request $request){
		$sqltext = "SELECT id as value, CONCAT('[',id,'-',SUBSTRING(uid, 1, 4),'] - ', jabatan) as label FROM portofolio_kinerja WHERE nip = ?";
		$query_params = [$request->nip];
		$arr = DB::select($sqltext, $query_params);
		return $arr;
	}

	function satuan_option_list(Request $request){
		$sqltext = "SELECT  DISTINCT okgnama AS value,okgnama AS label FROM ref_satuan ORDER BY okgnama ASC";
		$query_params = [];
		$arr = DB::select($sqltext, $query_params);
		return $arr;
	}

	function rhka_id_option_list(Request $request){
		$sqltext = "SELECT id as value, id as label FROM rencana_hasil_kerja_atasan";
		$query_params = [];
		$arr = DB::select($sqltext, $query_params);
		return $arr;
	}

	function rhki_id_option_list(Request $request){
		$sqltext = "SELECT id as value, CONCAT('[',id,'] - ',kegiatan) as label, rhka_id as rhka_id, portofolio_kinerja_uid as portofolio_kinerja_uid FROM rencana_hasil_kerja_item WHERE nip = ?";
		$query_params = [$request->nip];
		$arr = DB::select($sqltext, $query_params);
		return $arr;
	}

	function portofolio_kinerja_uid_option_list(Request $request){
		if ($request->has('nip')) {
			$sqltext = "SELECT uid as value, jabatan as label FROM portofolio_kinerja WHERE nip = ?";
			$query_params = [$request->nip];
		} else {
			$sqltext = "SELECT uid as value, jabatan as label FROM portofolio_kinerja";
			$query_params = [];
		}
		$arr = DB::select($sqltext, $query_params);
		return $arr;
	}
	
}
