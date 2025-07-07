<?php

namespace App\Services;

use App\Models\Post;
use App\Models\Image as ImageModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Log;

class PostService
{
    protected ImageService $imageService;

    public function __construct(ImageService $imageService)
    {
        $this->imageService = $imageService;
    }
    /**
     * Lấy danh sách tất cả bài viết 
     */
    public function getAllPosts(Request $request): array
    {
        try {

            $perPage = max(1, min(100, (int) $request->input('limit', 25)));
            $currentPage = max(1, (int) $request->input('page', 1));
            $query = Post::query()->with(['categories', 'featuredImage']);

            if ($request->filled('keyword')) {
                $keyword = $request->input('keyword');
                $query->where(function ($q) use ($keyword) {
                    $q->where('title', 'like', "%{$keyword}%")
                        ->orWhere('content', 'like', "%{$keyword}%");
                });
            }
            if ($request->filled('category_id')) {
                $categoryId = $request->input('category_id');
                $query->whereHas('categories', function ($q) use ($categoryId) {
                    $q->where('categories.id', $categoryId);
                });
            }
            if ($request->filled('status')) {
                $query->where('status', $request->input('status'));
            }
            $query->orderBy('created_at', 'desc');
            $total = $query->count();

            $offset = ($currentPage - 1) * $perPage;
            $posts = $query->skip($offset)->take($perPage)->get();

            $lastPage = (int) ceil($total / $perPage);
            $nextPage = $currentPage < $lastPage ? $currentPage + 1 : null;
            $prevPage = $currentPage > 1 ? $currentPage - 1 : null;

            return [
                'data'      => $posts,
                'page'      => $currentPage,
                'total'     => $total,
                'last_page' => $lastPage,
                'next_page' => $nextPage,
                'pre_page'  => $prevPage,
            ];
        } catch (Exception $e) {
            Log::error('Lỗi khi lấy danh sách bài viết: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Lấy chi tiết một bài viết.
     */
    public function getPostById(int $id): Post
    {
        try {
            return Post::with(['categories', 'images', 'featuredImage'])->findOrFail($id);
        } catch (ModelNotFoundException $e) {
            Log::error("Lỗi khi cập nhật bài viết ID {$id}: " . $e->getMessage());
            throw new ModelNotFoundException("Không tìm thấy bài viết.");
        }
    }

    /**
     * Tạo một bài viết mới.
     */
    public function createPost(array $data): Post
    {
        return DB::transaction(function () use ($data) {
            try {
                $images = Arr::pull($data, 'image_url', []);
                $categoryIds = Arr::pull($data, 'category_ids', []);
                $featuredImageIndex = Arr::pull($data, 'featured_image_index', 0);

                if (empty($data['slug'])) {
                    $data['slug'] = Str::slug($data['title']);
                }
                $originalSlug = $data['slug'];
                $counter = 1;
                while (Post::where('slug', $data['slug'])->exists()) {
                    $data['slug'] = $originalSlug . '-' . $counter++;
                }

                $post = Post::create($data);

                if (!empty($images)) {
                    foreach ($images as $index => $imageFile) {
                        $basePath = $this->imageService->store($imageFile, 'posts', $post->title);
                        if ($basePath) {
                            $post->images()->create([
                                'image_url' => $basePath,
                                'is_featured' => ($index == $featuredImageIndex),
                            ]);
                        }
                    }
                }

                if (!empty($categoryIds)) {
                    $post->categories()->attach($categoryIds);
                }

                Log::info("Đã tạo bài viết mới thành công. ID: {$post->id}");
                return $post->load(['images', 'categories']);
            } catch (Exception $e) {
                Log::error('Lỗi khi tạo bài viết mới: ' . $e->getMessage());
                throw $e;
            }
        });
    }


    /**
     * Cập nhật một bài viết.
     */
    public function updatePost(int $id, array $data): Post
    {
        return DB::transaction(function () use ($id, $data) {
            try {
                $post = Post::findOrFail($id);

                $categoryIds = Arr::pull($data, 'category_ids', null);
                $newImageFiles = Arr::pull($data, 'image_url', []);
                $deletedImageIds = Arr::pull($data, 'deleted_image_ids', []);
                $featuredImageIndex = Arr::pull($data, 'featured_image_index', null);

                // Tự động tạo slug nếu cần
                if (isset($data['title']) && empty($data['slug'])) {
                    $data['slug'] = Str::slug($data['title']);
                }
                if (isset($data['slug'])) {
                    $originalSlug = $data['slug'];
                    $counter = 1;
                    while (Post::where('slug', $data['slug'])->where('id', '!=', $post->id)->exists()) {
                        $data['slug'] = $originalSlug . '-' . $counter++;
                    }
                }
                $post->update($data);

                //Xóa ảnh cũ
                if (!empty($deletedImageIds)) {
                    $imagesToDelete = ImageModel::whereIn('id', $deletedImageIds)
                        ->where('imageable_id', $post->id)
                        ->get();

                    foreach ($imagesToDelete as $image) {
                        $this->imageService->delete($image->image_url, 'posts');
                        $image->delete();
                    }
                }

                //Thêm ảnh mới
                if (!empty($newImageFiles)) {
                    foreach ($newImageFiles as $imageFile) {
                        $basePath = $this->imageService->store($imageFile, 'posts', $post->title);
                        if ($basePath) {
                            $post->images()->create(['image_url' => $basePath, 'is_featured' => false]);
                        }
                    }
                }

                //Xử lý ảnh đại diện
                $finalImages = $post->images()->orderBy('created_at')->orderBy('id')->get();
                if ($finalImages->isNotEmpty()) {
                    // Mặc định chọn ảnh đầu tiên nếu không có chỉ định
                    $indexToFeature = $featuredImageIndex ?? 0;

                    if (isset($finalImages[$indexToFeature])) {
                        $featuredImageId = $finalImages[$indexToFeature]->id;
                        $post->images()->update(['is_featured' => false]);
                        ImageModel::where('id', $featuredImageId)->update(['is_featured' => true]);
                    }
                }

                //Đồng bộ danh mục
                if (is_array($categoryIds)) {
                    $post->categories()->sync($categoryIds);
                }

                Log::info("Đã cập nhật bài viết [ID: {$post->id}]");
                return $post->refresh()->load(['images', 'categories', 'featuredImage']);
            } catch (ModelNotFoundException $e) {
                Log::warning("Không tìm thấy bài viết để cập nhật. ID: {$id}");
                throw $e;
            } catch (Exception $e) {
                Log::error("Lỗi khi cập nhật bài viết ID {$id}: " . $e->getMessage());
                throw $e;
            }
        });
    }

    /**
     * Xóa một bài viết.
     */
    public function deletePost(int $id): bool
    {
        $post = $this->getPostById($id);

        return DB::transaction(function () use ($post) {
            try {
                foreach ($post->images as $image) {
                    $this->imageService->delete($image->image_url, 'posts');
                }
                $post->images()->delete();
                $post->categories()->detach();

                $isDeleted = $post->delete();
                if ($isDeleted) {
                    Log::warning("Đã xóa bài viết thành công. ID: {$post->id}");
                }
                return $isDeleted;
            } catch (Exception $e) {
                Log::error("Lỗi khi xóa bài viết ID {$post->id}: " . $e->getMessage());
                throw $e;
            }
        });
    }

    /**
     * Xóa nhiều bài viết.
     */
    public function multiDeletePosts(array $ids): int
    {
        return DB::transaction(function () use ($ids) {
            try {
                $posts = Post::with('images')->whereIn('id', $ids)->get();
                $deletedCount = 0;

                foreach ($posts as $post) {
                    foreach ($post->images as $image) {
                        $this->imageService->delete($image->image_url, 'posts');
                    }
                    $post->images()->delete();
                    $post->categories()->detach();
                    if ($post->delete()) {
                        $deletedCount++;
                    }
                }

                if ($deletedCount > 0) {
                    Log::warning("{$deletedCount} bài viết đã được xóa thành công bởi người dùng.");
                }

                return $deletedCount;
            } catch (Exception $e) {
                Log::error("Lỗi khi xóa nhiều bài viết: " . $e->getMessage());
                // Ném lại exception để Controller có thể bắt và xử lý
                throw $e;
            }
        });
    }
}
