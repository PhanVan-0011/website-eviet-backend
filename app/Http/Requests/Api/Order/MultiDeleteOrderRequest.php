<?php

namespace App\Http\Requests\Api\Order;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class MultiDeleteOrderRequest extends FormRequest
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
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer|exists:orders,id',
        ];
    }
     public function messages(): array
    {
        return [
            'ids.required' => 'Danh sách ID đơn hàng là bắt buộc',
            'ids.array' => 'Danh sách ID phải là mảng',
            'ids.min' => 'Phải chọn ít nhất một ID để xóa',
            'ids.*.integer' => 'ID phải là số nguyên',
            'ids.*.exists' => 'Đơn hàng với ID :input không tồn tại',
        ];
    }
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors(),
            ], 422)
        );
    }
}
