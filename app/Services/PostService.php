<?php

namespace App\Services;
use App\Models\Post;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Intervention\Image\Facades\Image;
use Illuminate\Support\Facades\Storage;
class PostService
{
   /**
     * Lấy danh sách tất cả bài viết với phân trang, tìm kiếm và sắp xếp
     *
     * @param \Illuminate\Http\Request $request
     * @return array Mảng chứa dữ liệu bài viết và thông tin phân trang
     * @throws Exception
     */
    public function getAllPosts($request): array
    {
        try {
            // Chuẩn hóa các tham số đầu vào
            $perPage = max(1, min(100, (int) $request->input('per_page', 10)));
            $currentPage = max(1, (int) $request->input('page', 1));
            $keyword = (string) $request->input('keyword', '');

            // Khởi tạo truy vấn cơ bản
            $query = Post::query();

            // Áp dụng tìm kiếm nếu có từ khóa
            if (!empty($keyword)) {
                $query->where(function ($q) use ($keyword) {
                    $q->where('title', 'like', "%{$keyword}%")
                        ->orWhere('content', 'like', "%{$keyword}%");
                });
            }

            // Sắp xếp theo thời gian tạo mới nhất
            $query->orderBy('created_at', 'desc');

            // Chỉ lấy các trường cần thiết
            $query->select([
                'id',
                'title',
                'content',
                'slug',
                'status',
                'image_url',
                'created_at',
                'updated_at',
            ]);

            // Tải quan hệ categories
            $query->with(['categories']);

            // Tính tổng số bản ghi
            $total = $query->count();

            // Thực hiện phân trang thủ công
            $offset = ($currentPage - 1) * $perPage;
            $posts = $query->skip($offset)->take($perPage)->get();

            // Tính toán thông tin phân trang
            $lastPage = (int) ceil($total / $perPage);
            $nextPage = $currentPage < $lastPage ? $currentPage + 1 : null;
            $prevPage = $currentPage > 1 ? $currentPage - 1 : null;

            // Trả về mảng dữ liệu và thông tin phân trang
            return [
                'data' => $posts,
                'page' => $currentPage,
                'total' => $total,
                'last_page' => $lastPage,
                'next_page' => $nextPage,
                'pre_page' => $prevPage,
            ];
        } catch (Exception $e) {
            Log::error('Error retrieving posts: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Lấy thông tin chi tiết một bài viết
     *
     * @param int $id ID của bài viết
     * @return \App\Models\Post
     * @throws ModelNotFoundException
     */
    public function getPostById($id): Post
    {
        try {
            return Post::with(['categories'])
                ->select([
                    'id',
                    'title',
                    'content',
                    'slug',
                    'status',
                    'image_url',
                    'created_at',
                    'updated_at',
                ])
                ->findOrFail($id);
        } catch (ModelNotFoundException $e) {
            Log::error('Post not found: ID ' . $id . ' does not exist', ['exception' => $e]);
            throw $e;
        } catch (Exception $e) {
            Log::error('Error retrieving post: ' . $e->getMessage(), ['exception' => $e]);
            throw $e;
        }
    }

    /**
     * Tạo mới một bài viết
     *
     * @param array $data Dữ liệu bài viết
     * @return \App\Models\Post
     * @throws QueryException
     */
    public function createPost(array $data): Post
    {
        try {
            // Tự động tạo slug từ title nếu không có
            if (empty($data['slug'])) {
                $data['slug'] = Str::slug($data['title']);
            }

            // Kiểm tra slug đã tồn tại chưa
            if (Post::where('slug', $data['slug'])->exists()) {
                throw new Exception('Slug đã tồn tại. Vui lòng chọn tiêu đề hoặc slug khác.');
            }
            // Xử lý ảnh (image_url) chưa cắt ảnh lưu trong storage/app/public/posts_images/yyyy/mm
            if (isset($data['image']) && $data['image'] instanceof \Illuminate\Http\UploadedFile) {
                $year = now()->format('Y');
                $month = now()->format('m');
                $slug = Str::slug($data['title']);

                $path = "posts_images/{$year}/{$month}";
                $filename = uniqid($slug . '-') . '.' . $data['image']->getClientOriginalExtension();
                $fullPath = $data['image']->storeAs($path, $filename, 'public');

                $data['image_url'] = $fullPath;
                unset($data['image']);
            }
            $post = Post::create($data);

            // Gắn categories nếu có
            if (!empty($data['category_ids'])) {
                $post->categories()->sync($data['category_ids']);
            }

            return $post->load(['categories']);
        } catch (QueryException $e) {
            Log::error('Error creating post: ' . $e->getMessage());
            throw $e;
        } catch (Exception $e) {
            Log::error('Unexpected error creating post: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Cập nhật thông tin một bài viết
     *
     * @param int $id ID của bài viết
     * @param array $data Dữ liệu cập nhật
     * @return \App\Models\Post
     * @throws ModelNotFoundException
     * @throws QueryException
     */
    public function updatePost($id, array $data): Post
    {
        try {
            $post = Post::findOrFail($id);

            // Tự động tạo slug từ title nếu không có
            if (isset($data['title']) && empty($data['slug'])) {
                $data['slug'] = Str::slug($data['title']);
            }

            // Kiểm tra slug đã tồn tại chưa (trừ chính bài viết hiện tại)
            if (isset($data['slug']) && $data['slug'] !== $post->slug) {
                if (Post::where('slug', $data['slug'])->where('id', '!=', $id)->exists()) {
                    throw new Exception('Slug đã tồn tại. Vui lòng chọn tiêu đề hoặc slug khác.');
                }
            }
            // Xử lý ảnh nếu có ảnh mới được tải lên
            if (isset($data['image']) && $data['image'] instanceof \Illuminate\Http\UploadedFile) {
                // Xóa ảnh cũ nếu có
                if ($post->image_url && Storage::disk('public')->exists($post->image_url)) {
                    Storage::disk('public')->delete($post->image_url);
                }

                // Lưu ảnh mới
                $year = now()->format('Y');
                $month = now()->format('m');
                $slug = Str::slug($data['title'] ?? $post->title);

                $path = "posts_images/{$year}/{$month}";
                $filename = uniqid($slug . '-') . '.' . $data['image']->getClientOriginalExtension();
                $fullPath = $data['image']->storeAs($path, $filename, 'public');

                $data['image_url'] = $fullPath;
                unset($data['image']);
            }

            $post->update($data);

            // Cập nhật categories nếu có
            if (isset($data['category_ids'])) {
                $post->categories()->sync($data['category_ids']);
            }

            return $post->refresh()->load(['categories']);
        } catch (ModelNotFoundException $e) {
            Log::error('Post not found for update: ' . $e->getMessage());
            throw $e;
        } catch (QueryException $e) {
            Log::error('Error updating post: ' . $e->getMessage());
            throw $e;
        } catch (Exception $e) {
            Log::error('Unexpected error updating post: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Xóa một bài viết
     *
     * @param int $id ID của bài viết
     * @return bool
     * @throws ModelNotFoundException
     */
    public function deletePost($id): bool
    {
        try {
            $post = Post::findOrFail($id);
             // Xóa ảnh nếu có
            if ($post->image_url && Storage::disk('public')->exists($post->image_url)) {
                Storage::disk('public')->delete($post->image_url);
            }
            return $post->delete();
        } catch (ModelNotFoundException $e) {
            Log::error('Post not found for deletion: ' . $e->getMessage(), ['exception' => $e]);
            throw $e;
        } catch (Exception $e) {
            Log::error('Unexpected error deleting post: ' . $e->getMessage(), ['exception' => $e]);
            throw $e;
        }
    }

    /**
     * Xóa nhiều bài viết cùng lúc
     *
     * @param string $ids Chuỗi ID cách nhau bởi dấu phẩy (ví dụ: "1,2,3")
     * @return int Số lượng bản ghi đã xóa
     * @throws ModelNotFoundException
     */
    public function multiDeletePosts($ids): int
    {
        try {
            $ids = array_map('intval', explode(',', $ids));
            $posts = Post::whereIn('id', $ids)->get();

            $existingIds = Post::whereIn('id', $ids)->pluck('id')->toArray();
            $nonExistingIds = array_diff($ids, $existingIds);

            if (!empty($nonExistingIds)) {
                Log::error('IDs not found for deletion: ' . implode(',', $nonExistingIds));
                throw new ModelNotFoundException('ID cần xóa không tồn tại trong hệ thống');
            }
            // Xóa ảnh của từng bài viết
            foreach ($posts as $post) {
                if ($post->image_url && Storage::disk('public')->exists($post->image_url)) {
                    Storage::disk('public')->delete($post->image_url);
                }
            }
            return Post::whereIn('id', $ids)->delete();
        } catch (ModelNotFoundException $e) {
            Log::error('Error in multi-delete posts: ' . $e->getMessage(), ['exception' => $e]);
            throw $e;
        } catch (Exception $e) {
            Log::error('Unexpected error in multi-delete posts: ' . $e->getMessage(), ['exception' => $e]);
            throw $e;
        }
    }
}