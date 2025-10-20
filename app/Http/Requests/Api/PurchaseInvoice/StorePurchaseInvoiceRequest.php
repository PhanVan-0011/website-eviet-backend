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
     
    
      /**
     * Tính toán tổng tiền hóa đơn trước khi validation chạy (Phiên bản đã đơn giản hóa).
     */
    protected function prepareForValidation(): void
    {
        $subtotalAmount = 0.00;

        // 1. Tính tổng tiền hàng từ chi tiết
        if ($this->has('details') && is_array($this->details)) {
            foreach ($this->details as $detail) { 
                $quantity = (float) ($detail['quantity'] ?? 0);
                $unitPrice = (float) ($detail['unit_price'] ?? 0);
                $subtotalAmount += round($quantity * $unitPrice, 2);
            }
        }
    
        // 2. Lấy các giá trị khác từ request
        $invoiceDiscount = (float) $this->input('discount_amount', 0.00);
        $paidAmount = (float) $this->input('paid_amount', 0.00);

        // 3. Tính toán các giá trị cuối cùng
        // Đảm bảo giảm giá không lớn hơn tổng tiền hàng
        $adjustedDiscount = min($invoiceDiscount, $subtotalAmount); 
        $totalAmount = $subtotalAmount - $adjustedDiscount;
        
        // 4. Hợp nhất các giá trị đã tính vào request để validation
        $this->merge([
            'subtotal_amount' => $subtotalAmount,
            'total_amount' => $totalAmount, 
            'discount_amount' => $adjustedDiscount, // Sử dụng giảm giá đã được điều chỉnh
        ]);
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
            
            'invoice_discount_only' => 'nullable|numeric|min:0', // Trường được merge

            // Các trường giá trị tiền tệ/số lượng (Đã được merge)
            'subtotal_amount' => 'nullable|numeric|min:0',
            'discount_amount' => 'nullable|numeric|min:0',
            'total_amount' => 'nullable|numeric|min:0',
            'paid_amount' => 'nullable|numeric|min:0|lte:total_amount', 
            'notes' => 'nullable|string',

            // Chi tiết sản phẩm (Mảng)
            'details' => 'required|array|min:1',
            'details.*.product_id' => 'required|integer|exists:products,id',
            'details.*.quantity' => 'required|integer|min:1',
            'details.*.unit_price' => 'required|numeric|min:0',
            'details.*.unit_of_measure' => 'required|string|max:50', 
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
            'details.*.unit_of_measure.required' => 'Đơn vị tính là bắt buộc.',
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
