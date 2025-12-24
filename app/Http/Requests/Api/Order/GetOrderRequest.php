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
     */
    public function rules(): array
    {
        // Loại bỏ 'draft' khỏi danh sách tìm kiếm
        $orderStatuses = ['pending', 'processing', 'delivered', 'cancelled'];
        $orderMethods = ['delivery', 'takeaway', 'dine_in'];

        return [
            'keyword' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'string', Rule::in($orderStatuses)],
            'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
            'order_method' => ['nullable', 'string', Rule::in($orderMethods)],
            'payment_method_code' => ['nullable', 'string', 'exists:payment_methods,code'],
            
            'start_date' => ['nullable', 'date_format:Y-m-d'],
            'end_date' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:start_date'],
            
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
            
            // Bổ sung user_id nếu admin muốn lọc theo khách hàng cụ thể
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'status.in' => 'Trạng thái đơn hàng không hợp lệ.',
            'branch_id.integer' => 'ID chi nhánh phải là số nguyên.',
            'branch_id.exists' => 'Chi nhánh không tồn tại.',
            'order_method.in' => 'Phương thức đặt hàng không hợp lệ.',
            'payment_method_code.exists' => 'Mã phương thức thanh toán không tồn tại.',
            'start_date.date_format' => 'Ngày bắt đầu phải có định dạng Y-m-d.',
            'end_date.date_format' => 'Ngày kết thúc phải có định dạng Y-m-d.',
            'end_date.after_or_equal' => 'Ngày kết thúc phải lớn hơn hoặc bằng ngày bắt đầu.',
            'limit.integer' => 'Số mục trên mỗi trang phải là một số nguyên.',
            'page.integer' => 'Trang phải là một số nguyên.',
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

