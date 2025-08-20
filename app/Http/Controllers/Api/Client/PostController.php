<?php

namespace App\Http\Controllers\Api\Client;

use App\Http\Controllers\Controller;
use App\Services\Client\PostService;
use App\Http\Resources\PostResource;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Log;

class PostController extends Controller
{
     protected $postService;

    public function __construct(PostService $postService)
    {
        $this->postService = $postService;
    }

    /**
     * Lấy danh sách tin tức công khai.
     */
    public function index(Request $request)
    {
        try {
            $result = $this->postService->getPublicPosts($request);

            return response()->json([
                'success' => true,
                'message' => 'Lấy danh sách tin tức thành công.',
                'data' => PostResource::collection($result['data']),
                'pagination' => $result['pagination']
            ]);
        } catch (\Exception $e) {
            Log::error('Lỗi khi lấy danh sách tin tức công khai: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Không thể lấy danh sách tin tức.'
            ], 500);
        }
    }

    /**
     * Lấy thông tin chi tiết một tin tức công khai.
     */
    public function show(string $slug)
    {
        try {
            $post = $this->postService->findPublicPostBySlug($slug);

            return response()->json([
                'success' => true,
                'data' => new PostResource($post)
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy tin tức hoặc tin tức chưa được xuất bản.'
            ], 404);
        } catch (\Exception $e) {
            Log::error("Lỗi khi lấy chi tiết tin tức công khai #{$slug}: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Đã có lỗi xảy ra.'
            ], 500);
        }
    }
}
