<?php

namespace App\Http\Requests\Api\Role;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\Rule;
class UpdateRoleRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('roles.update');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $roleId = $this->route('id');
         return [
            'name' => ['sometimes','required', 'string', 'max:50', 'alpha_dash', Rule::unique('roles', 'name')->ignore($roleId)],
            'display_name' => ['sometimes', 'required', 'string', 'max:255'],
            'permissions' => ['sometimes', 'array'],
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

            'permissions.array' => 'Danh sách quyền hạn phải là một mảng.',

            'display_name.required' => 'Tên hiển thị không được để trống.',

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
