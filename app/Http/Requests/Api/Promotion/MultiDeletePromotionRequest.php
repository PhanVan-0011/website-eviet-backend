<?php

namespace App\Http\Requests\Api\Promotion;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class MultiDeletePromotionRequest extends FormRequest
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
            'ids' => ['required', 'string'],
            'promotion_ids' => ['required', 'array', 'min:1'],
            'promotion_ids.*' => ['integer', 'exists:promotions,id'],
        ];
    }
    protected function prepareForValidation(): void
    {
        if ($this->query('ids')) {
            $idsArray = explode(',', $this->query('ids'));
            $filteredIds = array_filter($idsArray);
            $sanitizedIds = array_map('intval', $filteredIds);
            $this->merge(['promotion_ids' => $sanitizedIds]);
        }
    }
     public function messages(): array
    {
        return [
            'ids.required' => 'Vui lòng cung cấp danh sách ID khuyến mãi trong URL (ví dụ: ?ids=1,2,3).',
            'promotion_ids.required' => 'Vui lòng chọn ít nhất một khuyến mãi để xóa.',
            'promotion_ids.*.exists' => 'Một hoặc nhiều ID khuyến mãi không tồn tại.',
        ];
    }
    protected function failedValidation(\Illuminate\Contracts\Validation\Validator $validator): void
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => $validator->errors()->first(),
            'errors' => $validator->errors(),
        ], 422));
    }
}
