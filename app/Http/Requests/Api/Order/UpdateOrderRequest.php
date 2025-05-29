<?php

namespace App\Http\Requests\Api\Order;

use Illuminate\Foundation\Http\FormRequest;

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
            'client_name' => 'sometimes|string|max:50',
            'client_phone' => 'sometimes|string|max:11',
            'shipping_address' => 'sometimes|string|max:255',
            'shipping_fee' => 'sometimes|numeric',
            'status' => 'sometimes|in:pending,processing,shipped,delivered,cancelled',
            'order_details' => 'sometimes|array',
            'order_details.*.product_id' => 'required_with:order_details|exists:products,id',
            'order_details.*.quantity' => 'required_with:order_details|integer|min:1',
        ];
    }
}
