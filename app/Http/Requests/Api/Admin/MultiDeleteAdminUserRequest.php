<?php

namespace App\Http\Requests\Api\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
class MultiDeleteAdminUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }
    /**
     * Chuẩn bị dữ liệu trước khi validation.
     * Phương thức này sẽ tự động chuyển đổi chuỗi ids="5,6" thành mảng [5, 6].
     */
    protected function prepareForValidation()
    {
        if ($this->has('ids') && is_string($this->ids)) {
            $this->merge([
                // Tách chuỗi bằng dấu phẩy và loại bỏ các khoảng trắng thừa
                'ids' => array_map('trim', explode(',', $this->ids)),
            ]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'exists:users,id'],
        ];
    }
    public function messages(): array
    {
        return [
            'ids.required' => 'Vui lòng chọn ít nhất một nhân viên để xóa.',
            'ids.array' => 'Dữ liệu không hợp lệ.',
            'ids.*.integer' => 'ID nhân viên không hợp lệ.',
            'ids.*.exists' => 'Một trong các nhân viên được chọn không tồn tại.',
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
