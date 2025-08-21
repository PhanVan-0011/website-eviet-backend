<?php

namespace App\Services\Client;
use App\Models\Product;
use App\Models\Combo;
use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Carbon\Carbon;

class SearchService
{
     /**
     * Thực hiện tìm kiếm tổng hợp trên nhiều loại nội dung.
     *
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function searchAll(Request $request): array
    {
       $keyword = $request->input('keyword', '');
       $limit = (int) $request->input('limit', 5);
       $now = Carbon::now();
        if (empty($keyword)) {
            return [
                'products' => collect(),
                'combos' => collect(),
                'posts' => collect(),
            ];
        }
        // Tìm kiếm sản phẩm
        $products = Product::where('status', 1)
            ->where('name', 'like', "%{$keyword}%")
            ->with('featuredImage')
            ->take($limit)
            ->get();

        // Tìm kiếm combo: Chỉ những combo đang hoạt động VÀ trong thời gian áp dụng
        $combos = Combo::where('is_active', true)
            ->where(function ($q) use ($now) {
                $q->where('start_date', '<=', $now)
                    ->orWhereNull('start_date');
            })
            ->where(function ($q) use ($now) {
                $q->where('end_date', '>=', $now)
                    ->orWhereNull('end_date');
            })
            ->where('name', 'like', "%{$keyword}%")
            ->with('image')
            ->take($limit)
            ->get();
        
        // Tìm kiếm tin tức
        $posts = Post::where('status', 1)
            ->where('title', 'like', "%{$keyword}%")
            ->with('featuredImage')
            ->take($limit)
            ->get();

        return [
            'products' => $products,
            'combos' => $combos,
            'posts' => $posts,
        ];
    }
}