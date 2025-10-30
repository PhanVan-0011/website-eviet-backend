<?php

namespace App\Http\Requests\Api\Suppliers;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class StoreSupplierRequest extends FormRequest
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
            'code' => 'nullable|string|max:50|unique:suppliers,code', 
            'name' => 'required|string|max:255|unique:suppliers,name',
            'group_id' => 'nullable|integer|exists:supplier_groups,id',
            'phone_number' => [
                'nullable',
                'string',
                'max:20'
            ],
            'address' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255|unique:suppliers,email',
            'tax_code' => 'nullable|string|max:50',
            'notes' => 'nullable|string',
            'is_active' => 'boolean',
            'user_id' => 'required|integer|exists:users,id',
        ];
    }
    /**
     * Get the error messages for the defined validation rules.
     */
    public function messages(): array
    {
        return [
            'code.unique' => 'Mã nhà cung cấp đã tồn tại.',
            'name.required' => 'Tên nhà cung cấp là bắt buộc.',
            'name.unique' => 'Tên nhà cung cấp đã tồn tại.',
            'group_id.exists' => 'Nhóm nhà cung cấp không hợp lệ.',
            'phone_number.unique' => 'Số điện thoại này đã được sử dụng.',
            'phone_number.regex' => 'Số điện thoại không đúng định dạng.',
            'email.email' => 'Email không đúng định dạng.',
            'email.unique' => 'Email này đã được sử dụng.',
            'user_id.required' => 'ID người tạo là bắt buộc.',
            'user_id.exists' => 'Người dùng không tồn tại.',
        ];
    }
    
    /**
     * Handle a failed validation attempt.
     */
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => $validator->errors()->first(),
            'errors' => $validator->errors(),
        ], 422));
    }
}
