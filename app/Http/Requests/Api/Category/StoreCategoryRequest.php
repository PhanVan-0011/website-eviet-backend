<?php

namespace App\Http\Requests\Api\Category;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreCategoryRequest extends FormRequest
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
            'name' => 'required|string|max:255|unique:categories,name,' . $this->route('category'),
            'icon' => 'nullable|file|mimes:jpeg,png,jpg,svg|max:2048',
            'description' => 'nullable|string',
            'status' => 'required|boolean',
            'parent_id' => 'nullable|exists:categories,id',
            'type' => 'sometimes|in:product,post,all', // Loại danh mục: product, post, all
        ];
    }
    public function messages(): array
    {
        return [
            'name.required' => 'Tên danh mục là bắt buộc.',
            'name.string' => 'Tên danh mục phải là chuỗi ký tự.',
            'name.max' => 'Tên danh mục không được dài quá 255 ký tự.',
            'name.unique' => 'Tên danh mục đã tồn tại.',

            'icon.image' => 'File tải lên phải là hình ảnh.',
            'icon.mimes' => 'Ảnh phải có định dạng: jpeg, png, jpg hoặc svg.',
            'icon.max' => 'Kích thước ảnh không được vượt quá 2MB.',


            'description.string' => 'Mô tả phải là chuỗi ký tự.',

            'status.required' => 'Trạng thái là bắt buộc.',
            'status.boolean' => 'Trạng thái phải là true hoặc false.',

            'parent_id.exists' => 'Danh mục cha không tồn tại.',

            'type.in' => 'Loại danh mục không hợp lệ. Chỉ chấp nhận: product, post, all.',
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
