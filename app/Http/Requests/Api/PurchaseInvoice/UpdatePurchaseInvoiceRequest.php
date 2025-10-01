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
        // 1. Lấy model FRESH (đảm bảo total_amount từ DB là giá trị chính xác)
        // **LƯU Ý:** Dùng fresh() để tránh lỗi cache model trong transaction
        $invoice = $this->route('id')
            ? PurchaseInvoice::query()->find($this->route('id'))?->fresh()
            : null;

        // Input an toàn
        $details = $this->input('details', []);
        $invoiceDiscountOnly = (float) $this->input('discount_amount', 0.00);
        // Lấy paid amount mới/cũ
        $paidAmount = (float) $this->input('paid_amount', $invoice->paid_amount ?? 0.00); 

        // === TRƯỜNG HỢP 1: Có gửi details -> tính lại từ payload ===
        if (!empty($details)) {
            $grossSubtotal     = 0.00; // Σ qty*unit_price
            $totalItemDiscount = 0.00; // Σ item_discount

            foreach ($details as $d) {
                $q  = max(0.0, (float)($d['quantity'] ?? 0));
                $up = max(0.0, (float)($d['unit_price'] ?? 0));
                $id = max(0.0, (float)($d['item_discount'] ?? 0));

                $lineGross = round($q * $up, 2);
                $lineDisc  = min($id, $lineGross);      // không cho chiết khấu lớn hơn giá trị dòng
                $grossSubtotal     = round($grossSubtotal + $lineGross, 2);
                $totalItemDiscount = round($totalItemDiscount + $lineDisc, 2);
            }

            $subtotalAmount = round(max(0, $grossSubtotal - $totalItemDiscount), 2);
            $invoiceDiscountOnly = min(round(max(0, $invoiceDiscountOnly), 2), $subtotalAmount); // CK HĐ không vượt quá Subtotal
            $totalAmount  = round(max(0, $subtotalAmount - $invoiceDiscountOnly), 2);

            // CHỈNH SỬA TẠI ĐÂY: discount_amount chỉ lưu CK Header (2500)
            $this->merge([
                'subtotal_amount'        => $subtotalAmount,
                'discount_amount'        => $invoiceDiscountOnly, // CHỈ LƯU CK HEADER
                'invoice_discount_only'  => $invoiceDiscountOnly, // Trường trung gian CK Header
                'total_amount'           => $totalAmount,
                'paid_amount'            => $paidAmount, 
            ]);
            
            return;
        }

        // === TRƯỜNG HỢP 2: Không gửi details -> dùng số liệu hiện có từ DB ===
        if ($invoice) {
            $existingSubtotal = (float) ($invoice->subtotal_amount ?? 0.00);
            $existingTotal    = (float) ($invoice->total_amount ?? 0.00);
            $existingDiscount = (float) ($invoice->discount_amount ?? 0.00); // CK Header cũ

            if ($this->has('discount_amount')) {
                // Đổi CK HĐ -> tính lại total từ subtotal hiện có
                $invoiceDiscountOnly = min(round(max(0, $invoiceDiscountOnly), 2), $existingSubtotal);
                
                // Tính Total Amount MỚI (Subtotal - CK HĐ mới)
                $newTotal = round(max(0, $existingSubtotal - $invoiceDiscountOnly), 2);
                
                // discount_amount luôn là CK Header mới
                $newDiscountTotal  = $invoiceDiscountOnly; 
                
                $this->merge([
                    'subtotal_amount'        => $existingSubtotal,
                    'total_amount'           => $newTotal,
                    'discount_amount'        => $newDiscountTotal, // CHỈ LƯU CK HEADER MỚI
                    'invoice_discount_only'  => $invoiceDiscountOnly,
                    'paid_amount'            => $paidAmount, 
                ]);
            } else {
                // Trường hợp chỉ cập nhật paid_amount (Logic FIX)
                // Giữ nguyên số trên DB để rule lte:total_amount hoạt động
                $this->merge([
                    'subtotal_amount' => $existingSubtotal,
                    'total_amount'    => $existingTotal,
                    'discount_amount' => $existingDiscount, // CHỈ LƯU CK HEADER CŨ
                    'paid_amount'     => $paidAmount, 
                ]);
                // Tính invoice_discount_only cũ (bằng existingDiscount vì discount_amount giờ là CK Header)
                $invoiceDiscountOnly = $existingDiscount;
                $this->merge(['invoice_discount_only' => $invoiceDiscountOnly]);
            }
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
