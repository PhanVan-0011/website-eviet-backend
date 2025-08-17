<?php

namespace App\Http\Requests\Api\Auth\ForgotPassWord;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class ForgotPasswordInitiateRequest extends FormRequest
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
            // Yêu cầu SĐT phải tồn tại và không bị xóa mềm
             'phone' => ['required', 'string', 'regex:/^(0[3|5|7|8|9])+([0-9]{8})$/', 'exists:users,phone,deleted_at,NULL']
        ];
    }
    public function messages(): array
    {
        return [
           'phone.required' => 'Vui lòng nhập số điện thoại.',
           'phone.exists' => 'Số điện thoại này chưa được đăng ký.',
           'phone.string' => 'Số điện thoại phải là một chuỗi ký tự.',
           'phone.regex' => 'Số điện thoại không đúng định dạng. Vui lòng kiểm tra lại.',
        ];
    }
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => $validator->errors()->first(),
            'errors' => $validator->errors(),
        ], 422));
    }
}
