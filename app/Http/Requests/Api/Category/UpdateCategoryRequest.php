<?php

namespace App\Http\Requests\Api\Category;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateCategoryRequest extends FormRequest
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
            'name' => 'string|max:255|unique:categories,name,' . $this->route('category'),
            'description' => 'nullable|string',
            'status' => 'boolean',
            'parent_id' => 'nullable|exists:categories,id', // Thêm validation cho parent_id
        ];
    }
    /**
     * Tùy chỉnh thông báo lỗi cho các quy tắc xác thực.
     *
     * @return array
     */
    public function messages(): array
    {
        return [
            'name.string' => 'Tên danh mục phải là chuỗi ký tự.',
            'name.max' => 'Tên danh mục không được dài quá 50 ký tự.',
            'name.unique' => 'Tên danh mục đã tồn tại.',
            'description.string' => 'Mô tả phải là chuỗi ký tự.',
            'status.boolean' => 'Trạng thái phải là true hoặc false.',
            'parent_id.exists' => 'Danh mục cha không tồn tại.',
        ];
    }
    /**
     * Xử lý khi xác thực thất bại, trả về phản hồi JSON.
     *
     * @param Validator $validator
     * @return void
     * @throws HttpResponseException
     */
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => 'Lỗi ràng buộc',
            'errors' => $validator->errors(),
        ], 422));
    }
}
