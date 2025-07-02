<?php

namespace App\Http\Requests\Api\Product;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreProductRequest extends FormRequest
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
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'size' => 'nullable|string|max:10',
            'original_price' => 'nullable|numeric|min:0',
            'sale_price' => 'nullable|numeric|min:0',
            'stock_quantity' => 'required|integer|min:0',

            'image_url' => 'nullable|array|max:4',
            
            // Mỗi file trong mảng 'image_url' phải là ảnh, đúng định dạng, và không quá 2MB
            'image_url.*' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
            
            // Chỉ số của ảnh đại diện (ví dụ: 0, 1, 2, 3) phải là số nguyên
            'featured_image_index' => 'nullable|integer|min:0',

            'status' => 'required|boolean',

            'category_ids' => 'required|array|min:1',
            'category_ids.*' => 'required|integer|exists:categories,id',
        ];
    }
    public function messages(): array
    {
        return [
            'name.required' => 'Tên sản phẩm là bắt buộc.',
            'name.max' => 'Tên sản phẩm không được dài quá 255 ký tự.',

            'description.string' => 'Mô tả phải là chuỗi ký tự.',

            'size.max' => 'Kích thước không được dài quá 10 ký tự.',

            'original_price.numeric' => 'Giá gốc phải là số.',
            'original_price.min' => 'Giá gốc không được nhỏ hơn 0.',

            'sale_price.numeric' => 'Giá khuyến mãi phải là số.',
            'sale_price.min' => 'Giá khuyến mãi không được nhỏ hơn 0.',

            'stock_quantity.required' => 'Số lượng tồn kho là bắt buộc.',
            'stock_quantity.integer' => 'Số lượng tồn kho phải là số nguyên.',
            'stock_quantity.min' => 'Số lượng tồn kho không được nhỏ hơn 0.',

            'status.required' => 'Trạng thái là bắt buộc.',
            'status.boolean' => 'Trạng thái phải là true hoặc false.',

            'category_ids.required' => 'Vui lòng chọn ít nhất một danh mục.',
            'category_ids.array' => 'Định dạng danh mục không hợp lệ.',
            'category_ids.*.exists' => 'Một trong các danh mục được chọn không tồn tại.',

             // --- THÔNG BÁO LỖI CHO UPLOAD NHIỀU ẢNH ---
            'image_url.array' => 'Định dạng ảnh không hợp lệ.',
            'image_url.max' => 'Chỉ được upload tối đa :max ảnh cho mỗi sản phẩm.',
            'image_url.*.required' => 'Vui lòng chọn file ảnh.',
            'image_url.*.image' => 'File phải là hình ảnh.',
            'image_url.*.mimes' => 'Mỗi hình ảnh phải có định dạng: jpeg, png, jpg, gif.',
            'image_url.*.max' => 'Kích thước mỗi hình ảnh không được vượt quá 2MB.',
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
