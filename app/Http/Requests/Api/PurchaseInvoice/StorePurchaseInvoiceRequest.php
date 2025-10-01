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
        $grossSubtotal = 0.00; // Tổng tiền trước mọi chiết khấu
        $totalItemDiscount = 0.00; // Tổng chiết khấu mặt hàng
        
        // Lấy chiết khấu HĐ từ input field (Chỉ Chiết khấu HĐ)
        $invoiceDiscountOnly = (float) $this->input('discount_amount', 0.00); 

        if ($this->has('details') && is_array($this->details)) {
            foreach ($this->details as $detail) {
                // Ép kiểu FLOAT cho tất cả các biến số học
                $quantity = (float) ($detail['quantity'] ?? 0.00);
                $unitPrice = (float) ($detail['unit_price'] ?? 0.00);
                $itemDiscount = (float) ($detail['item_discount'] ?? 0.00); 
                
                // 1. Tính TỔNG TIỀN GỐC (Gross Subtotal)
                $grossSubtotal += $quantity * $unitPrice;
                
                // 2. Cộng dồn giảm giá mặt hàng vào tổng chiết khấu
                $totalItemDiscount += $itemDiscount; 
            }
        }
        
        // TÍNH TOÁN CÁC GIÁ TRỊ CẦN LƯU VÀO DB:

        // Net Subtotal (Đã trừ CK Item): Gross Subtotal - Total Item Discount (69,500)
        $subtotalAmount = max(0.00, $grossSubtotal - $totalItemDiscount);

        // Giới hạn CK HĐ không được lớn hơn Net Subtotal
        $invoiceDiscountOnly = min(round(max(0.00, $invoiceDiscountOnly), 2), $subtotalAmount);

        // Total Amount CUỐI CÙNG: Net Subtotal - Invoice Discount Only (67,000)
        $totalAmount = max(0.00, $subtotalAmount - $invoiceDiscountOnly); 
        
        // Total Discount TOÀN BỘ (Item + Header) (3,000) - Chỉ dùng để tính totalAmount, không lưu vào cột discount_amount của Model
        $totalAllDiscount = $totalItemDiscount + $invoiceDiscountOnly;

        $paidAmount = (float) $this->input('paid_amount', 0.00);

        // Merge các giá trị đã tính toán và chuẩn hóa
        $this->merge([
            'total_amount' => $totalAmount, // Giá trị cuối cùng của HĐ (67,000)
            'subtotal_amount' => $subtotalAmount, // Giá trị hàng sau CK Item (69,500)
            'discount_amount' => $invoiceDiscountOnly, // CHỈ LƯU CK HEADER VÀO CỘT NÀY (2,500)
            'invoice_discount_only' => $invoiceDiscountOnly, // Trường trung gian CK Header (2,500)
            'paid_amount' => $paidAmount, 
            
            // LƯU Ý QUAN TRỌNG:
            // Cần lưu tổng chiết khấu vào một trường khác nếu bạn muốn lưu 3000.00
            // Hoặc sử dụng totalAllDiscount trong Service để tính toán.
            // Hiện tại, cột discount_amount (DB) được dùng để lưu CK Header (2500)
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
            
            'invoice_discount_only' => 'nullable|numeric|min:0', // Trường mới được merge

            // Các trường giá trị tiền tệ/số lượng (Đã được merge)
            'subtotal_amount' => 'nullable|numeric|min:0',
            'discount_amount' => 'nullable|numeric|min:0',
            'total_amount' => 'nullable|numeric|min:0',
            'paid_amount' => 'nullable|numeric|min:0|lte:total_amount', // Số tiền đã trả không được lớn hơn tổng tiền (122k)
            'notes' => 'nullable|string',

            // Chi tiết sản phẩm (Mảng)
            'details' => 'required|array|min:1',
            'details.*.product_id' => 'required|integer|exists:products,id',
            'details.*.quantity' => 'required|integer|min:1',
            'details.*.unit_price' => 'required|numeric|min:0',
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
