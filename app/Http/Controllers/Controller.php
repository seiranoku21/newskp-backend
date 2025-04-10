<?php

namespace App\Http\Controllers;

use App\Helpers\Uploader;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

use Illuminate\Support\Facades\File;
use App\Recipients\EmailRecipient;
use App\Notifications\RecordActionMail;
use App\Notifications\OTPVerification;
use Exception;
class Controller extends BaseController
{
	use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

	/**
	 * List of supported export format
	 * @var array
	 */
	protected $exportFormats = ['pdf', 'csv', 'excel', 'print'];

	/**
	 * Get current request export format from GET
	 * @example /products/index?export=csv
	 * @return string
	 */
	public function getExportFormat(){
		$format = request()->export ?? '';
		return in_array(strtolower($format), $this->exportFormats) ? $format: null;
	}

	/**
	 * Build custom pagination object from the request record
	 * @param \Illuminate\Database\Eloquent\Builder $query
	 * @param array $fields // list of table fields to select
	 * @return array
	 */
	public function paginate($query, $fields = []){
		$limit = request()->limit ?? 20;
		$page = request()->page ?? 1;
		$offset = (($page-1) * $limit);
		$total_records = $query->count();
		$records = $query->skip($offset)->take($limit)->get($fields);
		$records_count = count($records);
		$total_pages = ceil($total_records / $limit);
		$result = [
			"records" => $records,
			"totalRecords" => $total_records,
			"recordCount" => $records_count,
			"totalPages" => $total_pages,
		];
		return $result;
	}


	/**
	 * Return success Http response (200)
	 * @return \Illuminate\Http\Response
	 */
	public function respond($data){
		return $data;
	}


	/**
	 * Return failed Http response
	 * @return \Illuminate\Http\Response
	 */
	public function reject($msg, $status_code = 500){
		return response($msg, $status_code);
	}


	/**
     * Parse csv file into multidimensional array
	 * @param string $file_path
	 * @param array $options //Values include :- fields | delimiter | quote
     * @return array
     */
	function parse_csv_data($file_path, $options){
		$arr_data = array();
		if (($csv_handle = fopen($file_path, "r")) === FALSE)
			throw new Exception('Cannot open file');

		extract($options);

		if(empty($fields)){
			$columns = array_map(function ($field){
				return strtolower(preg_replace("/[^a-zA-Z0-9_]/i", '', $field));
            }, fgetcsv($csv_handle, 0, $delimiter, $quote));
		}
		else{
            $columns = (is_array($fields) ? $fields : explode(",", $fields));
		}
		
		if(empty($delimiter))
			$delimiter = ',';

		if(empty($quote))
			$quote = '"';

		while (($row = fgetcsv($csv_handle, 0, $delimiter, $quote)) !== FALSE) {
			$arr_data[] = array_combine($columns, $row);
		}
		return $arr_data;
	}


	/**
	 * Convinient function to delete files associated with record when deleted
	 * @param string $fileNames // can be separated by comma
	 * @return void
	 */
	public function deleteRecordFiles($fileNames, $fieldName){
		try{
			$filesToBeDeleted = explode(",", $fileNames);
			$imgThumbDirs = ["small", "medium", "large"];

			$uploadSettings = config("upload.$fieldName");
			if($uploadSettings){
				$imgThumbDirs = array_keys($uploadSettings["image_resize"]);
			}

			$imgExts = ["jpg", "png", "jpeg"];
			foreach($filesToBeDeleted as $file){
				$fullPath = public_path() . "/" . $file;
				if(File::exists($fullPath)){
					File::delete($fullPath);
					
					$isImg = (in_array(File::extension($fullPath), $imgExts) ? true : false);
					if($isImg){
						foreach($imgThumbDirs as $dir){
							$paths = explode("/", $fullPath);
							$lastpath = count($paths) - 1;
							array_splice($paths, $lastpath, 0, $dir);
							$thumbFullPath = implode("/", $paths);
							if(File::exists($thumbFullPath)){
								File::delete($thumbFullPath);
							}
						}
					}
				}
			}
		}
		catch(Exception $e){
			throw $e;
		}
	}

	/**
	 * Serialize form data with array values to string
	 * @param array $arr //Request data
	 * @return array
	 */
	function normalizeFormData($arr){
		foreach($arr as $key => $val){
			if(is_array($val)){
				$arr[$key] = implode(",", $val);
			}
		}
		return $arr;
	}

	/**
	 * Move uploaded files from temp directory to new directory when form submit is submitted
	 * @param string $files //uploaded files names
	 * @param string $fieldname //fieldname for the uploaded file
	 * @return string
	 */
	function moveUploadedFiles($files, $fieldname){
		$fileInfo = [
			"filepath" => $files, 
			"fileext" => "", 
			"filetype" => "", 
			"filename" => "", 
			"filesize" => ""
		];
		if($files){
			$uploader = new Uploader($fieldname);
			$arrFiles = explode(",", $files);
			$movedFilesPaths = [];
			foreach($arrFiles as $file){
				if(stripos($file, config("upload.tempDir")) > -1){
					//move only files in temp directory
					$movedFilesPaths[] = $uploader->moveUploadedFiles($file);
				}
				else{
					$movedFilesPaths[] = $file;
				}
			}
			if($movedFilesPaths){
				$file = public_path($movedFilesPaths[0]);
				if(file_exists($file)){
					$fileInfo['filetype'] = File::mimeType($file);
					$fileInfo['filesize'] = File::size($file);
				}
				$fileInfo['filepath'] = implode(",", $movedFilesPaths);
				$fileInfo['filename'] = File::basename($file);
				$fileInfo['fileext'] = File::extension($file);
			
			}
		}
		return $fileInfo;
	}

	/**
	 * Send mail to system admin on record action such as Insert| Delete | Update
	 * @param $receiver
	 * @param $subject
	 * @param $message
	 * @param $recordLink
	 * @return void
	 */
	public function sendRecordActionMail($receiver, $subject, $message, $recordLink = null){
		try{
			$recipient = new EmailRecipient($receiver);
			$recipient->notify(new RecordActionMail($subject, $message, $recordLink));
		}
		catch(Exception $e){
			throw $e;
		}
	}


	/**
	 * Send otp verification email to user
	 * @param $receiver
	 * @param $subject
	 * @param $message
	 * @return void
	 */
	public function sendOTPVerificationMail($receiver, $subject, $message){
		try{
			$recipient = new EmailRecipient($receiver);
			$recipient->notify(new OTPVerification($subject, $message));
		}
		catch(Exception $e){
			throw $e;
		}
	}
}