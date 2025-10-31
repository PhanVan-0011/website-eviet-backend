<?php

namespace App\Http\Requests\Api\Product;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;
use App\Models\Product;

class UpdateProductRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }
    protected function prepareForValidation(): void
    {
        if ($this->has('unit_conversions_json') && is_string($this->unit_conversions_json)) {
            $this->merge(['unit_conversions' => json_decode($this->unit_conversions_json, true) ?? []]);
        }
        if ($this->has('attributes_json') && is_string($this->attributes_json)) {
            $this->merge(['attributes' => json_decode($this->attributes_json, true) ?? []]);
        }
        // if ($this->has('branch_prices_json') && is_string($this->branch_prices_json)) {
        //     $this->merge(['branch_prices' => json_decode($this->branch_prices_json, true) ?? []]);
        // }
        if ($this->has('applies_to_all_branches')) {
            $this->merge(['applies_to_all_branches' => filter_var($this->applies_to_all_branches, FILTER_VALIDATE_BOOLEAN)]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $productId = $this->route('id');
        return [
            'name' => 'sometimes|required|string|max:255',
            'description' => 'sometimes|nullable|string',
            'product_code' => ['sometimes', 'nullable', 'string', 'max:255', Rule::unique('products')->ignore($productId)],

            'status' => 'sometimes|required|boolean',
            'category_ids' => 'sometimes|required|array|min:1',
            'category_ids.*' => 'sometimes|required|integer|exists:categories,id',

            'image_url' => 'sometimes|nullable|array',
            'image_url.*' => 'sometimes|required|image|mimes:jpeg,png,jpg,gif|max:2048',

            'deleted_image_ids' => 'sometimes|nullable|array',
            'deleted_image_ids.*' => 'sometimes|required|integer|exists:images,id',

            'deleted_image_ids.*' => [
                'sometimes',
                'required',
                'integer',
                Rule::exists('images', 'id')->where(function ($query) use ($productId) {
                    $query->where('imageable_id', $productId)
                        ->where('imageable_type', Product::class);
                }),
            ],

            'featured_image_index' => ['sometimes', 'nullable', 'integer', 'min:0'],

            // === ĐƠN VỊ TÍNH & GIÁ BÁN ===
            'base_unit' => 'sometimes|required|string|max:50',
            'cost_price' => 'sometimes|required|numeric|min:0',

            'base_store_price' => ['sometimes', 'required', 'numeric', 'min:0', Rule::when($this->filled('cost_price'), 'gte:cost_price')],
            'base_app_price' => ['sometimes', 'required', 'numeric', 'min:0', Rule::when($this->filled('cost_price'), 'gte:cost_price')],

            'is_sales_unit' => 'sometimes|required|boolean',
            'unit_conversions' => 'sometimes|array',
            'unit_conversions.*.unit_name' => 'required|string|max:50',
            'unit_conversions.*.unit_code' => ['nullable', 'string', 'max:255', 'distinct', Rule::unique('product_unit_conversions', 'unit_code')->where(function ($query) use ($productId) {
                return $query->where('product_id', '!=', $productId);
            })],
            //=== Đơn vị ====
            'unit_conversions.*.conversion_factor' => 'required|numeric|gt:0',
            'unit_conversions.*.store_price' => 'nullable|numeric|min:0',
            'unit_conversions.*.app_price' => 'nullable|numeric|min:0',
            'unit_conversions.*.is_sales_unit' => 'required|boolean',

            //=== THUỘC TÍNH ===
            'attributes' => 'sometimes|array',
            'attributes.*.name' => 'required|string|max:255',
            'attributes.*.type' => 'required|string|in:select,checkbox,text',
            'attributes.*.values' => 'nullable|array',
            'attributes.*.values.*.value' => 'required_unless:attributes.*.type,text|string|max:255',
            'attributes.*.values.*.price_adjustment' => 'required_unless:attributes.*.type,text|numeric',
            'attributes.*.values.*.is_default' => 'required_unless:attributes.*.type,text|boolean',

            // === PHÂN BỔ CHI NHÁNH ===
            'applies_to_all_branches' => 'sometimes|boolean',
            'branch_ids' => ['sometimes', 'nullable', 'array', Rule::requiredIf(function () {
                return $this->has('applies_to_all_branches') && !$this->input('applies_to_all_branches');
            })],
            'branch_ids.*' => 'integer|exists:branches,id',
            // GIÁ THEO CHI NHÁNH
            // 'branch_prices' => 'sometimes|array',
            // 'branch_prices.*.branch_id' => 'required|integer|exists:branches,id',
            // 'branch_prices.*.price_type' => 'required|string|in:store_price,app_price',
            // 'branch_prices.*.price' => 'required|numeric|min:0',
            // 'branch_prices.*.unit_of_measure' => 'required|string|max:50',
        ];
    }
    public function withValidator(Validator $validator): void
    {
        $validator->after(function ($validator) {
            $productId = $this->route('id');
            $product = Product::withCount('images')->findOrFail($productId);

            $deletedIds = (array) $this->input('deleted_image_ids', []);
            $newImages = (array) $this->file('image_url', []);

            //Kiểm tra tổng số ảnh cuối cùng không được vượt quá 4
            $finalImageCount = ($product->images_count - count($deletedIds)) + count($newImages);
            if ($finalImageCount > 4) {
                $validator->errors()->add('image_url', 'Tổng số ảnh của một sản phẩm không được vượt quá 4.');
            }

            //Kiểm tra chỉ số ảnh đại diện có hợp lệ không
            if ($this->filled('featured_image_index')) {
                $featuredIndex = (int) $this->input('featured_image_index');
                // Chỉ số phải nhỏ hơn tổng số ảnh cuối cùng
                if ($featuredIndex >= $finalImageCount) {
                    $validator->errors()->add('featured_image_index', 'Chỉ số ảnh đại diện không hợp lệ hoặc vượt quá số lượng ảnh.');
                }
            }

            // Lấy giá vốn mới nhất (từ request hoặc từ DB) để so sánh
            $costPrice = $this->filled('cost_price') ? (float)$this->input('cost_price') : (float)$product->cost_price;
            $unitConversions = $this->input('unit_conversions', []);

            foreach ($unitConversions as $index => $unit) {
                $factor = (float) ($unit['conversion_factor'] ?? 1);
                $minPrice = $costPrice * $factor;
                if (isset($unit['store_price']) && (float) $unit['store_price'] < $minPrice) {
                    $validator->errors()->add("unit_conversions.{$index}.store_price", "Giá bán tại cửa hàng của ĐVT phải >= giá vốn quy đổi ({$minPrice}).");
                }
                if (isset($unit['app_price']) && (float) $unit['app_price'] < $minPrice) {
                    $validator->errors()->add("unit_conversions.{$index}.app_price", "Giá bán trên app của ĐVT phải >= giá vốn quy đổi ({$minPrice}).");
                }
            }
        });
    }
    public function messages(): array
    {
        $productId = $this->route('id');
        return [
            'name.string' => 'Tên sản phẩm phải là chuỗi ký tự.',

            'name.max' => 'Tên sản phẩm không được dài quá 255 ký tự.',

            'description.string' => 'Mô tả phải là chuỗi ký tự.',

            'status.boolean' => 'Trạng thái phải là true hoặc false.',

            'category_ids.required' => 'Vui lòng chọn ít nhất một danh mục.',
            'category_ids.array' => 'Định dạng danh mục không hợp lệ.',
            'category_ids.*.exists' => 'Một trong các danh mục được chọn không tồn tại.',
            // images
            'image_url.array' => 'Định dạng ảnh tải lên không hợp lệ.',
            'image_url.max' => 'Chỉ được upload tối đa :max ảnh cho mỗi sản phẩm.',
            'image_url.*.image' => 'Mỗi file tải lên phải là hình ảnh.',
            'image_url.*.mimes' => 'Mỗi hình ảnh phải có định dạng: jpeg, png, jpg, gif.',
            'image_url.*.max' => 'Kích thước mỗi hình ảnh không được vượt quá 2MB.',

            'deleted_image_ids.array' => 'Định dạng ID ảnh cần xóa không hợp lệ.',
            'deleted_image_ids.*.exists' => 'ID ảnh cần xóa không tồn tại.',

            'featured_image_index.integer' => 'Chỉ số ảnh đại diện phải là một số nguyên.',
            'featured_image_index.min' => 'Vui lòng chọn ảnh đại diện.',

            // Đơn vị tính & giá
            'base_unit.required' => 'Tên đơn vị cơ sở là bắt buộc.',
            'cost_price.required' => 'Giá vốn là bắt buộc.',
            'cost_price.min' => 'Giá vốn phải lớn hơn hoặc bằng 0.',
            'base_store_price.required' => 'Giá bán tại cửa hàng là bắt buộc.',
            'base_store_price.min' => 'Giá bán tại cửa hàng phải lớn hơn hoặc bằng 0.',
            'base_app_price.required' => 'Giá bán trên ứng dụng là bắt buộc.',
            'base_app_price.min' => 'Giá bán trên ứng dụng phải lớn hơn hoặc bằng 0.',

            // Đơn vị quy đổi
            'unit_conversions.*.unit_name.required' => 'Tên đơn vị quy đổi không được để trống.',
            'unit_conversions.*.unit_code.required' => 'Mã hàng của đơn vị quy đổi không được để trống.',
            'unit_conversions.*.unit_code.unique' => 'Mã hàng của đơn vị quy đổi đã tồn tại.',
            'unit_conversions.*.unit_code.distinct' => 'Các mã hàng của đơn vị quy đổi không được trùng nhau.',
            'unit_conversions.*.conversion_factor.required' => 'Giá trị quy đổi là bắt buộc.',
            'unit_conversions.*.conversion_factor.gt' => 'Giá trị quy đổi phải lớn hơn 0.',

            // Thuộc tính
            'attributes.*.name.required' => 'Tên thuộc tính không được để trống.',
            'attributes.*.type.required' => 'Vui lòng chọn loại thuộc tính.',
            'attributes.*.type.in' => 'Loại thuộc tính không hợp lệ.',
            'attributes.*.values.*.value.required_unless' => 'Tên giá trị thuộc tính không được để trống.',
            'attributes.*.values.*.price_adjustment.required_unless' => 'Giá trị điều chỉnh là bắt buộc.',

            // Chi nhánh
            'branch_ids.*.exists' => 'Chi nhánh được chọn không hợp lệ.',
            'branch_ids.required_unless' => 'Vui lòng chọn chi nhánh nếu không áp dụng cho tất cả.'
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
