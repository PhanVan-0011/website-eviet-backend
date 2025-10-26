<?php

namespace App\Http\Requests\Api\Combo;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class MultiDeleteComboRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'ids' => 'required|array|min:1',
            'ids.*' => [
                'required',
                'integer',
                Rule::exists('combos', 'id'), // Kiểm tra từng ID phải tồn tại trong bảng combos
            ],
        ];
    }
    
    public function messages(): array
    {
        return [
            'ids.required' => 'Vui lòng chọn ít nhất một combo để xóa',
            'ids.string' => 'Định dạng ID combo không hợp lệ.',
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
            $ids = array_map('intval', $ids); 
        }
        return [
            'ids' => $ids,
        ];
    }
}