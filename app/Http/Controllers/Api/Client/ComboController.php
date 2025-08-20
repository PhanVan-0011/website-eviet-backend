<?php

namespace App\Http\Controllers\Api\Client;

use App\Http\Controllers\Controller;
use App\Services\Client\ComboService;
use App\Http\Resources\ComboResource;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Log;

class ComboController extends Controller
{
     protected $comboService;

    public function __construct(ComboService $comboService)
    {
        $this->comboService = $comboService;
    }

    /**
     * Lấy danh sách combo công khai.
     */
    public function index(Request $request)
    {
        try {
            $result = $this->comboService->getPublicCombos($request);

            return response()->json([
                'success' => true,
                'message' => 'Lấy danh sách combo thành công.',
                'data' => ComboResource::collection($result['data']),
                'pagination' => $result['pagination']
            ]);
        } catch (\Exception $e) {
            Log::error('Lỗi khi lấy danh sách combo công khai: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Không thể lấy danh sách combo.'
            ], 500);
        }
    }

    /**
     * Lấy thông tin chi tiết một combo công khai.
     */
    public function show(int $id)
    {
        try {
            $combo = $this->comboService->findPublicComboById($id);

            return response()->json([
                'success' => true,
                'data' => new ComboResource($combo)
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy combo hoặc combo đã hết hạn.'
            ], 404);
        } catch (\Exception $e) {
            Log::error("Lỗi khi lấy chi tiết combo công khai #{$id}: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Đã có lỗi xảy ra.'
            ], 500);
        }
    }
}
