<?php
namespace App\Http\Requests;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
class AktifitasKinerjaAddRequest extends FormRequest
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
            
				"rhki_id" => "required",
				// "rhka_id" => "required",
				// "portofolio_kinerja_uid" => "required",
				"nip" => "required|string",
				"tanggal_mulai" => "required|date",
				"tanggal_selesai" => "nullable|date",
				// "tahun" => "required|numeric",
				"jumlah" => "required|numeric",
				"satuan" => "nullable",
				"dokumen" => "nullable",
				"gambar" => "nullable",
                "tautan" => "nullable"
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
