<?php

namespace App\Http\Requests\Api\ProductUnitConversion;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class UpdateProductUnitConversionRequest extends FormRequest
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
        // Lấy ID của bản ghi đang được cập nhật từ Route
        $conversionId = $this->route('id'); 
        
        return [
            'product_id' => 'sometimes|required|integer|exists:products,id',
            'unit_name' => [
                'sometimes',
                'required',
                'string',
                'max:50',
                Rule::unique('product_unit_conversions','unit_name')->ignore($conversionId), // LOẠI TRỪ bản ghi hiện tại
            ],
            'conversion_factor' => 'sometimes|required|numeric|min:0.0001',
            'is_purchase_unit' => 'nullable|boolean',
            'is_sales_unit' => 'nullable|boolean',
        ];
    }
     public function messages(): array
    {
        return [
            'product_id.required' => 'ID sản phẩm là bắt buộc.',
            'product_id.exists' => 'Sản phẩm không tồn tại.',
            'unit_name.required' => 'Tên đơn vị quy đổi là bắt buộc.',
            'unit_name.unique' => 'Quy tắc chuyển đổi này đã tồn tại cho sản phẩm này.',
            'conversion_factor.required' => 'Hệ số quy đổi là bắt buộc.',
            'conversion_factor.min' => 'Hệ số quy đổi phải lớn hơn 0.',
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
