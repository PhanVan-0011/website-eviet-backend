<?php

namespace App\Http\Requests\Api\PurchaseInvoice;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class UpdatePurchaseInvoiceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }
    /**
     * Tính toán lại tổng tiền hóa đơn trước khi validation chạy.
     */
    protected function prepareForValidation(): void
    {
        $subtotal = 0.00;
        // Lấy discount từ request, hoặc giá trị hiện tại của model nếu không được gửi
        $discount = (float) $this->input('discount_amount', $this->invoice->discount_amount ?? 0.00);

        // Tính toán Subtotal từ chi tiết MỚI được gửi
        if ($this->has('details') && is_array($this->details)) {
            foreach ($this->details as $detail) {
                // Ép kiểu float rõ ràng
                $quantity = (float) ($detail['quantity'] ?? 0.00);
                $unitPrice = (float) ($detail['unit_price'] ?? 0.00);
                $subtotal += $quantity * $unitPrice;
            }
        } else {
            // Nếu KHÔNG có details mới, giữ lại subtotal cũ của model
            $subtotal = $this->invoice->subtotal_amount ?? 0.00;
        }

        // Tính Total Amount cuối cùng
        $totalAmount = max(0.00, $subtotal - $discount);
        $paidAmount = (float) $this->input('paid_amount', $this->invoice->paid_amount ?? 0.00); // Lấy giá trị paid_amount mới hoặc cũ

        // Merge các giá trị đã tính toán và chuẩn hóa
        $this->merge([
            'total_amount' => $totalAmount,
            'subtotal_amount' => $subtotal,
            'discount_amount' => $discount,
            'paid_amount' => $paidAmount,
        ]);
    }

    public function rules(): array
    {
        return [
            'supplier_id' => 'sometimes|required|integer|exists:suppliers,id',
            'branch_id' => 'sometimes|required|integer|exists:branches,id',
            'invoice_date' => 'sometimes|required|date',
            'status' => 'sometimes|required|string|in:draft,received,cancelled',

            // Các trường tiền tệ/số lượng
            'discount_amount' => 'sometimes|nullable|numeric|min:0',
            'paid_amount' => 'sometimes|nullable|numeric|min:0|lte:total_amount',
            'notes' => 'sometimes|nullable|string',

            // Chi tiết sản phẩm (Update chi tiết thường là thao tác thay thế/chỉnh sửa)
            'details' => 'sometimes|required|array|min:1',
            'details.*.product_id' => 'required|integer|exists:products,id',
            'details.*.quantity' => 'required|integer|min:1',
            'details.*.unit_price' => 'required|numeric|min:0',

            // Các trường tổng tiền này thường được tính toán, nhưng có thể cần update cho mục đích chỉnh sửa thủ công
            'subtotal_amount' => 'sometimes|nullable|numeric|min:0',
            'total_amount' => 'sometimes|nullable|numeric|min:0',
        ];
    }

    public function messages(): array
    {
        return [
            'paid_amount.lte' => 'Số tiền đã trả không được lớn hơn Tổng tiền hóa đơn.',
            'details.required' => 'Hóa đơn phải có ít nhất một sản phẩm.',
            'details.*.product_id.required' => 'ID sản phẩm chi tiết là bắt buộc.',
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
