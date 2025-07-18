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
use App\Models\Combo;

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
            if ($request->query('context') === 'select_list') {

                $this->authorize('combos.select_list'); // Kiểm tra quyền 'select_list'

                $combos = Combo::where('is_active', 1)->with('images')->latest()->get();
                $data = $combos->map(function ($combo) {
                    return [
                        'id' => $combo->id,
                        'name' => $combo->name,
                        'image_urls' => $this->formatImages($combo->images),
                    ];
                });
                return response()->json($data);
            }
            else {
                $this->authorize('combos.view'); // Kiểm tra quyền 'view'

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
            }
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
         $this->authorize('combos.view');
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
    public function store(StoreComboRequest $request){
        $this->authorize('combos.manage');
        try {
            $combo = $this->comboService->createCombo($request->validated());
            return response()->json([
                'message' => 'Tạo combo thành công',
                'data' => new ComboResource($combo),
                'success' => true,
            ], 201);
        } catch (\Exception $e) {
            Log::error('Lỗi tạo combo: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi tạo combo',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    // Cập nhật combo
    public function update(UpdateComboRequest $request, int $id)
    {
        $this->authorize('combos.manage');
        try {
            $combo = $this->comboService->updateCombo($id, $request->validated());

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
        $this->authorize('combos.manage');
        try {
            $deleted = $this->comboService->deleteCombo($id);
            return response()->json([
                'success' => true,
                'message' => 'Xóa combo thành công'
            ]);
            
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Combo không tồn tại.'
            ], 404);
        }
        catch (\Exception $e) {
            Log::error('Lỗi khi xóa combo: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $e->getMessage() 
            ], 422);
        }
    }

    // Xóa nhiều combo
    public function multiDelete(MultiDeleteComboRequest $request)
    {
        $this->authorize('combos.manage');
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
            Log::error('Lỗi không xác định khi xóa nhiều combo: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }
}
