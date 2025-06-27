<?php

namespace App\Http\Requests\Api\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Rule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
class UpdateAdminUserRequest extends FormRequest
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
         // Lấy ID user từ route, ví dụ: /api/admin/admins/{id}
        $userId = $this->route('id');

        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'email' => ['sometimes', 'required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($userId)],
            'phone' => ['sometimes', 'required', 'string', 'max:11', Rule::unique('users', 'phone')->ignore($userId)],
            'password' => ['sometimes', 'nullable', 'confirmed', Password::min(8)],
            'password' => ['sometimes', 'nullable', 'confirmed', Password::min(8)], 
            'role_ids' => ['sometimes', 'required', 'array', 'min:1'],
            'role_ids.*' => ['integer', Rule::exists('roles', 'id')->where('guard_name', 'api')->whereNot('name', 'super-admin')],
            
            'is_active' => ['sometimes', 'required', 'boolean'],
            'gender' => ['sometimes', 'nullable', 'string', Rule::in(['male', 'female', 'other'])],
            'date_of_birth' => ['sometimes', 'nullable', 'date_format:Y-m-d', 'before_or_equal:today'],
            'address' => ['sometimes', 'nullable', 'string', 'max:255'],
        ];
        
    }
     public function messages(): array
    {
        return [
           'name.required' => 'Họ và tên không được để trống.',
            'email.required' => 'Email không được để trống.',
            'email.unique' => 'Email này đã tồn tại.',
            'phone.required' => 'Số điện thoại không được để trống.',
            'phone.unique' => 'Số điện thoại này đã tồn tại.',
            'password.confirmed' => 'Xác nhận mật khẩu không khớp.',

            'role_ids.required' => 'Vui lòng chọn ít nhất một vai trò.',
            'role_ids.*.exists' => 'Vai trò được chọn không hợp lệ hoặc không được phép gán.',
            'date_of_birth.before_or_equal' => 'Ngày sinh không hợp lệ.',
        ];
    }
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors(),
            ], 422)
        );
    }
}
