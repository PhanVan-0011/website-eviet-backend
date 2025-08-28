<?php

namespace App\Http\Requests\ProductAttribute;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreProductAttributeRequest extends FormRequest
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
            'product_id' => 'required|exists:products,id',
            'name' => 'required|string|max:255',
            'type' => 'required|in:select,checkbox',
            'display_order' => 'sometimes|integer',
            'values' => 'required|array|min:1',
            'values.*.value' => 'required|string|max:255',
            'values.*.price_adjustment' => 'sometimes|numeric|min:0',
            'values.*.display_order' => 'sometimes|integer',
            'values.*.is_active' => 'sometimes|boolean',
            'values.*.is_default' => 'sometimes|boolean',
        ];
    }
    /**
     * Get the custom error messages for validator errors.
     *
     * @return array
     */
    public function messages(): array
    {
        return [
            'product_id.required' => 'Vui lòng chọn sản phẩm.',
            'product_id.exists' => 'Sản phẩm được chọn không tồn tại.',
            'name.required' => 'Tên thuộc tính không được để trống.',
            'name.max' => 'Tên thuộc tính không được vượt quá 255 ký tự.',
            'type.required' => 'Vui lòng chọn loại thuộc tính.',
            'type.in' => 'Loại thuộc tính không hợp lệ.',
            'values.required' => 'Cần có ít nhất một giá trị cho thuộc tính.',
            'values.array' => 'Dữ liệu giá trị không hợp lệ.',
            'values.min' => 'Cần có ít nhất một giá trị cho thuộc tính.',
            'values.*.value.required' => 'Tên giá trị không được để trống.',
            'values.*.price_adjustment.numeric' => 'Giá điều chỉnh phải là một con số.',
            'values.*.price_adjustment.min' => 'Giá điều chỉnh không được là số âm.',
        ];
    }
    /**
     * Handle a failed validation attempt.
     *
     * @param  \Illuminate\Contracts\Validation\Validator  $validator
     * @return void
     *
     * @throws \Illuminate\Http\Exceptions\HttpResponseException
     */
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => $validator->errors()->first(),
            'errors' => $validator->errors(),
        ], 422));
    }
}
