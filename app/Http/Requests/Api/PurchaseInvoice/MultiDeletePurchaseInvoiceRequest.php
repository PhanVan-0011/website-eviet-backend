<?php

namespace App\Http\Requests\Api\PurchaseInvoice;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class MultiDeletePurchaseInvoiceRequest extends FormRequest
{
    /**
     * Prepare the data for validation.
     * Xử lý ID có thể đến từ query string (URL) hoặc request body.
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
                // Đảm bảo hóa đơn nhập tồn tại trong database
                Rule::exists('purchase_invoices', 'id'),
            ],
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     */
    public function messages(): array
    {
        return [
            'ids.required' => 'Vui lòng cung cấp danh sách ID hóa đơn nhập cần xóa.',
            'ids.array' => 'Định dạng danh sách ID không hợp lệ.',
            'ids.min' => 'Vui lòng chọn ít nhất một hóa đơn để xóa.',
            'ids.*.integer' => 'Mỗi ID trong danh sách phải là một số nguyên.',
            'ids.*.exists' => 'Một trong các ID hóa đơn nhập không tồn tại.',
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
