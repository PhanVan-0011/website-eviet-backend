<?php

namespace App\Http\Requests\Api\Category;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class MultiDeleteCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('ids') && is_array($this->ids)) {
            $this->merge([
                'ids' => implode(',', $this->ids)
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'ids' => ['required', 'regex:/^[0-9]+(,[0-9]+)*$/']
        ];
    }

    public function messages(): array
    {
        return [
            'ids.required' => 'Vui lòng chọn ít nhất một danh mục để xóa',
            'ids.string' => 'Danh sách ID danh mục không hợp lệ',
            'ids.regex' => 'Định dạng danh sách ID không hợp lệ. Ví dụ: 1,2,3'
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
