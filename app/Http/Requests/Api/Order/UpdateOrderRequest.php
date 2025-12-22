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
     * Chuẩn bị dữ liệu trước khi validate (Làm sạch dữ liệu)
     */
    protected function prepareForValidation(): void
    {
        // Xử lý số điện thoại: loại bỏ khoảng trắng nếu có update
        if ($this->has('client_phone')) {
            $this->merge(['client_phone' => preg_replace('/\s+/', '', $this->client_phone)]);
        }
        
        // Đảm bảo shipping_fee là số nếu có gửi lên
        if ($this->has('shipping_fee')) {
            $this->merge(['shipping_fee' => (float) $this->shipping_fee]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        // Danh sách trạng thái hợp lệ trong ENUM
        $allowedStatuses = ['draft', 'pending', 'processing', 'delivered', 'cancelled'];

        return [
            // Thông tin khách hàng
            'client_name' => ['sometimes', 'required', 'string', 'max:50'],
            
            'client_phone' => ['sometimes', 'required', 'string', 'regex:/^(0|\+84)(3[2-9]|5[6|8|9]|7[0|6-9]|8[1-9]|9[0-4|6-9])[0-9]{7}$/'],
            
            // Thông tin giao nhận
            'shipping_address' => ['sometimes', 'nullable', 'string', 'max:255'],
            'shipping_fee' => ['sometimes', 'required', 'numeric', 'min:0'],
            
            'notes' => ['sometimes', 'nullable', 'string', 'max:500'],
            'pickup_time' => ['sometimes', 'nullable', 'date_format:Y-m-d H:i:s'],

            // Trạng thái 
            'status' => ['sometimes', 'required', Rule::in($allowedStatuses)],
        ];
    }

    public function messages()
    {
        return [
            'client_name.required' => 'Tên khách hàng không được để trống.',
            'client_name.string' => 'Tên khách hàng phải là chuỗi ký tự.',
            'client_name.max' => 'Tên khách hàng quá dài.',
            
            'client_phone.required' => 'Số điện thoại không được để trống.',
            'client_phone.regex' => 'Số điện thoại không đúng định dạng Việt Nam.',
            
            'shipping_address.string' => 'Địa chỉ phải là chuỗi ký tự.',
            
            'shipping_fee.numeric' => 'Phí vận chuyển phải là số.',
            'shipping_fee.min' => 'Phí vận chuyển không được âm.',
            
            'notes.max' => 'Ghi chú không được vượt quá 500 ký tự.',
            'pickup_time.date_format' => 'Định dạng giờ hẹn lấy không hợp lệ (Y-m-d H:i:s).',

            'status.required' => 'Trạng thái đơn hàng là bắt buộc.',
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