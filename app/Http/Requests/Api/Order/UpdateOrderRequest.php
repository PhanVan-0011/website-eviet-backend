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

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        // Lấy các giá trị trạng thái hợp lệ từ ENUM của CSDL
         $allowedStatuses = ['pending', 'processing', 'shipped', 'delivered', 'cancelled'];
        return [
            'client_name' => ['sometimes', 'required', 'string', 'max:50'],
            'client_phone' => ['sometimes', 'required', 'string', 'max:11'],
            'shipping_address' => ['sometimes', 'required', 'string', 'max:255'],
            'shipping_fee' => ['sometimes', 'required', 'numeric', 'min:0'],
            'status' => ['sometimes', 'required', Rule::in($allowedStatuses)],
        ];
    }
    public function messages()
    {
        return [
            'client_name.required' => 'Tên khách hàng không được để trống.',
            'client_phone.required' => 'Số điện thoại không được để trống.',
            'shipping_address.required' => 'Địa chỉ giao hàng không được để trống.',
            'status.in' => 'Trạng thái đơn hàng không hợp lệ.',
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
