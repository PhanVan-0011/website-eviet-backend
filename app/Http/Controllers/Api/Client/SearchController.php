<?php

namespace App\Http\Controllers\Api\Client;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\Client\SearchService;
use App\Http\Resources\ProductResource;
use App\Http\Resources\ComboResource;
use App\Http\Resources\PostResource;
use Illuminate\Support\Facades\Log;

class SearchController extends Controller
{
    protected $searchService;

    public function __construct(SearchService $searchService)
    {
        $this->searchService = $searchService;
    }

    /**
     * API tìm kiếm tổng hợp.
     */
    public function search(Request $request)
    {
        try {
            $results = $this->searchService->searchAll($request);

            return response()->json([
                'success' => true,
                'message' => 'Kết quả tìm kiếm.',
                'data' => [
                    'products' => ProductResource::collection($results['products']),
                    'combos' => ComboResource::collection($results['combos']),
                    'posts' => PostResource::collection($results['posts']),
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Lỗi API tìm kiếm tổng hợp: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Đã có lỗi xảy ra trong quá trình tìm kiếm.'
            ], 500);
        }
    }
}
