<?php

namespace App\Http\Requests\Api\Post;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;
use App\Models\Post;

class UpdatePostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
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

            'image_url' => 'sometimes|nullable|array',
            'image_url.*' => 'sometimes|required|image|mimes:jpeg,png,jpg,gif|max:2048',

            'deleted_image_ids' => 'sometimes|nullable|array',
            'deleted_image_ids.*' => [
                'required',
                'integer',
                Rule::exists('images', 'id')->where(function ($query) use ($postId) {
                    $query->where('imageable_id', $postId)
                        ->where('imageable_type', Post::class);
                }),
            ],

            'featured_image_id' => [
                'sometimes',
                'nullable',
                'integer',
                Rule::exists('images', 'id')->where(function ($query) use ($postId) {
                    $query->where('imageable_id', $postId)
                        ->where('imageable_type', Post::class);
                }),
            ],
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

            // Featured Image ID
            'featured_image_id.exists' => 'Ảnh đại diện được chọn không hợp lệ hoặc không thuộc về bài viết này.',

            // Category IDs
            'category_ids.array'      => 'Định dạng danh mục không hợp lệ.',
            'category_ids.*.exists'   => 'Một trong các danh mục được chọn không tồn tại.',
        ];
    }
    public function withValidator(Validator $validator): void
    {
        $validator->after(function ($validator) {
            $postId = $this->route('id');
            $post = Post::withCount('images')->find($postId);

            if ($post) {
                $deletedIds = (array) $this->input('deleted_image_ids', []);
                $newImages = (array) $this->file('image_url', []);

                $currentImageCount = $post->images_count;
                $deletedImageCount = count($deletedIds);
                $newImageCount = count($newImages);

                // Giả sử giới hạn là 5 ảnh
                $totalImages = ($currentImageCount - $deletedImageCount) + $newImageCount;
                if ($totalImages > 5) {
                    $validator->errors()->add('image_url', 'Tổng số ảnh của một bài viết không được vượt quá 5.');
                }

                $featuredId = $this->input('featured_image_id');
                if ($featuredId && in_array($featuredId, $deletedIds)) {
                    $validator->errors()->add('featured_image_id', 'Không thể đặt ảnh đang bị xóa làm ảnh đại diện.');
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
