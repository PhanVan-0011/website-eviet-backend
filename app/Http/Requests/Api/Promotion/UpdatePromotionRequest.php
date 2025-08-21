<?php

namespace App\Http\Requests\Api\Promotion;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;
use App\Models\Promotion;
use Illuminate\Support\Facades\DB;

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
        $promotionId = $this->route('promotion');
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'code' => ['sometimes', 'required', 'string', 'max:50', Rule::unique('promotions', 'code')->ignore($promotionId)],
            'description' => ['sometimes', 'nullable', 'string'],
            'application_type' => ['sometimes', 'required', 'string', Rule::in(['orders', 'products', 'categories', 'combos'])],
            'type' => ['sometimes', 'required', 'string', Rule::in(['percentage', 'fixed_amount', 'free_shipping'])],
            'value' => ['sometimes', 'required', 'numeric', 'min:0'],
            
            'image_url' => 'sometimes|image|mimes:jpeg,png,jpg,gif|max:2048',

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
            
            'product_ids.*.exists' => 'Một hoặc nhiều ID sản phẩm được chọn không tồn tại.',
            'category_ids.*.exists' => 'Một hoặc nhiều ID danh mục được chọn không tồn tại.',
            'combo_ids.*.exists' => 'Một hoặc nhiều ID combo được chọn không tồn tại.',


        ];
    }
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            // Tìm đối tượng Promotion từ ID trong route
            $promotion = Promotion::find($this->route('promotion'));
            if (!$promotion) {
                // Nếu không tìm thấy, không cần validate thêm
                return;
            }

            // Xác định application_type. Nếu không được gửi lên, dùng giá trị hiện tại.
            $applicationType = $this->input('application_type', $promotion->application_type);

            // Kiểm tra logic: Nếu loại áp dụng là 'products' thì phải có product_ids
            if ($applicationType === 'products') {
                if ($this->has('product_ids') && empty($this->input('product_ids'))) {
                    $validator->errors()->add('product_ids', 'Vui lòng chọn ít nhất một sản phẩm.');
                }
            }
            // Ngược lại, nếu loại áp dụng KHÔNG PHẢI 'products' nhưng lại gửi product_ids -> báo lỗi
            elseif ($this->has('product_ids')) {
                $validator->errors()->add('product_ids', 'Không được chọn sản phẩm vì loại áp dụng không phải là "products".');
            }

            // Tương tự cho categories và combos...
            if ($applicationType === 'categories') {
                if ($this->has('category_ids') && empty($this->input('category_ids'))) {
                    $validator->errors()->add('category_ids', 'Vui lòng chọn ít nhất một danh mục.');
                }
            } elseif ($this->has('category_ids')) {
                $validator->errors()->add('category_ids', 'Không được chọn danh mục vì loại áp dụng không phải là categories.');
            }
             // Logic tương tự cho combos
            if ($applicationType === 'combos') {
                 if ($this->has('combo_ids') && empty($this->input('combo_ids'))) {
                    $validator->errors()->add('combo_ids', 'Vui lòng chọn ít nhất một combo khi loại áp dụng là combos.');
                }
            } elseif ($this->has('combo_ids')) {
                $validator->errors()->add('combo_ids', 'Không được chọn combo vì loại áp dụng không phải là combos.');
            }
        });
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
