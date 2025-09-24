<?php

namespace App\Http\Requests\Api\Combo;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;


class MultiDeleteComboRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'ids' => ['required', 'string', 'regex:/^\\d+(?:,\\d+)*$/'],
        ];
    }
    
    public function messages(): array
    {
        return [
            'ids.required' => 'Vui lòng chọn ít nhất một danh mục để xóa',
            'ids.string' => 'Định dạng ID danh mục không hợp lệ.',
            'ids.regex' => 'Định dạng danh sách ID không hợp lệ. Ví dụ: 1,2,3',
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
    
    public function validationData()
    {
        $ids = $this->query('ids');
        if (is_string($ids)) {
            $ids = explode(',', $ids);
            // Ép kiểu các phần tử trong mảng thành số nguyên
            $ids = array_map('intval', $ids); 
        }
        return [
            'ids' => $ids,
        ];
    }
}