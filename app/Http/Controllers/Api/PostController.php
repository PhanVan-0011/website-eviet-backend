<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\PostRequest;
use App\Http\Resources\PostResource;
use App\Services\PostService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PostController extends Controller
{
    protected $postService;

    /**
     * Khởi tạo PostController và tiêm PostService thông qua dependency injection.
     *
     * @param \App\Services\PostService $postService
     */
    public function __construct(PostService $postService)
    {
        $this->postService = $postService;
    }

    /**
     * Lấy danh sách tất cả bài viết với phân trang, tìm kiếm và sắp xếp.
     * Gọi phương thức getAllPosts từ PostService để lấy dữ liệu.
     * Sử dụng PostResource để định dạng dữ liệu trả về.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        try {
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
            Log::error('Controller error retrieving posts: ' . $e->getMessage(), ['exception' => $e]);
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi lấy danh sách bài viết',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Lấy thông tin chi tiết một bài viết theo ID.
     * Gọi phương thức getPostById từ PostService để lấy dữ liệu.
     * Sử dụng PostResource để định dạng dữ liệu trả về.
     *
     * @param int $id ID của bài viết
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        try {
            $post = $this->postService->getPostById($id);
            return response()->json([
                'success' => true,
                'data' => new PostResource($post),
                'message' => 'Lấy thông tin bài viết thành công',
            ], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Bài viết không tồn tại',
            ], 404);
        } catch (\Exception $e) {
            Log::error('Controller error retrieving post: ' . $e->getMessage(), ['exception' => $e]);
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi lấy thông tin bài viết',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Tạo mới một bài viết.
     * Sử dụng PostRequest để validate dữ liệu đầu vào.
     * Gọi phương thức createPost từ PostService để tạo bài viết.
     *
     * @param \App\Http\Requests\PostRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(PostRequest $request)
    {
        try {
            $post = $this->postService->createPost($request->validated());

            return response()->json([
                'success' => true,
                'data' => new PostResource($post),
                'message' => 'Tạo bài viết thành công',
            ], 201);
        } catch (QueryException $e) {
            Log::error('Error creating post: ' . $e->getMessage(), ['exception' => $e]);
            return response()->json([
                'success' => false,
                'message' => 'Slug đã tồn tại',
            ], 409);
        } catch (\Exception $e) {
            Log::error('Unexpected error creating post: ' . $e->getMessage(), ['exception' => $e]);
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi tạo bài viết',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Cập nhật thông tin một bài viết theo ID.
     * Sử dụng PostRequest để validate dữ liệu đầu vào.
     * Gọi phương thức updatePost từ PostService để cập nhật bài viết.
     *
     * @param \App\Http\Requests\PostRequest $request
     * @param int $id ID của bài viết
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(PostRequest $request, $id)
    {
        try {
            $post = $this->postService->updatePost($id, $request->validated());

            return response()->json([
                'success' => true,
                'data' => new PostResource($post),
                'message' => 'Cập nhật bài viết thành công',
            ], 200);
        } catch (ModelNotFoundException $e) {
            // Xử lý trường hợp không tìm thấy bài viết
            Log::error('Post not found for update: ' . $e->getMessage(), ['exception' => $e]);
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy bài viết',
            ], 404);
        } catch (QueryException $e) {
            // Xử lý trường hợp lỗi cơ sở dữ liệu (ví dụ: slug trùng lặp)
            Log::error('Error updating post: ' . $e->getMessage(), ['exception' => $e]);
            return response()->json([
                'success' => false,
                'message' => 'Slug đã tồn tại',
            ], 409);
        } catch (\Exception $e) {
            // Ghi log lỗi và trả về phản hồi lỗi
            Log::error('Unexpected error updating post: ' . $e->getMessage(), ['exception' => $e]);
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi cập nhật bài viết',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Xóa một bài viết theo ID.
     * Gọi phương thức deletePost từ PostService để xóa bài viết.
     *
     * @param int $id ID của bài viết
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        try {
            $this->postService->deletePost($id);

            return response()->json([
                'success' => true,
                'message' => 'Xóa bài viết thành công',
            ], 200);
        } catch (ModelNotFoundException $e) {
            // Xử lý trường hợp không tìm thấy bài viết
            Log::error('Post not found for deletion: ' . $e->getMessage(), ['exception' => $e]);
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy bài viết',
            ], 404);
        } catch (\Exception $e) {
            // Ghi log lỗi và trả về phản hồi lỗi
            Log::error('Unexpected error deleting post: ' . $e->getMessage(), ['exception' => $e]);
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi xóa bài viết',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Xóa nhiều bài viết cùng lúc dựa trên danh sách ID.
     * Gọi phương thức multiDeletePosts từ PostService để xóa các bài viết.
     * Kiểm tra dữ liệu đầu vào trước khi thực hiện.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function multiDelete(Request $request)
    {
        try {
            // Kiểm tra xem trường ids có tồn tại và không rỗng không
            if (!$request->has('ids') || empty($request->ids)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Danh sách ID không hợp lệ',
                ], 400);
            }

            $deletedCount = $this->postService->multiDeletePosts($request->ids);
            return response()->json([
                'success' => true,
                'message' => "Đã xóa thành công {$deletedCount} bài viết",
            ], 200);
        } catch (ModelNotFoundException $e) {
            // Xử lý trường hợp một hoặc nhiều ID không tồn tại
            Log::error('Error in multi-delete posts: ' . $e->getMessage(), ['exception' => $e]);
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 404);
        } catch (\Exception $e) {
            Log::error('Unexpected error in multi-delete posts: ' . $e->getMessage(), ['exception' => $e]);
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi xóa bài viết',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
