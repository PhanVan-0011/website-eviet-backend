<?php

namespace App\Http\Requests\Api\Product;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

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
     * Chuẩn bị dữ liệu trước khi validation.
     * Chuyển đổi các chuỗi JSON thành mảng PHP.
     */
    protected function prepareForValidation(): void
    {
        // Chuyển đổi JSON string của đơn vị tính thành mảng
        if ($this->has('unit_conversions_json') && is_string($this->unit_conversions_json)) {
            $decoded = json_decode($this->unit_conversions_json, true);
            $this->merge([
                'unit_conversions' => is_array($decoded) ? $decoded : [],
            ]);
        }

        // Chuyển đổi JSON string của thuộc tính thành mảng
        if ($this->has('attributes_json') && is_string($this->attributes_json)) {
            $decoded = json_decode($this->attributes_json, true);
            $this->merge([
                'attributes' => is_array($decoded) ? $decoded : [],
            ]);
        }
        // //Chuyển đổi giá khác nhau theo chi nhánh
        // if ($this->has('branch_prices_json') && is_string($this->branch_prices_json)) {
        //     $this->merge(['branch_prices' => json_decode($this->branch_prices_json, true) ?? []]);
        // }

        // Chọn toàn bộ chi nhánh để thêm sản phẩm
        if ($this->has('applies_to_all_branches')) {
            $this->merge([
                'applies_to_all_branches' => filter_var($this->applies_to_all_branches, FILTER_VALIDATE_BOOLEAN),
            ]);
        }
    }
    /**
     * Thêm các quy tắc validation tùy chỉnh sau khi các quy tắc cơ bản đã được áp dụng.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function ($validator) {
            $costPrice = (float) $this->input('cost_price', 0);
            $unitConversions = $this->input('unit_conversions', []);

            foreach ($unitConversions as $index => $unit) {
                $factor = (float) ($unit['conversion_factor'] ?? 1);
                $minPrice = $costPrice * $factor;

                // ---- Làm sạch dữ liệu đầu vào ----
                $storePrice = $unit['store_price'] ?? null;
                $appPrice   = $unit['app_price'] ?? null;

                // nếu chuỗi rỗng "" thì chuyển thành null
                if ($storePrice === '') {
                    $storePrice = null;
                }
                if ($appPrice === '') {
                    $appPrice = null;
                }

                // ---- Chỉ validate nếu người dùng có nhập giá ----
                if ($storePrice !== null && (float) $storePrice < $minPrice) {
                    $validator->errors()->add(
                        "unit_conversions.{$index}.store_price",
                        "Giá bán tại cửa hàng của đơn vị quy đổi phải lớn hơn hoặc bằng giá vốn quy đổi ({$minPrice})."
                    );
                }

                if ($appPrice !== null && (float) $appPrice < $minPrice) {
                    $validator->errors()->add(
                        "unit_conversions.{$index}.app_price",
                        "Giá bán trên app của đơn vị quy đổi phải lớn hơn hoặc bằng giá vốn quy đổi ({$minPrice})."
                    );
                }
            }
        });
    }


    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            //'product_code' => 'required|string|max:255|unique:products,product_code',
            'product_code' => 'nullable|string|max:255|unique:products,product_code',

            'name' => 'required|string|max:255',
            'description' => 'nullable|string',

            //ẢNH
            'image_url' => 'nullable|array|max:4',
            'image_url.*' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
            'featured_image_index' => 'nullable|integer|min:0',
            'status' => 'required|boolean',

            // DANH MỤC
            'category_ids' => 'required|array|min:1',
            'category_ids.*' => 'required|integer|exists:categories,id',

            // ===  ĐƠN VỊ TÍNH & GIÁ BÁN ===
            'base_unit' => 'required|string|max:50',
            'cost_price' => 'required|numeric|min:0',
            'base_store_price' => 'required|numeric|min:0|gte:cost_price', //ràng buộc giá
            'base_app_price' => 'required|numeric|min:0|gte:cost_price',
            'is_sales_unit' => 'required|boolean',

            'unit_conversions' => 'nullable|array',
            'unit_conversions.*.unit_name' => 'required|string|max:50',
            'unit_conversions.*.unit_code' => ['nullable', 'string', 'max:255', 'distinct', Rule::unique('product_unit_conversions', 'unit_code')],
            'unit_conversions.*.conversion_factor' => 'required|numeric|gt:0',
            'unit_conversions.*.store_price' => 'nullable|numeric|min:0',
            'unit_conversions.*.app_price' => 'nullable|numeric|min:0',
            'unit_conversions.*.is_sales_unit' => 'required|boolean',

            // === THUỘC TÍNH ===
            'attributes' => 'nullable|array',
            'attributes.*.name' => 'required|string|max:255',
            'attributes.*.type' => 'required|string|in:select,checkbox,text',
            'attributes.*.values' => 'nullable|array',
            'attributes.*.values.*.value' => 'required_unless:attributes.*.type,text|string|max:255',
            'attributes.*.values.*.price_adjustment' => 'required_unless:attributes.*.type,text|numeric',
            'attributes.*.values.*.is_default' => 'required_unless:attributes.*.type,text|boolean',

            //===CHI NHÁNH ===
            'applies_to_all_branches' => 'nullable|boolean',
            //'branch_ids' => 'required_unless:apply_to_all_branches,true|nullable|array',
            'branch_ids.*' => 'integer|exists:branches,id',
            // GIÁ KHÁC NHAU THEO CHI NHÁNH
            // 'branch_prices' => 'sometimes|array',
            // 'branch_prices.*.branch_id' => 'required|integer|exists:branches,id',
            // 'branch_prices.*.price_type' => 'required|string|in:store_price,app_price',
            // 'branch_prices.*.price' => 'required|numeric|min:0',
            // 'branch_prices.*.unit_of_measure' => 'required|string|max:50',

        ];
    }
    public function messages(): array
    {
        return [
            'product_code.unique' => 'Mã sản phẩm đã tồn tại.',
            'name.required' => 'Tên sản phẩm là bắt buộc.',
            'name.max' => 'Tên sản phẩm không được dài quá 255 ký tự.',

            'description.string' => 'Mô tả phải là chuỗi ký tự.',
            'status.required' => 'Trạng thái là bắt buộc.',
            'status.boolean' => 'Trạng thái phải là true hoặc false.',

            'category_ids.required' => 'Vui lòng chọn ít nhất một danh mục.',
            'category_ids.array' => 'Định dạng danh mục không hợp lệ.',
            'category_ids.*.exists' => 'Một trong các danh mục được chọn không tồn tại.',

            'image_url.array' => 'Định dạng ảnh không hợp lệ.',
            'image_url.max' => 'Chỉ được upload tối đa :max ảnh cho mỗi sản phẩm.',
            'image_url.*.required' => 'Vui lòng chọn file ảnh.',
            'image_url.*.image' => 'File phải là hình ảnh.',
            'image_url.*.mimes' => 'Mỗi hình ảnh phải có định dạng: jpeg, png, jpg, gif.',
            'image_url.*.max' => 'Kích thước mỗi hình ảnh không được vượt quá 2MB.',

            'base_unit.required' => 'Tên đơn vị cơ sở là bắt buộc.',
            'cost_price.required' => 'Giá vốn là bắt buộc.',
            'base_store_price.required' => 'Giá bán tại cửa hàng là bắt buộc.',
            'base_app_price.required' => 'Giá bán trên ứng dụng là bắt buộc.',
            'base_store_price.gte' => 'Giá bán tại cửa hàng phải lớn hơn hoặc bằng giá vốn.',
            'base_app_price.gte' => 'Giá bán trên ứng dụng phải lớn hơn hoặc bằng giá vốn.',

            'unit_conversions.*.unit_name.required' => 'Tên đơn vị quy đổi không được để trống.',
            'unit_conversions.*.unit_code.required' => 'Mã hàng của đơn vị quy đổi không được để trống.',
            'unit_conversions.*.unit_code.unique' => 'Mã hàng của đơn vị quy đổi đã tồn tại.',
            'unit_conversions.*.conversion_factor.required' => 'Giá trị quy đổi là bắt buộc.',
            'unit_conversions.*.unit_code.distinct' => 'Các mã hàng của đơn vị quy đổi không được trùng lặp trong cùng một lần tạo.',

            'attributes.*.name.required' => 'Tên thuộc tính không được để trống.',
            'attributes.*.values.*.value.required_unless' => 'Tên giá trị thuộc tính không được để trống.',
            'attributes.*.values.*.price_adjustment.required_unless' => 'Giá trị điều chỉnh là bắt buộc.',

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
