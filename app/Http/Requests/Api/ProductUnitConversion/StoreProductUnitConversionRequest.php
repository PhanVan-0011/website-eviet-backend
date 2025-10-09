<?php

namespace App\Http\Requests\Api\ProductUnitConversion;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class StoreProductUnitConversionRequest extends FormRequest
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
            'product_id' => 'required|integer|exists:products,id',
            'unit_name' => [
                'required',
                'string',
                'max:50',
                Rule::unique('product_unit_conversions')->where(function ($query) {
                    return $query->where('product_id', $this->product_id);
                }),
            ],
            'unit_code' => 'nullable|string|max:255|unique:product_unit_conversions,unit_code',
            
            'store_price' => 'nullable|numeric|min:0', 
            'app_price' => 'nullable|numeric|min:0', 
            
            'is_sales_unit' => 'nullable|boolean',
            
            'conversion_factor' => 'required|numeric|min:0.0001', // Hệ số phải lớn hơn 0
            
            'is_purchase_unit' => 'nullable|boolean', 
        ];
    }
    public function messages(): array
    {
        return [
            'product_id.required' => 'ID sản phẩm là bắt buộc.',
            'product_id.exists' => 'Sản phẩm không tồn tại.',
            'unit_name.required' => 'Tên đơn vị quy đổi là bắt buộc.',
            'unit_name.unique' => 'Quy tắc chuyển đổi này đã tồn tại cho sản phẩm này.',
            
            'unit_code.unique' => 'Mã hàng quy đổi đã tồn tại.',
            
            'conversion_factor.required' => 'Hệ số quy đổi là bắt buộc.',
            'conversion_factor.numeric' => 'Hệ số quy đổi phải là một số.',
            'conversion_factor.min' => 'Hệ số quy đổi phải lớn hơn 0.',
            
            'store_price.numeric' => 'Giá bán tại cửa hàng phải là một số.',
            'store_price.min' => 'Giá bán tại cửa hàng phải là số dương hoặc bằng 0.',
            'app_price.numeric' => 'Giá bán qua ứng dụng phải là một số.',
            'app_price.min' => 'Giá bán qua ứng dụng phải là số dương hoặc bằng 0.',
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
