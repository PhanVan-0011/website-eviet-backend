<?php

namespace App\Http\Requests\ProductAttribute;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;
class UpdateProductAttributeRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        $attributeId = $this->route('product_attribute');
        return [
            'name' => 'sometimes|required|string|max:255',
            'type' => 'sometimes|required|in:select,checkbox',
            'display_order' => 'sometimes|integer',
            'values' => 'sometimes|array',
             'values.*.id' => [
                'sometimes',
                'integer',
                Rule::exists('attribute_values', 'id')->where(function ($query) use ($attributeId) {
                    return $query->where('product_attribute_id', $attributeId);
                }),
            ],
            'values.*.id' => 'sometimes|exists:attribute_values,id', // ID cho các value đã tồn tại
            'values.*.value' => 'required|string|max:255',
            'values.*.price_adjustment' => 'sometimes|numeric|min:0',
            'values.*.display_order' => 'sometimes|integer',
            'values.*.is_active' => 'sometimes|boolean',
            'values.*.is_default' => 'sometimes|boolean',
        ];
    }
    public function messages(): array
    {
        return [
            'values.*.id.exists' => 'Một hoặc nhiều giá trị không thuộc về thuộc tính này.',
            'values.*.value.required' => 'Tên giá trị không được để trống.',
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
