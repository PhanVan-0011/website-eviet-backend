<?php

namespace App\Http\Requests\Api\PurchaseInvoice;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;
use App\Models\PurchaseInvoice;

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
     * Chuẩn bị dữ liệu để validation, đảm bảo tính toán tổng tiền chính xác khi cập nhật.
     */
    protected function prepareForValidation(): void
    {
        $invoice = $this->route('id') ? PurchaseInvoice::with('details')->find($this->route('id')) : null;

        if (!$invoice) {
            return; // Nếu không tìm thấy hóa đơn, không cần làm gì thêm
        }

        // 1. Xác định dữ liệu chi tiết để tính toán
        // Ưu tiên lấy 'details' từ request gửi lên, nếu không có thì lấy từ DB
        $details = $this->has('details') ? $this->input('details') : $invoice->details->toArray();

        // 2. Tính lại tổng tiền hàng (subtotal)
        $subtotalAmount = 0.00;
        if (is_array($details)) {
            foreach ($details as $detail) {
                $quantity = (float)($detail['quantity'] ?? 0);
                $unitPrice = (float)($detail['unit_price'] ?? 0);
                $subtotalAmount += round($quantity * $unitPrice, 2);
            }
        }

        // 3. Lấy các giá trị khác, ưu tiên từ request, nếu không có thì lấy từ DB
        $invoiceDiscount = $this->has('discount_amount') ? (float)$this->input('discount_amount') : (float)$invoice->discount_amount;
        
        // 4. Tính toán các giá trị cuối cùng
        $adjustedDiscount = min($invoiceDiscount, $subtotalAmount);
        $totalAmount = $subtotalAmount - $adjustedDiscount;

        // 5. Hợp nhất các giá trị đã tính vào request để validation
        $this->merge([
            'subtotal_amount' => $subtotalAmount,
            'total_amount' => $totalAmount,
            'discount_amount' => $adjustedDiscount,
        ]);
    }


    public function rules(): array
    {
        return [
            // Các trường chính của hóa đơn
            'supplier_id' => 'sometimes|required|integer|exists:suppliers,id',
            'branch_id' => 'sometimes|required|integer|exists:branches,id',
            'invoice_date' => 'sometimes|required|date',
            'status' => 'sometimes|required|string|in:draft,received,cancelled',

            // Trường trung gian cần cho Service/validation
            //'invoice_discount_only' => 'nullable|numeric|min:0', // Đã thêm

            // Các trường tiền tệ/số lượng (Đã được merge)
            'subtotal_amount' => 'sometimes|nullable|numeric|min:0',
            'discount_amount' => 'sometimes|nullable|numeric|min:0',
            'total_amount' => 'sometimes|nullable|numeric|min:0',
            // Validation dựa trên total_amount đã merge
            'paid_amount' => 'sometimes|nullable|numeric|min:0|lte:total_amount',

            'notes' => 'sometimes|nullable|string',

            // Chi tiết sản phẩm (Mảng)
            'details' => 'sometimes|required|array|min:1',
            'details.*.product_id' => 'required|integer|exists:products,id',
            'details.*.quantity' => 'required|integer|min:1',
            'details.*.unit_price' => 'required|numeric|min:0',

            'details.*.unit_of_measure' => 'required|string|max:50',
        ];
    }
    public function messages(): array
    {
        return [
            'paid_amount.lte' => 'Số tiền đã trả không được lớn hơn Tổng tiền hóa đơn.',
            'details.required' => 'Hóa đơn phải có ít nhất một sản phẩm.',
            'details.*.product_id.required' => 'ID sản phẩm chi tiết là bắt buộc.',
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
