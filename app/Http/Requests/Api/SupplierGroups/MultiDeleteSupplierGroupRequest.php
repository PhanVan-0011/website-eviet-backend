<?php

namespace App\Http\Requests\Api\SupplierGroups;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class MultiDeleteSupplierGroupRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return false;
    }

     /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $ids = $this->query('ids');
        if (is_string($ids)) {
            $ids = explode(',', $ids);
        }
        $this->merge(['ids' => $ids]);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'ids' => 'required|array|min:1',
            'ids.*' => [
                'required',
                'integer',
                // Đảm bảo nhóm nhà cung cấp tồn tại trong database
                Rule::exists('supplier_groups', 'id'),
            ],
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     */
    public function messages(): array
    {
        return [
            'ids.required' => 'Vui lòng cung cấp danh sách ID nhóm nhà cung cấp cần xóa.',
            'ids.array' => 'Định dạng danh sách ID không hợp lệ.',
            'ids.min' => 'Vui lòng chọn ít nhất một nhóm để xóa.',
            'ids.*.integer' => 'Mỗi ID trong danh sách phải là một số nguyên.',
            'ids.*.exists' => 'Một trong các ID không tồn tại.',
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
