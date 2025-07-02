<?php

namespace App\Http\Requests\Api\Product;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateProductRequest extends FormRequest
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
            'name' => 'sometimes|required|string|max:255',
            'description' => 'sometimes|nullable|string',
            'size' => 'sometimes|nullable|string|max:10',
            'original_price' => 'sometimes|nullable|numeric|min:0',
            'sale_price' => 'sometimes|nullable|numeric|min:0|lte:original_price',
            'stock_quantity' => 'sometimes|required|integer|min:0',
            'status' => 'sometimes|required|boolean',
            'category_ids' => 'sometimes|required|array|min:1',
            'category_ids.*' => 'sometimes|required|integer|exists:categories,id',

            'image_url' => 'sometimes|nullable|array|max:4', // Giới hạn tổng số ảnh
            'image_url.*' => 'sometimes|required|image|mimes:jpeg,png,jpg,gif|max:2048',

            // 2. Mảng các ID của ảnh cũ cần xóa
            'deleted_image_ids' => 'sometimes|nullable|array',
            'deleted_image_ids.*' => 'sometimes|required|integer|exists:images,id',

            // 3. ID của ảnh mới được chọn làm ảnh đại diện
            'featured_image_id' => 'sometimes|nullable|integer|exists:images,id',
        ];
    }
    public function messages(): array
    {
        return [
            'name.string' => 'Tên sản phẩm phải là chuỗi ký tự.',

            'name.max' => 'Tên sản phẩm không được dài quá 255 ký tự.',

            'description.string' => 'Mô tả phải là chuỗi ký tự.',

            'size.max' => 'Kích thước không được dài quá 10 ký tự.',

            'original_price.numeric' => 'Giá gốc phải là số.',
            'original_price.min' => 'Giá gốc không được nhỏ hơn 0.',

            'sale_price.numeric' => 'Giá khuyến mãi phải là số.',
            'sale_price.min' => 'Giá khuyến mãi không được nhỏ hơn 0.',

            'stock_quantity.integer' => 'Số lượng tồn kho phải là số nguyên.',
            'stock_quantity.min' => 'Số lượng tồn kho không được nhỏ hơn 0.',

            'status.boolean' => 'Trạng thái phải là true hoặc false.',

            'category_ids.required' => 'Vui lòng chọn ít nhất một danh mục.',
            'category_ids.array' => 'Định dạng danh mục không hợp lệ.',
            'category_ids.*.exists' => 'Một trong các danh mục được chọn không tồn tại.',

            'image_url.array' => 'Định dạng ảnh tải lên không hợp lệ.',
            'image_url.max' => 'Chỉ được upload tối đa :max ảnh cho mỗi sản phẩm.',
            'image_url.*.image' => 'Mỗi file tải lên phải là hình ảnh.',
            'image_url.*.mimes' => 'Mỗi hình ảnh phải có định dạng: jpeg, png, jpg, gif.',
            'image_url.*.max' => 'Kích thước mỗi hình ảnh không được vượt quá 2MB.',
            'deleted_image_ids.array' => 'Định dạng ID ảnh cần xóa không hợp lệ.',
            'deleted_image_ids.*.exists' => 'ID ảnh cần xóa không tồn tại.',
            'featured_image_id.exists' => 'ID ảnh đại diện được chọn không tồn tại.',
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
