<?php

namespace App\Services\Client;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class ProductService
{
    /**
     * Lấy danh sách sản phẩm công khai với phân trang.
     *
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function getPublicProducts(Request $request): array
    {
        $perPage = max(1, min(100, (int) $request->input('limit', 12)));
        $currentPage = max(1, (int) $request->input('page', 1));

        $query = Product::where('status', 1)->with(['categories', 'featuredImage']);

        if ($request->filled('keyword')) {
            $keyword = $request->input('keyword');
            $query->where('name', 'like', "%{$keyword}%");
        }

        // Lọc theo danh mục
        if ($request->filled('category_id')) {
            $categoryId = $request->input('category_id');
            $query->whereHas('categories', fn($q) => $q->where('categories.id', $categoryId));
        }
        // Sắp xếp theo sản phẩm mới nhất
        $query->orderBy('created_at', 'desc');

        // Phân trang
        $total = $query->count();
        $products = $query->skip(($currentPage - 1) * $perPage)->take($perPage)->get();

        return [
            'data' => $products,
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
     * Tìm một sản phẩm công khai theo ID.
     *
     * @param int $id
     * @return \App\Models\Product
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function findPublicProductById(int $id): Product
    {
        // Tìm sản phẩm theo ID và phải có status = 1
        return Product::where('status', 1)
            ->with(['categories', 'images', 'featuredImage'])
            ->findOrFail($id);
    }
    /**
     * Lấy danh sách sản phẩm bán chạy nhất.
     *
     * @param int $limit
     * @return \Illuminate\Support\Collection
     */
    public function getBestSellingProducts(int $limit = 10)
    {
        return Product::where('status', 1)
            ->withCount(['orderDetails as total_sold' => function ($query) {
                $query->whereHas('order', function ($q) {
                    $q->where('status', 'delivered');
                });
            }])
            ->orderByDesc('total_sold') // Sắp xếp theo số lượng bán
            ->take($limit)
            ->with(['featuredImage'])
            ->get();
    }

    /**
     *Lấy danh sách sản phẩm gợi ý (dành cho bạn).
     *
     * @param int $limit
     * @return \Illuminate\Support\Collection
     */
    public function getRecommendedProducts(int $limit = 10)
    {
        return Product::where('status', 1)
            ->inRandomOrder()
            ->take($limit)
            ->with(['featuredImage'])
            ->get();
    }
}
