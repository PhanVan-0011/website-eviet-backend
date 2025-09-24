<?php

namespace App\Http\Requests\Api\Category;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;
class MultiDeleteCategoryRequest extends FormRequest
{
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
            'ids' => 'required|array|min:1',
            'ids.*' => [
                'required',
                'integer',
                Rule::exists('categories', 'id'),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'ids.required' => 'Vui lòng cung cấp danh sách ID danh mục cần xóa.',
            'ids.array' => 'Định dạng danh sách ID không hợp lệ.',
            'ids.min' => 'Vui lòng chọn ít nhất một danh mục để xóa.',
            'ids.*.integer' => 'Mỗi ID trong danh sách phải là một số nguyên.',
            'ids.*.exists' => 'Một trong các ID không tồn tại.',
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
