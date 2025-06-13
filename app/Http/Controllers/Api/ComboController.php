<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Combo\StoreComboRequest;
use App\Http\Requests\Api\Combo\UpdateComboRequest;
use App\Http\Requests\Api\Combo\MultiDeleteComboRequest;
use App\Http\Resources\ComboResource;
use App\Services\ComboService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class ComboController extends Controller
{
    protected $comboService;

    public function __construct(ComboService $comboService)
    {
        $this->comboService = $comboService;
    }

    // Lấy danh sách combo
    public function index(Request $request)
    {
        try {
            $data = $this->comboService->getAllCombos($request);
            return response()->json([
                'success' => true,
                'data' => ComboResource::collection($data['data']),
                'pagination' => [
                    'page' => $data['page'],
                    'total' => $data['total'],
                    'last_page' => $data['last_page'],
                    'next_page' => $data['next_page'],
                    'prev_page' => $data['prev_page'],
                ],
                'message' => 'Lấy danh sách combo thành công',
                'timestamp' => now()->format('Y-m-d H:i:s'),
            ], 200);
        } catch (\Exception $e) {
            Log::error('Lỗi lấy danh sách combo: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi lấy danh sách combo',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    // Xem chi tiết combo
    public function show(int $id)
    {
        try {
            $combo = $this->comboService->getComboById($id);
            return response()->json([
                'success' => true,
                'data' => new ComboResource($combo),
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Combo không tồn tại',
            ], 404);
        } catch (\Exception $e) {
            Log::error('Lỗi khi lấy combo: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi lấy thông tin combo'
            ], 500);
        }
    }
    // Tạo combo mới
    public function store(StoreComboRequest $request)
    {
        try {
            $combo = $this->comboService->createCombo($request->validated());
            return response()->json([
                'message' => 'Tạo combo thành công',
                'data' => new ComboResource($combo),
            ], 201);
        } catch (\Exception $e) {
            Log::error('Lỗi tạo combo: ' . $e->getMessage());
            return response()->json([
                'message' => 'Lỗi khi tạo combo',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    // Cập nhật combo
    public function update(UpdateComboRequest $request, int $id)
    {
        try {
            $combo = $this->comboService->updateCombo($id, $request->all());
            return response()->json([
                'success' => true,
                'message' => 'Cập nhật combo thành công',
                'data' => new ComboResource($combo),
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Combo không tồn tại',
            ], 404);
        } catch (\Exception $e) {
            Log::error('Lỗi cập nhật combo: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi cập nhật combo',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Xóa combo đơn
    public function destroy(int $id)
    {
        try {
            $deleted = $this->comboService->delete($id);
            if (!$deleted) {
                return response()->json([
                    'success' => false,
                    'message' => 'Combo không tồn tại'
                ], 404);
            }
            return response()->json([
                'success' => true,
                'message' => 'Xóa combo thành công'
            ]);
        } catch (\Exception $e) {
            Log::error('Lỗi khi xóa combo: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi xóa combo',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Xóa nhiều combo
    public function multiDelete(MultiDeleteComboRequest $request)
    {
        try {
            $deletedCount = $this->comboService->deleteMultiple($request->validated()['ids']);
            return response()->json([
                'success' => true,
                'message' => "Đã xóa thành công {$deletedCount} combo",
            ], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 404);
        } catch (\Exception $e) {
            Log::error('Lỗi xóa nhiều combo: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Đã xảy ra lỗi khi xóa combo',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
