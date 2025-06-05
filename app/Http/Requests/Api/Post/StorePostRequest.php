<?php

namespace App\Http\Requests\Api\Post;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class StorePostRequest extends FormRequest
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
            'title' => ['required', 'string', 'max:255', Rule::unique('posts')],
            'content' => ['nullable', 'string', 'max:65535'],
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('posts')],
            'status' => ['required', 'boolean'],
            'image_url' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'category_ids' => ['nullable', 'array'],
            'category_ids.*' => ['exists:categories,id'],
        ];
    }
    public function messages(): array
    {
        return [
            'title.required' => 'Tiêu đề bài viết là bắt buộc.',
            'title.string' => 'Tiêu đề bài viết phải là chuỗi ký tự.',
            'title.max' => 'Tiêu đề bài viết không được vượt quá 255 ký tự.',
            'title.unique' => 'Tiêu đề bài viết đã tồn tại. Vui lòng chọn tiêu đề khác.',
            'content.string' => 'Nội dung bài viết phải là chuỗi ký tự.',
            'content.max' => 'Nội dung bài viết không được vượt quá 65,535 ký tự.',
            'slug.string' => 'Slug phải là chuỗi ký tự.',
            'slug.max' => 'Slug không được vượt quá 255 ký tự.',
            'slug.unique' => 'Slug đã tồn tại. Vui lòng chọn slug khác.',
            'status.required' => 'Trạng thái bài viết là bắt buộc.',
            'image_url.image' => 'File phải là hình ảnh.',
            'image_url.mimes' => 'Hình ảnh phải có định dạng: jpeg, png, jpg, gif.',
            'image_url.max' => 'Kích thước hình ảnh không được vượt quá 2MB.',
            'category_ids.array' => 'Danh sách danh mục phải là một mảng.',
            'category_ids.*.exists' => 'Danh mục không tồn tại trong hệ thống.',
        ];
    }
    protected function prepareForValidation(): void
    {
        if ($this->has('title')) {
            $this->merge([
                'title' => trim($this->input('title')),
            ]);
        }

        if ($this->has('slug')) {
            $this->merge([
                'slug' => trim($this->input('slug')),
            ]);
        }

        if ($this->has('content')) {
            $this->merge([
                'content' => trim($this->input('content')),
            ]);
        }
    }
    public function validated($key = null, $default = null): array
    {
        $validated = parent::validated();

        if (empty($validated['content'])) {
            $validated['content'] = null;
        }

        if (empty($validated['slug'])) {
            $validated['slug'] = null;
        }

        if (empty($validated['image']) && empty($validated['image_url'])) {
            $validated['image_url'] = null;
        }

        return $validated;
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
