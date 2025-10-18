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

    protected function prepareForValidation(): void
    {
        //Lấy model FRESH (đảm bảo total_amount từ DB là giá trị chính xác)
        $invoice = $this->route('id')
            ? PurchaseInvoice::query()->find($this->route('id'))?->fresh()
            : null;

        $details = $this->input('details', []);
        $invoiceDiscountOnly = (float) $this->input('discount_amount', 0.00);
        // Lấy paid amount mới/cũ
        $paidAmount = (float) $this->input('paid_amount', $invoice->paid_amount ?? 0.00);

        // Các flags kiểm tra thay đổi
        $hasNewDetails = !empty($details);
        $hasNewDiscount = $this->has('discount_amount');

        if ($hasNewDetails || $hasNewDiscount) {
            $grossSubtotal     = 0.00; 
            $totalItemDiscount = 0.00; 

            // Nếu không có details mới,  lấy details từ DB
            $detailsToCalculate = $hasNewDetails ? $details : ($invoice->details ?? collect())->toArray();

            foreach ($detailsToCalculate as $d) {
                $q  = max(0.0, (float)($d['quantity'] ?? 0));
                $up = max(0.0, (float)($d['unit_price'] ?? 0));
                //$id = max(0.0, (float)($d['item_discount'] ?? 0));

                //$lineGross = round($q * $up, 2);
                //$lineDisc  = min($id, $lineGross); // Giới hạn Item Discount tạm thời

                $grossSubtotal     = round($grossSubtotal + $lineGross, 2);
                $totalItemDiscount = round($totalItemDiscount + $lineDisc, 2);
            }

            // Tính toán tổng tiền mới
            $subtotalAmount = round(max(0, $grossSubtotal - $totalItemDiscount), 2);
            $invoiceDiscountOnly = round(min(max(0, $invoiceDiscountOnly), 2), $subtotalAmount); 
            $totalAmount  = round(max(0, $subtotalAmount - $invoiceDiscountOnly), 2);

            // Nếu số tiền đã trả > tổng tiền mới, giảm paid_amount
            $adjustedPaidAmount = min($paidAmount, $totalAmount);

            $this->merge([
                'subtotal_amount'      => $subtotalAmount,
                'discount_amount'      => $invoiceDiscountOnly, // Chỉ lưu CK Header
                'invoice_discount_only' => $invoiceDiscountOnly,
                'total_amount'         => $totalAmount,
                'paid_amount'          => $adjustedPaidAmount, // Sử dụng giá trị đã điều chỉnh
            ]);

            return;
        }

        //cập nhật Paid Amount/Status/Note (KHÔNG có details/discount)
        if ($invoice) {
            $existingSubtotal = (float) ($invoice->subtotal_amount ?? 0.00);
            $existingTotal    = (float) ($invoice->total_amount ?? 0.00);
            $existingDiscount = (float) ($invoice->discount_amount ?? 0.00);

            // Lấy Total Amount CŨ từ DB
            $newTotal = $existingTotal;

            // FIX LỖI PAID AMOUNT: Nếu paid amount > total amount cũ, điều chỉnh paid amount
            $adjustedPaidAmount = round(min($paidAmount, $newTotal), 2);

            // SỬ DỤNG MERGE ĐỂ ĐẨY CÁC GIÁ TRỊ CŨ VÀO REQUEST CHO VALIDATION (FIX LỖI GỐC)
            // Lệnh merge này đảm bảo 'total_amount' = 50000.00 được dùng cho validation
            $this->merge([
                'subtotal_amount'       => $existingSubtotal,
                'total_amount'          => $existingTotal, // Gán Total Amount cũ từ DB
                'discount_amount'       => $existingDiscount,
                'paid_amount'           => $adjustedPaidAmount, // Giá trị PAID_AMOUNT đã điều chỉnh
                'invoice_discount_only' => $existingDiscount,
            ]);

            return; // Dùng early return để Request không bị xử lý tiếp
        }
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
            'invoice_discount_only' => 'nullable|numeric|min:0', // Đã thêm

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
            'details.*.item_discount' => 'nullable|numeric|min:0',
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
