<?php

namespace App\Http\Requests\Api\Role;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator;
class StoreRoleRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('roles.create');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:50','alpha_dash', 'unique:roles,name'],
            'display_name' => ['required', 'string', 'max:255'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['integer', 'exists:permissions,id'],
        ];
    }
     public function messages(): array
    {
        return [
            'name.required' => 'Tên vai trò không được để trống.',
            'name.string' => 'Tên vai trò phải là một chuỗi ký tự.',
            'name.max' => 'Tên vai trò không được vượt quá 50 ký tự.',
            'name.unique' => 'Tên vai trò này đã tồn tại.',
            'name.alpha_dash' => 'Tên vai trò chỉ được chứa chữ cái, số, dấu gạch ngang (-) và gạch dưới (_).',

            'display_name.required' => 'Tên hiển thị không được để trống.',

            'permissions.array' => 'Danh sách quyền hạn phải là một mảng.',
            'permissions.*.integer' => 'Mỗi quyền hạn phải là một ID số nguyên.',
            'permissions.*.exists' => 'Một hoặc nhiều quyền hạn không tồn tại.',
        ];
    }
    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => $validator->errors()->first(),
            'errors' => $validator->errors(),
        ], 422));
    }
}
