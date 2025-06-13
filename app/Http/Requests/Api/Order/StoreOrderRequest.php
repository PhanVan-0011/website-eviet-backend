<?php

namespace App\Http\Requests\Api\Order;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;
use App\Models\Product;
use App\Models\Combo;

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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // Dữ liệu khách hàng 
            'client_name' => 'required|string|max:50',
            'client_phone' => 'required|string|max:11',
            'shipping_address' => 'required|string|max:255',

            // Dữ liệu đơn hàng
            'shipping_fee' => 'required|numeric|min:0',
            'payment_method_code' => ['required', 'string', Rule::exists('payment_methods', 'code')->where('is_active', true)],

            // Dữ liệu sản phẩm/combo
            'items' => ['required', 'array', 'min:1'],
            'items.*' => ['array'],
            'items.*.type' => ['required', 'string', Rule::in(['product', 'combo'])],
            'items.*.id' => ['required', 'integer'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
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

            'order_details.required' => 'Đơn hàng phải có ít nhất một sản phẩm',
            'order_details.array' => 'Chi tiết đơn hàng phải là mảng',

            'order_details.*.product_id.required' => 'Thiếu ID sản phẩm',
            'order_details.*.product_id.exists' => 'Sản phẩm không tồn tại trong hệ thống',

            'order_details.*.quantity.required' => 'Thiếu số lượng sản phẩm',
            'order_details.*.quantity.integer' => 'Số lượng phải là số nguyên',
            'order_details.*.quantity.min' => 'Số lượng tối thiểu là 1',

            'payment.array' => 'Thông tin thanh toán phải là mảng',
            'payment.paid_at.date' => 'Thời gian thanh toán không hợp lệ',

            'shipping_fee.required' => 'Phí vận chuyển là bắt buộc.',
            'payment_method_code.required' => 'Vui lòng chọn phương thức thanh toán.',
            'payment_method_code.exists' => 'Phương thức thanh toán không hợp lệ.',

            'items.required' => 'Đơn hàng phải có ít nhất một sản phẩm.',
            'items.*.id.required' => 'Thiếu ID của sản phẩm/combo.',
            'items.*.quantity.min' => 'Số lượng tối thiểu là 1.',
        ];
    }
    /**
     * Thêm các logic validation kiểm tra.
     *
     * @param  \Illuminate\Validation\Validator  $validator
     * @return void
     */
   public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            foreach ($this->input('items', []) as $key => $item) {
                if (empty($item['type']) || empty($item['id'])) {
                    continue;
                }
                $type = $item['type'];
                $id = $item['id'];

                if ($type === 'product') {
                    //kiểm tra sản phẩm
                    $product = Product::find($id);
                    if (!$product) {
                        $validator->errors()->add("items.{$key}.id", "Sản phẩm với ID {$id} không tồn tại.");
                    } elseif ($product->status != 1) { 
                        $validator->errors()->add("items.{$key}.id", "Sản phẩm '{$product->name}' (ID: {$id}) đã ngừng kinh doanh.");
                    }
                } elseif ($type === 'combo') {
                    $combo = Combo::find($id); 
                    
                    if (!$combo) {
                        // Nếu không tìm thấy, báo lỗi ID không tồn tại
                        $validator->errors()->add("items.{$key}.id", "Combo với ID {$id} không tồn tại.");
                    } elseif (!$combo->is_active) {
                        // Nếu tìm thấy, nhưng không active, báo lỗi combo đã ngừng áp dụng
                        $validator->errors()->add("items.{$key}.id", "Combo '{$combo->name}' (ID: {$id}) đã ngừng áp dụng.");
                    }
                }
            }
        });
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
