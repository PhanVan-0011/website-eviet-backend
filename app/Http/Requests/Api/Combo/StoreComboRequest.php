<?php

namespace App\Http\Requests\Api\Combo;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class StoreComboRequest extends FormRequest
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
            $this->merge([
                'applies_to_all_branches' => filter_var($this->input('applies_to_all_branches', false), FILTER_VALIDATE_BOOLEAN),
            ]);
    }
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'combo_code' => 'nullable|string|max:50|unique:combos,combo_code',
            'name'                  => 'required|string|max:200',

            'description'           => 'nullable|string|max:255',
            'base_store_price' => 'required|numeric|min:0', // Bắt buộc
            'base_app_price' => 'required|numeric|min:0',   // Bắt buộc
            'image_url'             => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'start_date'            => 'nullable|date',
            'end_date'              => 'nullable|date|after_or_equal:start_date',
            'is_active'             => 'boolean',
            'items'                 => 'required|array|min:1', 
            'items.*.product_id'    => 'required|integer|exists:products,id',
            'items.*.quantity'      => 'required|integer|min:1',

            // Phân bổ chi nhánh
            'applies_to_all_branches' => 'required|boolean',
            'branch_ids' => [
                // Chỉ bắt buộc khi applies_to_all_branches là false
                Rule::requiredIf(!$this->input('applies_to_all_branches')), 
                'nullable',
                Rule::when($this->input('applies_to_all_branches') === false, ['min:1'], []), 
            ],
            'branch_ids.*' => 'integer|exists:branches,id',
        ];
    }
    public function messages()
    {
        return [
            'name.required' => 'Tên combo là bắt buộc.',
            'name.string' => 'Tên combo phải là chuỗi ký tự.',
            'name.max' => 'Tên combo không được vượt quá 200 ký tự.',

            'combo_code.unique' => 'Mã combo đã tồn tại.',
            'base_store_price.required' => 'Giá bán tại cửa hàng là bắt buộc.',
            'base_app_price.required' => 'Giá bán qua app là bắt buộc.',

            'description.string' => 'Mô tả phải là chuỗi.',
            'description.max' => 'Mô tả không được vượt quá 255 ký tự.',
            
            'image_url.image' => 'File tải lên phải là hình ảnh.',
            'image_url.mimes' => 'Ảnh phải có định dạng: jpeg, png, jpg, gif.',
            'image_url.max' => 'Kích thước ảnh không được vượt quá 2MB.',

            'items.required' => 'Combo phải có ít nhất một sản phẩm.',
            'items.min' => 'Combo phải có ít nhất một sản phẩm.',
            'items.*.product_id.required' => 'Vui lòng chọn sản phẩm.',
            'items.*.product_id.exists' => 'Sản phẩm không hợp lệ.',
            'items.*.quantity.min' => 'Số lượng sản phẩm phải lớn hơn 0.',

            'start_date.date' => 'Ngày bắt đầu phải đúng định dạng ngày.',
            'end_date.date' => 'Ngày kết thúc phải đúng định dạng ngày.',
            'end_date.after_or_equal' => 'Ngày kết thúc phải sau hoặc bằng ngày bắt đầu.',

            'is_active.boolean' => 'Trạng thái kích hoạt phải là true hoặc false.',

            'applies_to_all_branches.required' => 'Vui lòng chọn phạm vi áp dụng chi nhánh.',
            'branch_ids.required_if' => 'Vui lòng chọn ít nhất một chi nhánh áp dụng.',
            'branch_ids.min' => 'Vui lòng chọn ít nhất một chi nhánh áp dụng.',
            'branch_ids.*.exists' => 'Chi nhánh không hợp lệ.',
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
