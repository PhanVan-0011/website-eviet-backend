<?php

namespace App\Http\Requests\Api\Promotion;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class UpdatePromotionRequest extends FormRequest
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
        $promotionId = $this->route('promotion')->id;
        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50', Rule::unique('promotions', 'code')->ignore($promotionId)],
            'description' => ['nullable', 'string'],
            'application_type' => ['required', 'string', Rule::in(['all_orders', 'specific_products', 'specific_categories', 'specific_combos'])],
            'type' => ['required', 'string', Rule::in(['percentage', 'fixed_amount', 'free_shipping'])],
            'value' => ['required', 'numeric', 'min:0'],
            'min_order_value' => ['nullable', 'numeric', 'min:0'],
            'max_discount_amount' => ['nullable', 'numeric', 'min:0'],
            'max_usage' => ['nullable', 'integer', 'min:1'],
            'max_usage_per_user' => ['nullable', 'integer', 'min:1'],
            'is_combinable' => ['required', 'boolean'],
            'is_active' => ['required', 'boolean'],
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            
            // Dùng 'sometimes' để chỉ validate nếu trường được gửi lên
            'product_ids' => ['sometimes', 'nullable', 'array'],
            'product_ids.*' => ['integer', 'exists:products,id'],
            'category_ids' => ['sometimes', 'nullable', 'array'],
            'category_ids.*' => ['integer', 'exists:categories,id'],
            'combo_ids' => ['sometimes', 'nullable', 'array'],
            'combo_ids.*' => ['integer', 'exists:combos,id'],
        ];
    }
     public function messages(): array
    {
        return [
            'name.required' => 'Tên chương trình khuyến mãi không được để trống.',

            'code.required' => 'Mã khuyến mãi không được để trống.',
            'code.unique' => 'Mã khuyến mãi này đã tồn tại.',

            'application_type.required' => 'Vui lòng chọn phạm vi áp dụng.',
            'type.required' => 'Vui lòng chọn loại khuyến mãi.',
            'value.required' => 'Giá trị khuyến mãi không được để trống.',
            'is_combinable.required' => 'Vui lòng chọn cho phép kết hợp hay không.',
            'is_active.required' => 'Vui lòng chọn trạng thái hoạt động.',
            'start_date.required' => 'Ngày bắt đầu là bắt buộc.',
            'end_date.after_or_equal' => 'Ngày kết thúc phải lớn hơn hoặc bằng ngày bắt đầu.',
            'product_ids.required_if' => 'Vui lòng chọn ít nhất một sản phẩm.',
            'category_ids.required_if' => 'Vui lòng chọn ít nhất một danh mục.',
            'combo_ids.required_if' => 'Vui lòng chọn ít nhất một combo.',
            '*.exists' => 'ID được chọn không hợp lệ hoặc không tồn tại.',
        ];
    }

    protected function failedValidation(\Illuminate\Contracts\Validation\Validator $validator): void
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'errors' => $validator->errors(),
        ], 422));
    }
}
