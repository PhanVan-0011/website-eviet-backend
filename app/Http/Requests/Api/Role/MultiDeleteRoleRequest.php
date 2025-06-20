<?php

namespace App\Http\Requests\Api\Role;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator;
class MultiDeleteRoleRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('roles.manage');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'ids' => ['required', 'string'],
            'role_ids' => ['required', 'array', 'min:1'],
            'role_ids.*' => ['integer', 'exists:roles,id'],
        ];
    }
    public function messages(): array
    {
        return [
            'ids.required' => 'Vui lòng cung cấp danh sách ID vai trò trong URL (ví dụ: ?ids=1,2,3).',
            'role_ids.required' => 'Vui lòng chọn ít nhất một vai trò để xóa.',
            'role_ids.array' => 'Dữ liệu ID vai trò không hợp lệ.',
            'role_ids.min' => 'Vui lòng chọn ít nhất một vai trò để xóa.',
            'role_ids.*.integer' => 'Mỗi ID vai trò phải là một số nguyên.',
            'role_ids.*.exists' => 'Một hoặc nhiều ID vai trò không tồn tại trong hệ thống.',
        ];
    }
    protected function prepareForValidation(): void
    {
        if ($this->query('ids')) {
            $idsArray = explode(',', $this->query('ids'));
            $filteredIds = array_filter($idsArray);
            $sanitizedIds = array_map('intval', $filteredIds);

            $this->merge([
                'role_ids' => $sanitizedIds
            ]);
        }
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
