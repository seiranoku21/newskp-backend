<?php
namespace App\Http\Requests;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
class SkpKontrakEditRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
		
        return [
            
				"skp_tipe_id" => "filled",
				"tahun" => "filled|numeric",
				"periode_id" => "filled",
				"periode_awal" => "filled|date",
				"periode_akhir" => "filled|date",
				"penilai_nip" => "filled|string",
				"penilai_email" => "nullable|email",
				"penilai_nama" => "nullable|string",
				"penilai_pangkat_id" => "nullable|numeric",
				"penilai_pangkat" => "nullable|string",
				"penilai_jabatan_id" => "nullable|numeric",
				"penilai_jabatan" => "nullable|string",
				"penilai_unit_kerja_id" => "nullable|numeric",
				"penilai_unit_kerja" => "nullable|string",
                "portofolio_id" => "nullable|numeric",
			    "portofolio_uid "  => "nullable|string",
                "pegawai_nip" => "nullable|string",
                "updated_at" => "nullable|date",
                
        ];
    }

	public function messages()
    {
        return [
            //using laravel default validation messages
        ];
    }

	/**
     * If validator fails return the exception in json form
     * @param Validator $validator
     * @return array
     */
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json($validator->errors(), 422));
    }
}
