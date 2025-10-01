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
     * Tính toán tổng tiền hóa đơn trước khi validation chạy.
     */
    protected function prepareForValidation(): void
    {
        $subtotal = 0.00;
        // Tổng chiết khấu HĐ ban đầu (có thể từ input field discount_amount)
        $totalDiscount = (float) $this->input('discount_amount', 0.00); 

        if ($this->has('details') && is_array($this->details)) {
            foreach ($this->details as $detail) {
                // Ép kiểu float/numeric
                $quantity = (float) ($detail['quantity'] ?? 0.00);
                $unitPrice = (float) ($detail['unit_price'] ?? 0.00);
                // Lấy item discount từ input, mặc định là 0
                $itemDiscount = (float) ($detail['item_discount'] ?? 0.00); 
                
                // Tính subtotal: (Số lượng * Đơn giá) - Giảm giá mặt hàng
                $subtotal += ($quantity * $unitPrice) - $itemDiscount;
                
                // Cộng dồn giảm giá mặt hàng vào tổng chiết khấu HĐ
                $totalDiscount += $itemDiscount; 
            }
        }
        
        $totalAmount = max(0.00, $subtotal - $totalDiscount);
        $paidAmount = (float) $this->input('paid_amount', 0.00);

        // Merge các giá trị đã tính toán và chuẩn hóa
        $this->merge([
            'total_amount' => $totalAmount,
            'subtotal_amount' => $subtotal,
            'discount_amount' => $totalDiscount,
            'paid_amount' => $paidAmount, 
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
              // --- Validation cho 2 cột mới ---
            'details.*.unit_of_measure' => 'required|string|max:50',
            'details.*.item_discount' => 'nullable|numeric|min:0', 
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
            'details.*.item_discount.numeric' => 'Giảm giá phải là số.',
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
