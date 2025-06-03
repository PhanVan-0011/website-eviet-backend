<?php

namespace App\Http\Requests\Api\Order;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreOrderRequest extends FormRequest
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
            'client_name' => 'required|string|max:50',
            'client_phone' => 'required|string|max:11',
            'shipping_address' => 'required|string|max:255',
            'shipping_fee' => 'nullable|numeric',
            'status' => 'required|in:pending,processing,shipped,delivered,cancelled',
            'order_details' => 'required|array',
            'order_details.*.product_id' => 'required|exists:products,id',
            'order_details.*.quantity' => 'required|integer|min:1',    
        ];
    }
    public function messages()
    {
        return [
            'client_name.required' => 'Tên khách hàng không được để trống',
            'client_phone.required' => 'Số điện thoại khách hàng không được để trống',
            'client_phone.regex' => 'Số điện thoại không đúng định dạng',
            'shipping_address.required' => 'Địa chỉ giao hàng không được để trống',
            'order_details.required' => 'Phải có ít nhất một sản phẩm trong đơn hàng',
            'order_details.*.product_id.exists' => 'Sản phẩm không tồn tại',
            'order_details.*.quantity.min' => 'Số lượng phải lớn hơn hoặc bằng 1',
            'order_details.*.unit_price.min' => 'Giá đơn vị phải lớn hơn hoặc bằng 0',
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
