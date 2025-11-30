<?php

namespace App\Services\Client;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class CategoryService
{
    /**
     * Lấy danh sách các danh mục công khai đang hoạt động.
     *
     * @return \Illuminate\Support\Collection
     */
    public function getPublicCategories(): Collection
    {
        // Chỉ lấy các danh mục đang hoạt động và sắp xếp theo tên
        // Load icon để trả về trong response (giống như API admin)
        return Category::where('status', 1)
            ->with(['icon'])
            ->orderBy('name', 'asc')
            ->get();
    }
}