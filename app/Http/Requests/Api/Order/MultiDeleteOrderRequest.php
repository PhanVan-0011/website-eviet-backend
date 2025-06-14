<?php

namespace App\Http\Requests\Api\Order;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class MultiDeleteOrderRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'ids' => ['required', 'string'],
            'order_ids' => ['required', 'array', 'min:1'],
            'order_ids.*' => ['integer', 'exists:orders,id'],
        ];
    }
     public function messages(): array
    {
        return [
            'ids.required' => 'Vui lòng cung cấp danh sách ID đơn hàng trong URL',
            'order_ids.required' => 'Vui lòng chọn ít nhất một đơn hàng để hủy.',
            'order_ids.*.exists' => 'Một hoặc nhiều ID đơn hàng không tồn tại trong hệ thống.',
        ];
    }
    protected function prepareForValidation(): void
    {
        if ($this->query('ids')) {
            $idsArray = explode(',', $this->query('ids'));
            $filteredIds = array_filter($idsArray);
            $sanitizedIds = array_map('intval', $filteredIds);

            $this->merge([
                'order_ids' => $sanitizedIds
            ]);
        }
    }
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors(),
            ], 422)
        );
    }
}
