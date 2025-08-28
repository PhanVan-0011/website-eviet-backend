<?php

namespace App\Http\Requests\ProductAttribute;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class MultiDeleteProductAttributeRequest extends FormRequest
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
        if ($this->has('ids') && is_string($this->ids)) {
            $this->merge([
                'ids' => array_filter(array_map('intval', explode(',', $this->ids))),
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'ids'   => 'required|array|min:1',
            'ids.*' => 'required|integer|exists:product_attributes,id'
        ];
    }

    public function messages(): array
    {
        return [
            'ids.required' => 'Vui lòng cung cấp danh sách ID cần xóa.',
            'ids.array'    => 'Định dạng danh sách ID không hợp lệ.',
            'ids.min'      => 'Cần có ít nhất một ID để thực hiện xóa.',
            'ids.*.integer' => 'Mỗi ID trong danh sách phải là một số nguyên.',
            'ids.*.exists'  => 'Một hoặc nhiều ID thuộc tính không tồn tại.',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => $validator->errors()->first(),
            'errors'  => $validator->errors(),
        ], 422));
    }
}
