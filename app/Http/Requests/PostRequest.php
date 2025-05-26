<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class PostRequest extends FormRequest
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
            'title' => ['required', 'string', 'max:255', Rule::unique('posts')->ignore($this->post)],
            'content' => ['nullable', 'string'],
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('posts')->ignore($this->post)],
            'status' => ['required', 'boolean'],
            'image_url' => ['nullable', 'url', 'max:2048'],
            'category_ids' => ['nullable', 'array'],
            'category_ids.*' => ['exists:categories,id'],
        ];
    }
     /**
     * Get custom messages for validation errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'title.required' => 'Tiêu đề bài viết là bắt buộc.',
            'title.string' => 'Tiêu đề bài viết phải là chuỗi ký tự.',
            'title.max' => 'Tiêu đề bài viết không được vượt quá 255 ký tự.',
            'title.unique' => 'Tiêu đề bài viết đã tồn tại. Vui lòng chọn tiêu đề khác.',
            'content.string' => 'Nội dung bài viết phải là chuỗi ký tự.',
            'slug.string' => 'Slug phải là chuỗi ký tự.',
            'slug.max' => 'Slug không được vượt quá 255 ký tự.',
            'slug.unique' => 'Slug đã tồn tại. Vui lòng chọn slug khác.',
            'status.required' => 'Trạng thái bài viết là bắt buộc.',
            'status.boolean' => 'Trạng thái bài viết phải là true hoặc false.',
            'image_url.url' => 'URL hình ảnh không hợp lệ.',
            'image_url.max' => 'URL hình ảnh không được vượt quá 2048 ký tự.',
            'category_ids.array' => 'Danh sách danh mục phải là một mảng.',
            'category_ids.*.exists' => 'Danh mục không tồn tại trong hệ thống.',
        ];
    }
     /**
     * Chuẩn hóa dữ liệu trước khi thực hiện validation.
     * Loại bỏ khoảng trắng thừa ở đầu và cuối của các trường title, slug, content.
     * Đảm bảo dữ liệu sạch trước khi kiểm tra.
     */
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

    /**
     * Tùy chỉnh dữ liệu đã validate trước khi trả về.
     * Chuyển các trường nullable (content, slug, image_url) thành null nếu chúng là chuỗi rỗng.
     * Điều này giúp tránh lưu chuỗi rỗng vào cơ sở dữ liệu, giữ dữ liệu sạch sẽ.
     *
     * @return array
     */
    public function validated($key = null, $default = null): array
    {
        $validated = parent::validated();

        // Chuyển chuỗi rỗng thành null cho các trường nullable
        if (empty($validated['content'])) {
            $validated['content'] = null;
        }

        if (empty($validated['slug'])) {
            $validated['slug'] = null;
        }

        if (empty($validated['image_url'])) {
            $validated['image_url'] = null;
        }

        return $validated;
    }
    /**
     * Tùy chỉnh phản hồi khi validation thất bại.
     * Ghi đè phương thức failedValidation của FormRequest để trả về phản hồi JSON với định dạng:
     * - success: false
     * - message: "Lỗi ràng buộc"
     * - errors: Danh sách lỗi validation
     * Mã trạng thái là 422 (Unprocessable Entity).
     *
     * @param \Illuminate\Validation\Validator $validator
     * @throws \Illuminate\Http\Exceptions\HttpResponseException
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
