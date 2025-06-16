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
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'code' => ['sometimes', 'required', 'string', 'max:50', Rule::unique('promotions', 'code')->ignore($promotionId)],
            'description' => ['sometimes', 'nullable', 'string'],
            'application_type' => ['sometimes', 'required', 'string', Rule::in(['orders', 'products', 'categories', 'combos'])],
            'type' => ['sometimes', 'required', 'string', Rule::in(['percentage', 'fixed_amount', 'free_shipping'])],
            'value' => ['sometimes', 'required', 'numeric', 'min:0'],
            'min_order_value' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'max_discount_amount' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'max_usage' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'max_usage_per_user' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'is_combinable' => ['sometimes', 'required', 'boolean'],
            'is_active' => ['sometimes', 'required', 'boolean'],
            'start_date' => ['sometimes', 'required', 'date'],
            'end_date' => ['sometimes', 'nullable', 'date', 'after_or_equal:start_date'],
            
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
            'application_type.required' => 'Vui lòng chọn phạm vi áp dụng.',
            'type.required' => 'Vui lòng chọn loại khuyến mãi.',
            'value.required' => 'Giá trị khuyến mãi không được để trống.',
            'start_date.required' => 'Ngày bắt đầu là bắt buộc.',

            'code.unique' => 'Mã khuyến mãi này đã tồn tại.',
            'application_type.in' => 'Phạm vi áp dụng đã chọn không hợp lệ.',
            'type.in' => 'Loại khuyến mãi đã chọn không hợp lệ.',
            'value.numeric' => 'Giá trị khuyến mãi phải là một số.',
            'value.min' => 'Giá trị khuyến mãi phải lớn hơn hoặc bằng 0.', 
            'start_date.date' => 'Định dạng ngày bắt đầu không hợp lệ.',
            'end_date.date' => 'Định dạng ngày kết thúc không hợp lệ.',
            'end_date.after_or_equal' => 'Ngày kết thúc phải lớn hơn hoặc bằng ngày bắt đầu.',

            'product_ids.required_if' => 'Vui lòng chọn ít nhất một sản phẩm.',
            'category_ids.required_if' => 'Vui lòng chọn ít nhất một danh mục.',
            'combo_ids.required_if' => 'Vui lòng chọn ít nhất một combo.',

            'product_ids.*.exists' => 'Một hoặc nhiều ID sản phẩm không tồn tại.',
            'category_ids.*.exists' => 'Một hoặc nhiều ID danh mục không tồn tại.',
            'combo_ids.*.exists' => 'Một hoặc nhiều ID combo không tồn tại.',
        ];
    }

    protected function failedValidation(\Illuminate\Contracts\Validation\Validator $validator): void
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => $validator->errors()->first(),
            'errors' => $validator->errors(),
        ], 422));
    }
}
