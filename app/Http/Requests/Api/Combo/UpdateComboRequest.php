<?php

namespace App\Http\Requests\Api\Combo;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;
use App\Models\Combo;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class UpdateComboRequest extends FormRequest
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

        if ($this->has('applies_to_all_branches')) {
            $this->merge([
                'applies_to_all_branches' => filter_var($this->input('applies_to_all_branches'), FILTER_VALIDATE_BOOLEAN),
            ]);
        }
    }
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        //dd($this->route('id')); 
        $comboId = $this->route('id');
        return [
            'name'                  => ['sometimes', 'required', 'string', 'max:255', Rule::unique('combos', 'name')->ignore($comboId)],
            'combo_code'            => ['sometimes', 'nullable', 'string', 'max:50', Rule::unique('combos', 'combo_code')->ignore($comboId)],
            'description'           => 'sometimes|nullable|string|max:255',
            'base_store_price'      => 'sometimes|required|numeric|min:0',
            'base_app_price'        => 'sometimes|required|numeric|min:0', 
            'image_url'             => 'sometimes|nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'start_date'            => 'sometimes|nullable|date',
            'end_date'              => 'sometimes|nullable|date|after_or_equal:start_date',
            'is_active'             => 'sometimes|boolean',
            'items'                 => 'sometimes|array|min:1',
            'items.*.product_id'    => 'required_with:items|integer|exists:products,id',
            'items.*.quantity'      => 'required_with:items|integer|min:1',
            'applies_to_all_branches' => 'sometimes|boolean', // Dùng sometimes
            'branch_ids' => [
                 // Chỉ bắt buộc nếu applies_to_all_branches được gửi lên VÀ là false
                Rule::requiredIf(function () {
                     return $this->has('applies_to_all_branches') && !$this->input('applies_to_all_branches');
                }),
                'nullable',
                'array',
                // Chỉ kiểm tra min khi applies_to_all_branches là false
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
            'name.unique'   => 'Tên combo này đã tồn tại.',

            'description.string' => 'Mô tả phải là chuỗi.',
            'description.max' => 'Tên combo không được vượt quá 255 ký tự.',

            'start_date.date' => 'Ngày bắt đầu phải đúng định dạng ngày.',
            'end_date.date' => 'Ngày kết thúc phải đúng định dạng ngày.',
            'end_date.after_or_equal' => 'Ngày kết thúc phải sau hoặc bằng ngày bắt đầu.',

            'is_active.boolean' => 'Trạng thái kích hoạt phải là true hoặc false.',

            'items.required' => 'Danh sách sản phẩm là bắt buộc.',
            'items.array' => 'Danh sách sản phẩm phải là một mảng.',
            'items.min' => 'Phải có ít nhất một sản phẩm trong combo.',

            'items.*.product_id.required' => 'ID sản phẩm là bắt buộc.',
            'items.*.product_id.exists' => 'Sản phẩm không tồn tại trong hệ thống.',

            'items.*.quantity.required' => 'Số lượng là bắt buộc.',
            'items.*.quantity.integer' => 'Số lượng phải là số nguyên.',
            'items.*.quantity.min' => 'Số lượng phải lớn hơn 0.',

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
