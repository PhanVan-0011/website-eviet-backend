<?php

namespace App\Http\Requests\api\Slider;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class MultiDeleteSliderRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'ids' => ['required', 'string', 'regex:/^\d+(,\d+)*$/'],
        ];
    }
    public function messages()
    {
        return [
            'ids.required' => 'Danh sách ID là bắt buộc.',
            'ids.string' => 'IDs phải là chuỗi.',
            'ids.regex' => 'Định dạng không hợp lệ. Ví dụ đúng: 1,2,3',
        ];
    }
     protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'errors' => $validator->errors(),
        ], 422));
    }
}
