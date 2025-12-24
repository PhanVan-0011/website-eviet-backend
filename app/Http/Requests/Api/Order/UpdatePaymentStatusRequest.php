<?php

namespace App\Http\Requests\Api\Order;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdatePaymentStatusRequest extends FormRequest
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
     */
    public function rules(): array
    {
        // Các trạng thái thanh toán hợp lệ
        $allowedStatuses = ['pending', 'success', 'failed'];
        
        return [
            'status' => ['required', 'string', Rule::in($allowedStatuses)],
            'paid_at' => ['nullable', 'date'], // Admin có thể chỉnh ngày thanh toán thực tế
        ];
    }

    public function messages(): array
    {
        return [
            'status.required' => 'Trường trạng thái thanh toán là bắt buộc.',
            'status.in' => 'Trạng thái thanh toán không hợp lệ (chỉ chấp nhận: pending, success, failed).',
            'paid_at.date' => 'Ngày thanh toán không hợp lệ.',
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
