<?php

namespace App\Http\Requests\Api\Order;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
class UpdateOrderRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'client_name' => 'sometimes|required|string|max:50',
            'client_phone' => 'sometimes|required|string|max:11',
            'shipping_address' => 'sometimes|required|max:255',
            'shipping_fee' => 'nullable|numeric',
            'status' => 'sometimes|required|in:pending,processing,shipped,delivered,cancelled',
            'order_details' => 'nullable|array|min:1',
            'order_details.*.product_id' => 'required_with:order_details|exists:products,id',
            'order_details.*.quantity' => 'required_with:order_details|integer|min:1',
            'payment' => 'nullable|array',
            'payment.gateway' => 'nullable|string',
            'payment.status' => 'nullable|string',
            'payment.transaction_id' => 'nullable|string',
            'payment.paid_at' => 'nullable|date',
            'payment.callback_data' => 'nullable|string',
        ];

    }
    public function messages()
    {
        return [
            'client_name.required' => 'Tên khách hàng không được để trống',
            'client_name.string' => 'Tên khách hàng phải là chuỗi',
            'client_name.max' => 'Tên khách hàng không được vượt quá 50 ký tự',

            'client_phone.required' => 'Số điện thoại không được để trống',
            'client_phone.string' => 'Số điện thoại phải là chuỗi',
            'client_phone.max' => 'Số điện thoại không được vượt quá 11 số',

            'shipping_address.required' => 'Địa chỉ giao hàng không được để trống',
            'shipping_address.max' => 'Địa chỉ không được vượt quá 255 ký tự',

            'shipping_fee.numeric' => 'Phí vận chuyển phải là số',

            'status.required' => 'Trạng thái đơn hàng là bắt buộc',
            'status.in' => 'Trạng thái đơn hàng không hợp lệ',

            'order_details.array' => 'Chi tiết đơn hàng phải là mảng',
            'order_details.*.product_id.required_with' => 'Thiếu ID sản phẩm trong đơn hàng',
            'order_details.*.product_id.exists' => 'Sản phẩm không tồn tại',
            'order_details.*.quantity.required_with' => 'Thiếu số lượng sản phẩm',
            'order_details.*.quantity.integer' => 'Số lượng phải là số nguyên',
            'order_details.*.quantity.min' => 'Số lượng tối thiểu là 1',

            'payment.array' => 'Thông tin thanh toán phải là mảng',
            'payment.paid_at.date' => 'Thời gian thanh toán không hợp lệ',
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
