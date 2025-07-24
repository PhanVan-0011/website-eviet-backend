<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Post\StorePostRequest;
use App\Http\Requests\Api\Post\UpdatePostRequest;
use App\Http\Requests\Api\Post\MultiDeletePostRequest;
use App\Http\Resources\PostResource;
use App\Services\PostService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use App\Models\Post;

class PostController extends Controller
{
    protected $postService;
    public function __construct(PostService $postService)
    {
        $this->postService = $postService;
    }

    /**
     * Lấy danh sách tất cả bài viết với phân trang, tìm kiếm và sắp xếp.
     */
    public function index(Request $request)
    {
        try {

            // $this->authorize('posts.view'); // Kiểm tra quyền 'view'

            $data = $this->postService->getAllPosts($request);
            return response()->json([
                'success' => true,
                'data' => PostResource::collection($data['data']),
                'pagination' => [
                    'page' => $data['page'],
                    'total' => $data['total'],
                    'last_page' => $data['last_page'],
                    'next_page' => $data['next_page'],
                    'pre_page' => $data['pre_page'],
                ],
                'message' => 'Lấy danh sách bài viết thành công',
                'timestamp' => now()->format('Y-m-d H:i:s'),
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi lấy danh sách bài viết',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Lấy chi tiết một bài viết.
     */
    public function show(int $id)
    {
        try {
            $post = $this->postService->getPostById($id);
            return response()->json([
                'success' => true,
                'message' => 'Lấy thông tin bài viết thành công',
                'data' => new PostResource($post),
            ], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(['success' => false, 'message' => 'Không tìm thấy bài viết.'], 404);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Đã xảy ra lỗi.'], 500);
        }
    }
    /**
     * Tạo mới một bài viết.
     */
    public function store(StorePostRequest $request)
    {
        try {
            $post = $this->postService->createPost($request->validated());
            return response()->json([
                'success' => true,
                'message' => 'Tạo bài viết thành công',
                'data' => new PostResource($post),
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Đã xảy ra lỗi khi tạo bài viết.'], 500);
        }
    }

    /**
     * Cập nhật một bài viết.
     */
    public function update(UpdatePostRequest $request, int $id)
    {
        try {
            $post = $this->postService->updatePost($id, $request->validated());
            return response()->json([
                'success' => true,
                'message' => 'Cập nhật bài viết thành công',
                'data' => new PostResource($post),
            ], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(['success' => false, 'message' => 'Không tìm thấy bài viết để cập nhật.'], 404);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Đã xảy ra lỗi khi cập nhật.'], 500);
        }
    }

    /**
     * Xóa một bài viết.
     */
    public function destroy(int $id)
    {
        try {
            $this->postService->deletePost($id);
            return response()->json(['success' => true, 'message' => 'Xóa bài viết thành công'], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(['success' => false, 'message' => 'Không tìm thấy bài viết để xóa.'], 404);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Đã xảy ra lỗi khi xóa.'], 500);
        }
    }

    /**
     * Xóa nhiều bài viết.
     */
    public function multiDelete(MultiDeletePostRequest $request)
    {
        try {
            $validated = $request->validated();
            $deletedCount = $this->postService->multiDeletePosts($validated['ids']);

            return response()->json([
                'success' => true,
                'message' => "Đã xóa thành công {$deletedCount} bài viết.",
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Đã xảy ra lỗi trong quá trình xóa.'], 500);
        }
    }
}
