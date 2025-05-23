<?php

namespace App\Http\Controllers\Api;

use App\Services\CategoryService;
use App\Http\Requests\Api\Category\StoreCategoryRequest;
use App\Http\Requests\Api\Category\UpdateCategoryRequest;
use Exception;
use App\Models\Category;
use App\Http\Controllers\Controller; 
class CategoryController extends Controller
{
    protected $categoryService;

    public function __construct(CategoryService $categoryService)
    {
        $this->categoryService = $categoryService;
    }

    /**
     * Lấy danh sách tất cả danh mục (có phân trang)
     */
    public function index()
    {
        try {
            $perPage = request()->input('per_page', 10);
            $categories = $this->categoryService->getAllCategories($perPage);

            return response()->json([
                'success' => true,
                'data' => $categories->items(),
                'pagination' => [
                    'current_page' => $categories->currentPage(),
                    'total_pages' => $categories->lastPage(),
                    'total_items' => $categories->total(),
                    'per_page' => $categories->perPage(),
                ],
                'message' => 'Lấy danh sách danh mục thành công',
                'timestamp' => now()->format('d-m-Y H:i:s')
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi lấy danh sách danh mục',
                'errors' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Lấy thông tin chi tiết một danh mục
     */
    public function show($id)
    {
        try {
            $category = $this->categoryService->getCategoryById($id);

            return response()->json([
                'success' => true,
                'data' => $category,
                'message' => 'Lấy thông tin danh mục thành công',
                'timestamp' => now()->format('d-m-Y H:i:s')
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 404);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi lấy thông tin danh mục',
                'errors' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Tạo mới một danh mục
     */
    public function store(StoreCategoryRequest $request)
    {
        try {
            $category = $this->categoryService->createCategory($request->only(['name', 'description', 'status', 'parent_id']));

            return response()->json([
                'success' => true,
                'data' => $category,
                'message' => 'Tạo danh mục thành công',
                'timestamp' => now()->format('d-m-Y H:i:s')
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi tạo danh mục',
                'errors' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cập nhật thông tin một danh mục
     */
    public function update(UpdateCategoryRequest $request, $id)
    {
        try {
            $category = Category::find($id);
            if (!$category) {
                throw new Exception('Danh mục không tồn tại');
            }

            $originalData = $category->toArray();
            $category = $this->categoryService->updateCategory($id, $request->only(['name', 'description', 'status', 'parent_id']));

            $changedFields = [];
            foreach ($request->only(['name', 'description', 'status', 'parent_id']) as $key => $value) {
                if (!is_null($value) && $originalData[$key] != $value) {
                    $changedFields[$key] = [
                        'old' => $originalData[$key],
                        'new' => $value
                    ];
                }
            }

            return response()->json([
                'success' => true,
                'data' => $category,
                'changed_fields' => $changedFields,
                'message' => 'Cập nhật danh mục thành công',
                'timestamp' => now()->format('d-m-Y H:i:s')
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 404);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi cập nhật danh mục',
                'errors' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Xóa một danh mục
     */
    public function destroy($id)
    {
        try {
            $deleted = $this->categoryService->deleteCategory($id);

            if (!$deleted) {
                return response()->json([
                    'success' => false,
                    'message' => 'Xóa danh mục thất bại',
                    'timestamp' => now()->format('d-m-Y H:i:s')
                ], 500);
            }

            return response()->json([
                'success' => true,
                'message' => 'Xóa danh mục thành công',
                'timestamp' => now()->format('d-m-Y H:i:s')
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'timestamp' => now()->format('d-m-Y H:i:s')
            ], 404);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi xóa danh mục',
                'errors' => $e->getMessage(),
                'timestamp' => now()->format('d-m-Y H:i:s')
            ], 500);
        }
    }
}