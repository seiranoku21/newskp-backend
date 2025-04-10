<?php
namespace App\Http\Requests;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
class PortofolioKinerjaEditRequest extends FormRequest
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
    			"jabatan" => "nullable|string",
				"jabatan_struktural" => "nullable|string",
				"jabatan_fungsional" => "nullable|string",
                "jabatan_id" => "nullable|string",
				"jabatan_struktural_id" => "nullable|string",
				"jabatan_fungsional_id" => "nullable|string",
                "unit_kerja_id" => "nullable|string",
                "unit_kerja" => "nullable|string",
                "homebase_id" => "nullable|string",
                "homebase" => "nullable|string"
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
