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

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */

    public function rules(): array
    {
        return [
            // --- 1. THÔNG TIN CHUNG & CHI NHÁNH ---
            'branch_id' => 'required|integer|exists:branches,id',
            'time_slot_id' => 'required|integer|exists:order_time_slots,id',

            'order_method' => ['required', 'string', Rule::in(['delivery', 'takeaway', 'dine_in'])],
            'notes' => 'nullable|string|max:500',

            'price_type' => ['nullable', 'string', Rule::in(['store', 'app'])],

            // --- THÔNG TIN KHÁCH HÀNG ---
            'client_name' => 'required|string|max:50',
            'client_phone' => ['required', 'string', 'regex:/^(0|\+84)(3[2-9]|5[6|8|9]|7[0|6-9]|8[1-9]|9[0-4|6-9])[0-9]{7}$/'],
            'user_id' => 'nullable|integer|exists:users,id', // Admin có thể chọn khách thành viên

            // --- LOGIC GIAO NHẬN ---
           
            'shipping_fee' => ['nullable', 'numeric', 'min:0'],

            // Điểm nhận hàng (Canteen/Xưởng) - Bắt buộc nếu là Delivery hoặc Takeaway
            'pickup_location_id' => [
                'nullable',
                'integer',
                'exists:pickup_locations,id',
                Rule::requiredIf(in_array($this->order_method, ['delivery', 'takeaway'])),
            ],

            // --- 4. THANH TOÁN ---
            'payment_method_code' => ['required', 'string', Rule::exists('payment_methods', 'code')->where('is_active', true)],

            // --- 5. CHI TIẾT SẢN PHẨM (ITEMS) ---
            'items' => ['required', 'array', 'min:1'],
            'items.*.type' => ['required', 'string', Rule::in(['product', 'combo'])],
            'items.*.id' => ['required', 'integer'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],

            
            'items.*.price' => ['nullable', 'numeric', 'min:0'],

            // Topping (chỉ dành cho product, gửi lên dạng mảng ID)
            'items.*.attribute_value_ids' => 'nullable|array',
            'items.*.attribute_value_ids.*' => 'integer|exists:attribute_values,id',
        ];
    }

    public function messages()
    {
        return [
            // Thông tin chung
            'branch_id.required' => 'Vui lòng chọn chi nhánh.',
            'branch_id.exists' => 'Chi nhánh không hợp lệ.',

            'time_slot_id.required' => 'Vui lòng chọn Ca/Khung giờ đặt hàng.',
            'time_slot_id.exists' => 'Khung giờ không hợp lệ.',

            'order_method.required' => 'Vui lòng chọn phương thức đặt hàng.',
            'order_method.in' => 'Phương thức đặt hàng không hợp lệ.',

            // Khách hàng
            'client_name.required' => 'Tên khách hàng là bắt buộc.',
            'client_phone.required' => 'Số điện thoại là bắt buộc.',
            'client_phone.regex' => 'Số điện thoại không đúng định dạng VN.',

            'price_type.in' => 'Loại giá không hợp lệ (chỉ chấp nhận store hoặc app).',

            // Giao nhận
            'shipping_address.required_if' => 'Vui lòng nhập địa chỉ giao hàng.',
            'pickup_time.after' => 'Giờ hẹn lấy phải sau thời điểm hiện tại.',
            'pickup_location_id.required_if' => 'Vui lòng chọn điểm nhận hàng (Canteen/Xưởng).',

            // Thanh toán
            'payment_method_code.required' => 'Vui lòng chọn phương thức thanh toán.',

            // Items
            'items.required' => 'Giỏ hàng không được để trống.',
            'items.min' => 'Giỏ hàng phải có ít nhất 1 món.',

            'items.*.type.required' => 'Loại sản phẩm không hợp lệ.',
            'items.*.id.required' => 'Thiếu ID sản phẩm/combo.',
            'items.*.quantity.min' => 'Số lượng tối thiểu là 1.',

            'items.*.price.numeric' => 'Giá sản phẩm/combo phải là số.',
            'items.*.price.min' => 'Giá sản phẩm/combo không được nhỏ hơn 0.',
        ];
    }

    /**
     * Thêm logic validation nghiệp vụ (kiểm tra tồn tại & trạng thái kinh doanh).
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $items = $this->input('items', []);

            // Gom nhóm ID để query 
            $productIds = [];
            $comboIds = [];

            foreach ($items as $index => $item) {
                if (empty($item['type']) || empty($item['id'])) continue;

                if ($item['type'] === 'product') {
                    $productIds[$item['id']][] = $index; // Lưu index để báo lỗi đúng dòng
                } elseif ($item['type'] === 'combo') {
                    $comboIds[$item['id']][] = $index;
                }
            }

            //Kiểm tra danh sách Product (1 Query duy nhất)
            if (!empty($productIds)) {
                $products = Product::whereIn('id', array_keys($productIds))->get()->keyBy('id');
                foreach ($productIds as $id => $indexes) {
                    if (!isset($products[$id])) {
                        foreach ($indexes as $index) $validator->errors()->add("items.{$index}.id", "Sản phẩm ID {$id} không tồn tại.");
                    } elseif ($products[$id]->status != 1) {
                        foreach ($indexes as $index) $validator->errors()->add("items.{$index}.id", "Sản phẩm '{$products[$id]->name}' đã ngừng kinh doanh.");
                    }
                }
            }

            //Kiểm tra danh sách Combo (1 Query duy nhất)
            if (!empty($comboIds)) {
                $combos = Combo::whereIn('id', array_keys($comboIds))->get()->keyBy('id');
                foreach ($comboIds as $id => $indexes) {
                    if (!isset($combos[$id])) {
                        foreach ($indexes as $index) $validator->errors()->add("items.{$index}.id", "Combo ID {$id} không tồn tại.");
                    } elseif (!$combos[$id]->is_active) {
                        foreach ($indexes as $index) $validator->errors()->add("items.{$index}.id", "Combo '{$combos[$id]->name}' đã ngừng hoạt động.");
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
