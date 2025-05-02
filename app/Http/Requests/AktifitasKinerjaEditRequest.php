<?php
namespace App\Http\Requests;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
class AktifitasKinerjaEditRequest extends FormRequest
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
            
				"rhki_id" => "nullable",
				"rhka_id" => "nullable",
				"portofolio_kinerja_uid" => "nullable",
				"nip" => "nullable|string",
				"tanggal_mulai" => "nullable|date",
				"tanggal_selesai" => "nullable|date",
				"tahun" => "nullable|numeric",
				"jumlah" => "nullable|numeric",
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
