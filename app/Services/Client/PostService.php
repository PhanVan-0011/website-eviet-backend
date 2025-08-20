<?php

namespace App\Services\Client;
use App\Models\Post;
use Illuminate\Http\Request;

class PostService
{
   /**
     * Lấy danh sách tin tức công khai với phân trang.
     *
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function getPublicPosts(Request $request): array
    {
        $perPage = max(1, min(100, (int) $request->input('limit', 10)));
        $currentPage = max(1, (int) $request->input('page', 1));

        // Bắt đầu query với điều kiện tin tức đã được xuất bản
        $query = Post::where('status', 1)->with(['categories', 'featuredImage']);

        // Lọc theo danh mục nếu có
        if ($request->filled('category_id')) {
            $categoryId = $request->input('category_id');
            $query->whereHas('categories', fn($q) => $q->where('categories.id', $categoryId));
        }

        $query->orderBy('created_at', 'desc');

        // Phân trang
        $total = $query->count();
        $posts = $query->skip(($currentPage - 1) * $perPage)->take($perPage)->get();

        return [
            'data' => $posts,
            'pagination' => [
                'page' => $currentPage,
                'total' => $total,
                'last_page' => (int) ceil($total / $perPage),
                'next_page' => $currentPage < ceil($total / $perPage) ? $currentPage + 1 : null,
                'prev_page' => $currentPage > 1 ? $currentPage - 1 : null,
            ]
        ];
    }

    /**
     * Tìm một tin tức công khai theo slug.
     *
     * @param string $slug
     * @return \App\Models\Post
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
       public function findPublicPostBySlug(string $slug): Post
    {
        return Post::where('status', 1)
            ->where('slug', $slug)
            ->with(['categories', 'images']) // Lấy tất cả hình ảnh cho trang chi tiết
            ->firstOrFail();
    }
      
}