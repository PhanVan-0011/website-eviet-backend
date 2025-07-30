<?php

namespace App\Http\Requests\Api\Auth\Me;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateProfileRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        // Chỉ cho phép người dùng đã đăng nhập cập nhật hồ sơ
        return auth('sanctum')->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'name' => 'sometimes|string|max:255',
            'gender' => 'sometimes|in:male,female,other',
            'date_of_birth' => 'sometimes|date|before:today',
            'email' => 'sometimes|email|unique:users,email,' . $this->user()->id,
            'phone' => 'sometimes|string|regex:/^0[0-9]{9}$/|unique:users,phone,' . $this->user()->id,
            'address' => 'required|string|max:255|regex:/^[\p{L}\p{N}\s,.-]+$/u',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array
     */
    public function messages()
    {
        return [
            'name.string' => 'Tên phải là chuỗi ký tự.',
            'name.max' => 'Tên không được vượt quá 255 ký tự.',
            'gender.in' => 'Giới tính phải là male, female hoặc other.',
            'date_of_birth.date' => 'Ngày sinh phải là định dạng ngày hợp lệ.',
            'date_of_birth.before' => 'Ngày sinh phải trước ngày hiện tại.',
            'email.email' => 'Định dạng email không hợp lệ.',
            'email.unique' => 'Email đã được sử dụng.',
            'phone.regex' => 'Số điện thoại phải bắt đầu bằng 0 và có 10 chữ số.',
            'phone.unique' => 'Số điện thoại đã được sử dụng.',
            'address.required' => 'Vui lòng nhập địa chỉ.',
            'address.string' => 'Địa chỉ phải là chuỗi ký tự.',
            'address.max' => 'Địa chỉ không được vượt quá 255 ký tự.',
            'address.regex' => 'Địa chỉ chỉ được chứa chữ cái, số, dấu cách, dấu phẩy, dấu chấm và dấu gạch ngang.',
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