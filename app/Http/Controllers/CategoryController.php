<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Exception;
use App\Http\Requests\StoreCategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;
use App\Http\Controllers\Controller; 
class CategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            $categories = Category::with(['parent', 'children'])->get();
    
            return response()->json([
                'success' => true,
                'data' => $categories,
                'message' => 'Lấy danh sách danh mục thành công',
                'timestamp' => now()->format('Y-m-d H:i:s')
            ], 200);
        } catch (Exception $e) {
            Log::error('Error retrieving categories: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi lấy danh sách danh mục',
                'errors' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreCategoryRequest $request)
    {
        try {
            $category = Category::create($request->only(['name', 'status', 'parent_id', 'description']));
            $category->load(['parent', 'children']);

            return response()->json([
                'success' => true,
                'data' => $category,
                'message' => 'Tạo danh mục thành công'
            ], 201);
        } catch (Exception $e) {
            Log::error('Error creating category: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi tạo danh mục',
                'errors' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        try {
            $category = Category::with(['parent', 'children'])->find($id);
            if (!$category) {
                return response()->json([
                    'success' => false,
                    'message' => 'Danh mục không tồn tại'
                ], 404);
            }
    
            return response()->json([
                'success' => true,
                'data' => $category,
                'message' => 'Lấy thông tin danh mục thành công',
                'timestamp' => now()->format('Y-m-d H:i:s')
            ], 200);
        } catch (Exception $e) {
            Log::error('Error retrieving category: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi lấy thông tin danh mục',
                'errors' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateCategoryRequest $request, string $id)
    {
        try {
            $category = Category::find($id);
            if (!$category) {
                return response()->json([
                    'success' => false,
                    'message' => 'Danh mục không tồn tại'
                ], 404);
            }
    
            $category->update($request->only(['name', 'status', 'parent_id', 'description']));
            $category->load(['parent', 'children']);
    
            return response()->json([
                'success' => true,
                'data' => $category,
                'message' => 'Cập nhật danh mục thành công'
            ], 200);
        }
        catch (Exception $e) {
            Log::error('Error updating category: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi cập nhật danh mục',
                'errors' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {
            $category = Category::find($id);
            if (!$category) {
                return response()->json([
                    'success' => false,
                    'message' => 'Danh mục không tồn tại'
                ], 404);
            }
            $category->delete();
    
            return response()->json([
                'success' => true,
                'message' => 'Xóa danh mục thành công'
            ], 200);
        } catch (Exception $e) {
            Log::error('Error deleting category: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi xóa danh mục',
                'errors' => $e->getMessage()
            ], 500);
        }
    }
}
