<?php

namespace App\Http\Requests\Api\User;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateUserRequest extends FormRequest
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
        $userId = $this->route('id');
        return [
            'name' => 'sometimes|required|string|max:255',
            'email' => [
                'sometimes', 'required', 'string', 'email', 'max:255',
                Rule::unique('users')->ignore($userId),
            ],
            'phone' => [
                'sometimes', 'required', 'string', 'regex:/^0[0-9]{9}$/',
                Rule::unique('users')->ignore($userId),
            ],
            'password' => 'sometimes|nullable|string|min:6|confirmed',
            'gender' => 'sometimes|nullable|in:male,female,other',
            'date_of_birth' => 'sometimes|nullable|date|before:today',
            'address' => 'sometimes|nullable|string|max:255',
            'is_active' => 'sometimes|nullable|boolean',
            'image_url' => 'sometimes|nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ];
    }
    public function messages(): array
    {
        return [
            //'name.required' => 'Tên không được để trống.',
            'name.string' => 'Tên phải là chuỗi ký tự.',
            'name.max' => 'Tên không được vượt quá 255 ký tự.',

            //'email.required' => 'Email không được để trống.',
            'email.email' => 'Email không đúng định dạng.',
            'email.unique' => 'Email này đã tồn tại trong hệ thống.',
            'email.max' => 'Email không được vượt quá 255 ký tự.',

            //'phone.required' => 'Số điện thoại không được để trống.',
            'phone.regex' => 'Số điện thoại phải có 10 chữ số và bắt đầu bằng số 0.',
            'phone.unique' => 'Số điện thoại này đã tồn tại trong hệ thống.',

            //'password.required' => 'Mật khẩu không được để trống.',
            'password.min' => 'Mật khẩu phải có ít nhất 6 ký tự.',
            'password.confirmed' => 'Mật khẩu xác nhận không khớp.',

            'gender.in' => 'Giới tính không hợp lệ',

            

            'date_of_birth.date' => 'Ngày sinh không đúng định dạng.',
            'date_of_birth.before' => 'Ngày sinh phải là một ngày trong quá khứ.',

            'address.string' => 'Địa chỉ phải là chuỗi ký tự.',
            'address.max' => 'Địa chỉ không được vượt quá 255 ký tự.',

            'is_active.boolean' => 'Trạng thái không hợp lệ.',

            'image_url.array' => 'Định dạng ảnh không hợp lệ.',
            'image_url.max' => 'Chỉ được tải lên tối đa 1 ảnh đại diện.',
            'image_url.required' => 'Vui lòng chọn một file ảnh.',
            'image_url.image' => 'File tải lên phải là hình ảnh.',
            'image_url.mimes' => 'Ảnh phải có định dạng: jpeg, png, jpg, gif.',
            'image_url.max' => 'Kích thước ảnh không được vượt quá 2MB.',
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

