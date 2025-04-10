<?php
namespace App\Http\Requests;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
class RencanaHasilKerjaItemEditRequest extends FormRequest
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
            
				"rhka_id" => "nullable|numeric",
				"portofolio_kinerja_uid" => "nullable|string",
				"nip" => "nullable|string",
				"kegiatan" => "nullable|string",
				"aspek_kuantitas" => "nullable|string",
				"aspek_kualitas" => "nullable|string",
				"aspek_waktu" => "nullable|string",
				"ukuran_keberhasilan" => "nullable|string",
				"realisasi" => "nullable|string",
				"updated_at" => "nullable|date"

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
