<?php

namespace App\Http\Requests\Api\Suppliers;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class UpdateSupplierRequest extends FormRequest
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
        // Lấy ID từ tham số route (giả sử tên tham số là 'id')
       $supplierId = $this->route('id');
       return [
            'code' => ['sometimes', 'required', 'string', 'max:50', Rule::unique('suppliers', 'code')->ignore($supplierId)],
            'name' => ['sometimes', 'required', 'string', 'max:255', Rule::unique('suppliers', 'name')->ignore($supplierId)],
            'group_id' => 'sometimes|nullable|integer|exists:supplier_groups,id',
            'phone_number' => ['sometimes', 'nullable', 'string', 'max:20', Rule::unique('suppliers', 'phone_number')->ignore($supplierId), 'regex:/^(0|\+84)(3[2-9]|5[6|8|9]|7[0|6-9]|8[1-9]|9[0-4|6-9])[0-9]{7}$/'],
            'address' => 'sometimes|nullable|string|max:255',
            'email' => ['sometimes', 'nullable', 'email', 'max:255', Rule::unique('suppliers', 'email')->ignore($supplierId)],
            'tax_code' => 'sometimes|nullable|string|max:50',
            'notes' => 'sometimes|nullable|string',
            'is_active' => 'sometimes|boolean',
            'user_id' => 'sometimes|required|integer|exists:users,id',
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
            'email.unique' => 'Email này đã được sử dụng.',
            'user_id.required' => 'ID người tạo là bắt buộc.', // Giữ lại
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
