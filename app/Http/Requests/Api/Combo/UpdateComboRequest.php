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

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $comboId = $this->route('id');
        return [
            'name'                  => ['sometimes', 'required', 'string', 'max:200', Rule::unique('combos', 'name')->ignore($comboId)],
            'slug'                  => ['sometimes', 'required', 'string', 'max:255', Rule::unique('combos', 'slug')->ignore($comboId)],
            'description'           => 'sometimes|nullable|string|max:255',
            'price'                 => 'sometimes|required|numeric|min:0',

            'image_url'             => 'sometimes|nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'start_date'            => 'sometimes|nullable|date',
            'end_date'              => 'sometimes|nullable|date|after_or_equal:start_date',
            'is_active'             => 'sometimes|boolean',
            'items'                 => 'sometimes|required|array|min:1',
            'items.*.product_id'    => 'required_with:items|integer|exists:products,id',
            'items.*.quantity'      => 'required_with:items|integer|min:1',
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

            'price.required' => 'Giá combo là bắt buộc.',
            'price.numeric' => 'Giá combo phải là số.',
            'price.min' => 'Giá combo không được âm.',

            'slug.string' => 'Slug phải là chuỗi.',
            'slug.unique' => 'Slug này đã tồn tại.',

            'image_url.image' => 'File tải lên phải là hình ảnh.',
            'image_url.mimes' => 'Ảnh phải có định dạng: jpeg, png, jpg, gif.',
            'image_url.max' => 'Kích thước ảnh không được vượt quá 2MB.',

            'start_date.date' => 'Ngày bắt đầu phải đúng định dạng ngày.',
            'end_date.date' => 'Ngày kết thúc phải đúng định dạng ngày.',
            'end_date.after_or_equal' => 'Ngày kết thúc phải sau hoặc bằng ngày bắt đầu.',

            'is_active.boolean' => 'Trạng thái kích hoạt phải là true hoặc false.',

            'items.required' => 'Danh sách sản phẩm là bắt buộc.',
            'items.array' => 'Danh sách sản phẩm phải là một mảng.',
            'items.min' => 'Phải có ít nhất một sản phẩm trong combo.',

            'items.*.product_id.required' => 'ID sản phẩm là bắt buộc.',
            'items.*.product_id.integer' => 'ID sản phẩm phải là số nguyên.',
            'items.*.product_id.exists' => 'Sản phẩm không tồn tại trong hệ thống.',

            'items.*.quantity.required' => 'Số lượng là bắt buộc.',
            'items.*.quantity.integer' => 'Số lượng phải là số nguyên.',
            'items.*.quantity.min' => 'Số lượng phải lớn hơn 0.',
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
