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
            // Sử dụng tham chiếu (&) để có thể cập nhật giá trị item_discount đã giới hạn
            // Tuy nhiên, đối với Store Request, ta chỉ cần tính toán tổng ở đây, việc
            // giới hạn Item Discount để lưu vào DB sẽ do Service đảm nhận.
            foreach ($this->details as $detail) {

                $quantity = (float) ($detail['quantity'] ?? 0.00);
                $unitPrice = (float) ($detail['unit_price'] ?? 0.00);
                $itemDiscount = (float) ($detail['item_discount'] ?? 0.00);

                // Tính Gross Line Total
                $lineGross = round($quantity * $unitPrice, 2);

                // Cộng dồn Gross Subtotal
                $grossSubtotal += $lineGross;

                //Giới hạn Item Discount không vượt quá giá trị dòng khi tính tổng Net Subtotal.
                $adjustedItemDiscount = round(min($itemDiscount, $lineGross), 2);
                $totalItemDiscount += $adjustedItemDiscount;
            }
        }

        // Net Subtotal (Đã trừ CK Item)
        $subtotalAmount = max(0.00, round($grossSubtotal - $totalItemDiscount, 2));

        //Giới hạn CK HĐ không được lớn hơn Net Subtotal
        $adjustedInvoiceDiscount = min(round(max(0.00, $invoiceDiscountOnly), 2), $subtotalAmount);

        // Total Amount CUỐI CÙNG: Net Subtotal - Adjusted Invoice Discount
        $totalAmount = max(0.00, round($subtotalAmount - $adjustedInvoiceDiscount, 2));

        // Cột discount_amount trong DB CHỈ LƯU CK HEADER ĐÃ ĐIỀU CHỈNH.

        $paidAmount = (float) $this->input('paid_amount', 0.00);

        // Merge các giá trị đã tính toán và chuẩn hóa
        $this->merge([
            'total_amount' => $totalAmount,
            'subtotal_amount' => $subtotalAmount,
            'discount_amount' => $adjustedInvoiceDiscount, // Lưu CK Header đã điều chỉnh
            'invoice_discount_only' => $adjustedInvoiceDiscount,
            'paid_amount' => $paidAmount,

        ]);
    }
    public function rules(): array
    {
        return [
            // Các trường chính của hóa đơn
            'supplier_id' => 'required|integer|exists:suppliers,id',
            'branch_id' => 'nullable|integer|exists:branches,id',
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
            'user_id.exists'   => 'Người tạo không tồn tại trong hệ thống.',

            'invoice_date.required' => 'Ngày hóa đơn là bắt buộc.',
            'status.in' => 'Trạng thái không hợp lệ.',
            'paid_amount.lte' => 'Số tiền đã trả không được lớn hơn Tổng tiền hóa đơn.',

            'details.required' => 'Hóa đơn phải có ít nhất một sản phẩm.',
            'details.*.product_id.required' => 'ID sản phẩm chi tiết là bắt buộc.',
            'details.*.quantity.min' => 'Số lượng sản phẩm phải lớn hơn 0.',
            'details.*.unit_of_measure.required' => 'Đơn vị tính là bắt buộc.',
            'details.*.item_discount.numeric' => 'Giảm giá phải là số.',
            'details.*.product_id.exists'   => 'Sản phẩm không tồn tại.',
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
