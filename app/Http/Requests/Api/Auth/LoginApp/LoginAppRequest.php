<?php

namespace App\Http\Requests\Api\Auth\LoginApp;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class LoginAppRequest extends FormRequest
{
     public function rules(): array
    {
        return [
            'phone' => ['required', 'string', 'exists:users,phone,deleted_at,NULL'],
            'password' => 'required|string',
        ];
    }
    
    public function messages(): array
    {
        return [
            'phone.required' => 'Vui lòng nhập số điện thoại.',
            'phone.exists' => 'Số điện thoại này chưa được đăng ký.',
            'password.required' => 'Vui lòng nhập mật khẩu.',
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
