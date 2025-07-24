<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Category\MultiDeleteCategoryRequest;
use App\Http\Requests\Api\Category\StoreCategoryRequest;
use App\Http\Requests\Api\Category\UpdateCategoryRequest;
use App\Http\Resources\CategoryResource;
use App\Services\CategoryService;
use Illuminate\Http\Request;
use Illuminate\Database\QueryException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Exception;
use App\Models\Category;
use Illuminate\Support\Facades\Log;

class CategoryController extends Controller
{
    protected $categoryService;

    /**
     * CategoryController constructor.
     *
     * @param \App\Services\CategoryService $categoryService
     */
    public function __construct(CategoryService $categoryService)
    {
        $this->categoryService = $categoryService;
    }

    /**
     * Lấy danh sách tất cả danh mục
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        try {
            // $this->authorize('categories.view');
            $data = $this->categoryService->getAllCategories($request);
            return response()->json([
                'success' => true,
                'data' => CategoryResource::collection($data['data']),
                'pagination' => [
                    'page' => $data['page'],
                    'total' => $data['total'],
                    'last_page' => $data['last_page'],
                    'next_page' => $data['next_page'],
                    'pre_page' => $data['pre_page'],
                ],
                'message' => 'Lấy danh sách danh mục thành công',
                'timestamp' => now()->format('d-m-Y H:i:s'),
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi lấy danh sách danh mục',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Lấy thông tin chi tiết một danh mục
     */
    public function show($category)
    {
        // $this->authorize('categories.view');
        try {
            $category = $this->categoryService->getCategoryById($category);
            return response()->json([
                'success' => true,
                'data' => new CategoryResource($category),
                'message' => 'Lấy thông tin danh mục thành công',
            ], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy danh mục',
            ], 404);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi lấy thông tin danh mục',
            ], 500);
        }
    }
    /**
     * Tao mới danh mục một danh mục
     */
    public function store(StoreCategoryRequest $request)
    {
        // $this->authorize('categories.manage');
        try {
            $category = $this->categoryService->createCategory($request->validated());
            return response()->json([
                'success' => true,
                'data' => new CategoryResource($category),
                'message' => 'Tạo danh mục thành công',
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi tạo danh mục',
            ], 500);
        }
    }
    /**
     * Cập nhật một danh mục
     */
    public function update(UpdateCategoryRequest $request, $id)
    {
        // $this->authorize('categories.manage');
        try {
            $category = $this->categoryService->updateCategory($id, $request->validated());
            return response()->json([
                'success' => true,
                'data' => new CategoryResource($category),
                'message' => 'Cập nhật danh mục thành công',
            ], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy danh mục',
            ], 404);
        } catch (QueryException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Tên danh mục đã tồn tại',
            ], 409);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi cập nhật danh mục',
            ], 500);
        }
    }

    /**
     * Xóa một danh mục
     */
    public function destroy($id)
    {
        // $this->authorize('categories.manage');
        try {
            $deleted = $this->categoryService->deleteCategory($id);

            if (!$deleted) {
                return response()->json([
                    'success' => false,
                    'message' => 'Xóa danh mục thất bại',
                ], 500);
            }
            return response()->json([
                'success' => true,
                'message' => 'Xóa danh mục thành công',
            ], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Danh mục không tồn tại',
            ], 404);
        } catch (Exception $e) {
            if (str_contains($e->getMessage(), 'không thể xóa') || str_contains($e->getMessage(), 'đang được sử dụng')) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 400);
            }
            return response()->json([
                'success' => false,
                'message' => 'Đã xảy ra lỗi khi xóa danh mục',
            ], 500);
        }
    }
    /**
     * Xóa nhiều danh mục cùng lúc
     */
    public function multiDelete(MultiDeleteCategoryRequest $request)
    {
        // $this->authorize('categories.manage');
        try {
            $deletedCount = $this->categoryService->multiDelete($request->validated()['ids']);

            return response()->json([
                'success' => true,
                'message' => "Đã xóa thành công {$deletedCount} danh mục"
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 404);
        } catch (Exception $e) {
            if (str_contains($e->getMessage(), 'không thể xóa') || str_contains($e->getMessage(), 'đang được sử dụng')) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 400);
            }
            return response()->json([
                'success' => false,
                'message' => 'Đã xảy ra lỗi khi xóa danh mục',
            ], 500);
        }
    }
}
