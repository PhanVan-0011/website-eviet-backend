<?php

namespace App\Http\Requests\Api\Order;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class GetOrderRequest extends FormRequest
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
        $orderStatuses = ['draft', 'pending', 'processing', 'delivered', 'cancelled'];
        return [
            'keyword' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'string', Rule::in($orderStatuses)],
            'payment_method_code' => ['nullable', 'string', 'exists:payment_methods,code'],
            'start_date' => ['nullable', 'date_format:Y-m-d'],
            'end_date' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:start_date'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
    public function messages(): array
    {
        return [
            'status.in' => 'Giá trị của trạng thái không hợp lệ.',
            'payment_method_code.exists' => 'Mã phương thức thanh toán không tồn tại.',
            'start_date.date_format' => 'Ngày bắt đầu phải có định dạng Y-m-d.',
            'end_date.date_format' => 'Ngày kết thúc phải có định dạng Y-m-d.',
            'end_date.after_or_equal' => 'Ngày kết thúc phải lớn hơn hoặc bằng ngày bắt đầu.',
            'limit.integer' => 'Số mục trên mỗi trang phải là một số nguyên.',
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
