<?php

namespace App\Http\Requests\Api\Category;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

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
        // Lấy ID của danh mục từ route
         $categoryId = $this->route('id');
        return [
            'name'        =>'sometimes','required','string','max:255',Rule::unique('name')->ignore($categoryId),
            'description' => 'sometimes|nullable|string',
            'status'      => 'sometimes|required|boolean',
            'parent_id'   => 'sometimes|nullable|exists:categories,id',
            'icon'        => 'sometimes|nullable|file|mimes:jpeg,png,jpg,svg|max:2048',
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
            'name.unique' => 'Tên danh mục đã tồn tại.',
            'name.string' => 'Tên danh mục phải là chuỗi ký tự.',
            'name.max' => 'Tên danh mục không được dài quá 50 ký tự.',
            'description.string' => 'Mô tả phải là chuỗi ký tự.',
            'status.boolean' => 'Trạng thái phải là true hoặc false.',
            'parent_id.exists' => 'Danh mục cha không tồn tại.',
            'icon.file' => 'Icon phải là một file ảnh.',
            'icon.mimes' => 'Icon phải thuộc định dạng: jpeg, png, jpg hoặc svg.',
            'icon.max' => 'Dung lượng file icon không được vượt quá 2MB.',
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
            'message' => $validator->errors()->first(),
            'errors' => $validator->errors(),
        ], 422));
    }
}
