<?php

namespace App\Http\Requests\Api\Client;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateProfileRequest extends FormRequest
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
        $userId = $this->user()->id;
        return [
            'name' => 'required|string|max:255',
            'email' => [
                'nullable',
                'string',
                'email',
                'max:255',
                Rule::unique('users')->ignore($userId),
            ],
            'gender' => 'nullable|string|in:male,female,other',
            'date_of_birth' => 'nullable|date_format:Y-m-d|before:today',
            'address' => 'nullable|string|max:255',
            'image_url' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048', // 2MB max
        ];
    }
     /**
     * Lấy các thông báo lỗi tùy chỉnh cho validator.
     *
     * @return array
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Vui lòng nhập họ và tên.',
            'email.email' => 'Email không đúng định dạng.',
            'email.unique' => 'Email này đã được sử dụng.',
            'gender.in' => 'Giới tính không hợp lệ.',
            'date_of_birth.date_format' => 'Ngày sinh phải có định dạng Y-m-d.',
            'date_of_birth.before' => 'Ngày sinh phải là một ngày trong quá khứ.',
            'address.max' => 'Địa chỉ không được vượt quá 500 ký tự.',
            'image_url.image' => 'File tải lên phải là một hình ảnh.',
            'image_url.mimes' => 'Hình ảnh phải có định dạng jpeg, png, jpg, hoặc gif.',
            'image_url.max' => 'Kích thước hình ảnh không được vượt quá 2MB.',
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
