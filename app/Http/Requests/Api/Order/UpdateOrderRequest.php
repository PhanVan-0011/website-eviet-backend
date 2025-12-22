<?php

namespace App\Http\Requests\Api\Order;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class UpdateOrderRequest extends FormRequest
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
        if ($this->has('client_phone')) {
            $this->merge(['client_phone' => preg_replace('/\s+/', '', $this->client_phone)]);
        }
        if ($this->has('shipping_fee')) {
            $this->merge(['shipping_fee' => (float) $this->shipping_fee]);
        }
        if ($this->has('discount_amount')) {
            $this->merge(['discount_amount' => (float) $this->discount_amount]);
        }
    }

    public function rules(): array
    {
        $allowedStatuses = ['pending', 'processing', 'delivered', 'cancelled'];
        $orderMethods = ['delivery', 'takeaway', 'dine_in'];
        $priceTypes = ['store', 'app'];

        return [
            // --- THÔNG TIN CHUNG ---
            'branch_id' => ['sometimes', 'integer', 'exists:branches,id'],
            'time_slot_id' => ['sometimes', 'integer', 'exists:order_time_slots,id'],
            'order_method' => ['sometimes', 'string', Rule::in($orderMethods)],
            'price_type' => ['sometimes', 'string', Rule::in($priceTypes)],
            'order_date' => ['sometimes', 'date'],

            // --- THÔNG TIN KHÁCH HÀNG ---
            'client_name' => ['sometimes', 'required', 'string', 'max:50'],
            'client_phone' => ['sometimes', 'required', 'string', 'regex:/^(0|\+84)(3[2-9]|5[6|8|9]|7[0|6-9]|8[1-9]|9[0-4|6-9])[0-9]{7}$/'],
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'notes' => ['sometimes', 'nullable', 'string', 'max:500'],

            // --- GIAO NHẬN ---
            'pickup_location_id' => [
                'sometimes', 'nullable', 'integer', 'exists:pickup_locations,id',
            ],
            
            // --- TÀI CHÍNH ---
            'shipping_fee' => ['sometimes', 'numeric', 'min:0'],
            'discount_amount' => ['sometimes', 'numeric', 'min:0'],
            'payment_method_code' => ['sometimes', 'string', 'exists:payment_methods,code'],

            // --- TRẠNG THÁI ---
            'status' => ['sometimes', 'string', Rule::in($allowedStatuses)],
            
            // --- CHI TIẾT SẢN PHẨM (ITEMS) ---
            'items' => ['sometimes', 'array', 'min:1'],
            'items.*.type' => ['required_with:items', 'string', Rule::in(['product', 'combo'])],
            'items.*.id' => ['required_with:items', 'integer'],
            'items.*.quantity' => ['required_with:items', 'integer', 'min:1'],
            'items.*.unit_of_measure' => ['nullable', 'string'],
            'items.*.price' => ['nullable', 'numeric', 'min:0'],
            'items.*.item_discount' => ['nullable', 'numeric', 'min:0'],
            'items.*.attribute_value_ids' => ['nullable', 'array'],
            'items.*.attribute_value_ids.*' => ['integer', 'exists:attribute_values,id'],
        ];
    }

    public function messages()
    {
        return [
            'branch_id.exists' => 'Chi nhánh không hợp lệ.',
            'time_slot_id.exists' => 'Ca làm việc không hợp lệ.',
            'order_method.in' => 'Phương thức đặt hàng không hợp lệ.',
            'price_type.in' => 'Loại giá không hợp lệ.',
            'client_phone.regex' => 'Số điện thoại không đúng định dạng.',
            'pickup_location_id.exists' => 'Điểm nhận hàng không tồn tại.',
            'status.in' => 'Trạng thái đơn hàng không hợp lệ.',
            'shipping_fee.numeric' => 'Phí vận chuyển phải là số.',
            'discount_amount.numeric' => 'Số tiền giảm giá phải là số.',
            'items.min' => 'Phải có ít nhất 1 món trong đơn hàng.',
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