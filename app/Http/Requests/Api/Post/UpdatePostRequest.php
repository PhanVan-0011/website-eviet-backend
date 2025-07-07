<?php

namespace App\Http\Requests\Api\Post;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;
use App\Models\Post;
use Illuminate\Http\UploadedFile;

class UpdatePostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }
    /**
     * Chuẩn bị dữ liệu trước khi xác thực.
     * Lọc bỏ các ô input ảnh bị bỏ trống.
     */
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
        $postId = $this->route('id');
        return [
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'content' => ['sometimes', 'nullable', 'string', 'max:65535'],
            'slug' => ['sometimes', 'nullable', 'string', 'max:255', Rule::unique('posts')->ignore($postId)],
            'status' => ['sometimes', 'required', 'boolean'],
            'category_ids' => ['sometimes', 'nullable', 'array'],
            'category_ids.*' => ['exists:categories,id'],

            // Ảnh mới tải lên
            'image_url' => ['sometimes', 'nullable', 'array'],
            'image_url.*' => ['sometimes', 'required', 'image', 'mimes:jpeg,png,jpg,gif', 'max:2048'],

            // ID ảnh cũ cần xóa
            'deleted_image_ids' => ['sometimes', 'nullable', 'array'],
            'deleted_image_ids.*' => [
                'integer',
                Rule::exists('images', 'id')->where('imageable_id', $postId),
            ],

            // Chỉ số của ảnh đại diện trong danh sách ảnh cuối cùng
            'featured_image_index' => ['sometimes', 'nullable', 'integer', 'min:0'],
        ];
    }
     public function messages(): array
    {
        return [
            // Title
            'title.required' => 'Tiêu đề bài viết là bắt buộc.',
            'title.string'   => 'Tiêu đề bài viết phải là chuỗi ký tự.',
            'title.max'      => 'Tiêu đề không được vượt quá 255 ký tự.',

            // Slug
            'slug.unique' => 'Slug này đã tồn tại.',

            // Image URL
            'image_url.array'   => 'Định dạng ảnh không hợp lệ.',
            'image_url.*.image' => 'Mỗi file phải là hình ảnh.',
            'image_url.*.mimes' => 'Mỗi hình ảnh phải có định dạng: jpeg, png, jpg, gif.',
            'image_url.*.max'   => 'Kích thước mỗi hình ảnh không được vượt quá 2MB.',

            // Deleted Image IDs
            'deleted_image_ids.array' => 'Định dạng ID ảnh cần xóa không hợp lệ.',
            'deleted_image_ids.*.exists' => 'ID ảnh cần xóa không tồn tại hoặc không thuộc về bài viết này.',

            'featured_image_index.integer' => 'Chỉ số ảnh đại diện phải là một số nguyên.',
            'featured_image_index.min' => 'Chỉ số ảnh đại diện phải lớn hơn hoặc bằng 0.',

            // Category IDs
            'category_ids.array'      => 'Định dạng danh mục không hợp lệ.',
            'category_ids.*.exists'   => 'Một trong các danh mục được chọn không tồn tại.',
        ];
    }
    public function withValidator(Validator $validator): void
    {
        $validator->after(function ($validator) {
            $postId = $this->route('id');
            $post = Post::withCount('images')->findOrFail($postId);

            $deletedIds = (array) $this->input('deleted_image_ids', []);
            $newImages = (array) $this->file('image_url', []);
            
            // Kiểm tra tổng số ảnh cuối cùng không được vượt quá 5
            $finalImageCount = ($post->images_count - count($deletedIds)) + count($newImages);
            if ($finalImageCount > 5) {
                $validator->errors()->add('image_url', 'Tổng số ảnh của một bài viết không được vượt quá 5.');
            }

            // Kiểm tra chỉ số ảnh đại diện có hợp lệ không
            if ($this->filled('featured_image_index')) {
                $featuredIndex = (int) $this->input('featured_image_index');
                if ($featuredIndex >= $finalImageCount) {
                    $validator->errors()->add('featured_image_index', 'Chỉ số ảnh đại diện không hợp lệ hoặc vượt quá số lượng ảnh.');
                }
            }
        });
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
