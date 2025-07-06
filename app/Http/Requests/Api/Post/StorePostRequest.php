<?php

namespace App\Http\Requests\Api\Post;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;
use Illuminate\Http\UploadedFile;

class StorePostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {  
       if ($this->has('image_url') && is_array($this->input('image_url'))) {
            $this->merge([
                'image_url' => array_filter($this->input('image_url'), function ($file) {
                    return $file instanceof UploadedFile;
                }),
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'content' => ['nullable', 'string', 'max:65535'],
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('posts')],
            'status' => ['required', 'boolean'],
            
            'image_url' => 'nullable|array|max:5',
            'image_url.*' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',

            'featured_image_index' => 'nullable|integer|min:0',

            'category_ids' => ['nullable', 'array'],
            'category_ids.*' => ['exists:categories,id'],
        ];
    }

    public function messages(): array
    {
        return [
            // Title
            'title.required' => 'Tiêu đề bài viết là bắt buộc.',
            'title.string'   => 'Tiêu đề bài viết phải là chuỗi ký tự.',
            'title.max'      => 'Tiêu đề không được vượt quá 255 ký tự.',

            // Content
            'content.string' => 'Nội dung bài viết phải là chuỗi ký tự.',
            'content.max'    => 'Nội dung không được vượt quá 65,535 ký tự.',

            // Slug
            'slug.string' => 'Slug phải là chuỗi ký tự.',
            'slug.max'    => 'Slug không được vượt quá 255 ký tự.',
            'slug.unique' => 'Slug này đã tồn tại.',

            // Status
            'status.required' => 'Trạng thái là bắt buộc.',
            'status.boolean'  => 'Trạng thái phải là true hoặc false.',

            // Image URL
            'image_url.array'   => 'Định dạng ảnh không hợp lệ.',
            'image_url.max'     => 'Chỉ được upload tối đa :max ảnh cho mỗi bài viết.',
            'image_url.*.required' => 'Vui lòng chọn một file ảnh.',
            'image_url.*.image' => 'Mỗi file phải là hình ảnh.',
            'image_url.*.mimes' => 'Mỗi hình ảnh phải có định dạng: jpeg, png, jpg, gif.',
            'image_url.*.max'   => 'Kích thước mỗi hình ảnh không được vượt quá 2MB.',

            // Featured Image Index
            'featured_image_index.integer' => 'Chỉ số ảnh đại diện phải là số nguyên.',
            'featured_image_index.min'     => 'Chỉ số ảnh đại diện phải lớn hơn hoặc bằng 0.',

            // Category IDs
            'category_ids.array'      => 'Định dạng danh mục không hợp lệ.',
            'category_ids.*.exists'   => 'Một trong các danh mục được chọn không tồn tại.',
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
