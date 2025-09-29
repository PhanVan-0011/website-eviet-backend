<?php

namespace App\Http\Requests\Api\PurchaseInvoice;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class StorePurchaseInvoiceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Các trường chính của hóa đơn
            'supplier_id' => 'required|integer|exists:suppliers,id',
            'branch_id' => 'required|integer|exists:branches,id',
            'user_id' => 'required|integer|exists:users,id',
            'invoice_date' => 'required|date',
            'status' => 'required|string|in:draft,received,cancelled',
            
            // Các trường giá trị tiền tệ/số lượng (sẽ được tính toán lại trong Service)
            'subtotal_amount' => 'nullable|numeric|min:0',
            'discount_amount' => 'nullable|numeric|min:0',
            'total_amount' => 'nullable|numeric|min:0',
            'paid_amount' => 'nullable|numeric|min:0|lte:total_amount', // Số tiền đã trả không được lớn hơn tổng tiền
            'notes' => 'nullable|string',

            // Chi tiết sản phẩm (Mảng)
            'details' => 'required|array|min:1',
            'details.*.product_id' => 'required|integer|exists:products,id',
            'details.*.quantity' => 'required|integer|min:1',
            'details.*.unit_price' => 'required|numeric|min:0',
        ];
    }
    
    public function messages(): array
    {
        return [
            'supplier_id.required' => 'Nhà cung cấp là bắt buộc.',
            'supplier_id.exists' => 'Nhà cung cấp không tồn tại.',
            'branch_id.required' => 'Chi nhánh nhập kho là bắt buộc.',
            'branch_id.exists' => 'Chi nhánh không tồn tại.',
            'user_id.required' => 'Người tạo hóa đơn là bắt buộc.',
            'invoice_date.required' => 'Ngày hóa đơn là bắt buộc.',
            'status.in' => 'Trạng thái không hợp lệ.',
            'paid_amount.lte' => 'Số tiền đã trả không được lớn hơn Tổng tiền hóa đơn.',
            'details.required' => 'Hóa đơn phải có ít nhất một sản phẩm.',
            'details.*.product_id.required' => 'ID sản phẩm chi tiết là bắt buộc.',
            'details.*.quantity.min' => 'Số lượng sản phẩm phải lớn hơn 0.',
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
