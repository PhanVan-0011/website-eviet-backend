<?php

namespace App\Http\Requests\Api\Promotion;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class StorePromotionRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50', 'unique:promotions,code'],
            'description' => ['nullable', 'string'],
            'application_type' => ['required', 'string', Rule::in(['orders', 'products', 'categories', 'combos'])],
            'type' => ['required', 'string', Rule::in(['percentage', 'fixed_amount', 'free_shipping'])],
            'value' => ['required', 'numeric', 'min:0'],

            'image_url' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',

            'min_order_value' => ['nullable', 'numeric', 'min:0'],
            'max_discount_amount' => ['nullable', 'numeric', 'min:0'],
            'max_usage' => ['nullable', 'integer', 'min:1'],
            'max_usage_per_user' => ['nullable', 'integer', 'min:1'],
            'is_combinable' => ['required', 'boolean'],
            'is_active' => ['required', 'boolean'],
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],

            'product_ids' => ['required_if:application_type,products', 'prohibited_unless:application_type,products', 'nullable', 'array'],
            'product_ids.*' => ['integer', 'exists:products,id'],
            
            'category_ids' => ['required_if:application_type,categories', 'prohibited_unless:application_type,categories', 'nullable', 'array'],
            'category_ids.*' => ['integer', 'exists:categories,id'],
            
            'combo_ids' => ['required_if:application_type,combos', 'prohibited_unless:application_type,combos', 'nullable', 'array'],
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

            'image_url.image' => 'File tải lên phải là hình ảnh.',
            'image_url.mimes' => 'Ảnh phải có định dạng: jpeg, png, jpg, gif.',
            'image_url.max' => 'Kích thước ảnh không được vượt quá 2MB.',

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

            'product_ids.prohibited_unless' => 'Không được chọn sản phẩm vì loại áp dụng không phải là products.',
            'category_ids.prohibited_unless' => 'Không được chọn danh mục vì loại áp dụng không phải là categories.',
            'combo_ids.prohibited_unless' => 'Không được chọn combo vì loại áp dụng không phải là combos.',
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
