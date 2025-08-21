<?php

namespace App\Http\Controllers\Api\Client;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\Client\CategoryService;
use App\Http\Resources\CategoryResource;
use Illuminate\Support\Facades\Log;

class CategoryController extends Controller
{
   protected $categoryService;

    public function __construct(CategoryService $categoryService)
    {
        $this->categoryService = $categoryService;
    }

    /**
     * Lấy danh sách danh mục công khai.
     */
    public function index(Request $request)
    {
        try {
            $categories = $this->categoryService->getPublicCategories();

            return response()->json([
                'success' => true,
                'message' => 'Lấy danh sách danh mục thành công.',
                'data' => CategoryResource::collection($categories)
            ]);
        } catch (\Exception $e) {
            Log::error('Lỗi khi lấy danh sách danh mục công khai: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Không thể lấy danh sách danh mục.'
            ], 500);
        }
    }
}
