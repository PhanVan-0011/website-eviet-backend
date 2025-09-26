<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Branch\StoreBranchRequest;
use App\Http\Requests\Api\Branch\UpdateBranchRequest;
use App\Http\Requests\Api\Branch\MultiDeleteBranchRequest;
use App\Http\Resources\BranchResource;
use App\Services\BranchService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
class BranchController extends Controller
{
   protected BranchService $branchService;

    public function __construct(BranchService $branchService)
    {
        $this->branchService = $branchService;
    }
    /**
     * Lấy danh sách tất cả chi nhánh.
     */
    public function index(Request $request)
    {
        try {
            $data = $this->branchService->getAllBranches($request);
            return response()->json([
                'success' => true,
                'data' => BranchResource::collection($data['data']),
                'pagination' => [
                    'page' => $data['page'],
                    'total' => $data['total'],
                    'last_page' => $data['last_page'],
                    'next_page' => $data['next_page'],
                    'pre_page' => $data['pre_page'],
                ],
                'message' => 'Lấy danh sách chi nhánh thành công',
                'timestamp' => now()->format('d-m-Y H:i:s'),
            ], 200);
        } catch (Exception $e) {
            Log::error('Lỗi khi lấy danh sách chi nhánh: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi lấy danh sách chi nhánh',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
     /**
     * Lấy thông tin chi tiết một chi nhánh.
     */
    public function show(string $id)
    {
        try {
            $branch = $this->branchService->getBranchById($id);
            return response()->json([
                'success' => true,
                'data' => new BranchResource($branch),
                'message' => 'Lấy thông tin chi nhánh thành công',
            ], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy chi nhánh',
            ], 404);
        } catch (Exception $e) {
            Log::error('Lỗi khi lấy thông tin chi nhánh: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi lấy thông tin chi nhánh',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Tạo mới một chi nhánh.
     */
    public function store(StoreBranchRequest $request)
    {
        try {
            $branch = $this->branchService->createBranch($request->validated());
            return response()->json([
                'success' => true,
                'data' => new BranchResource($branch),
                'message' => 'Tạo chi nhánh thành công',
            ], 201);
        } catch (Exception $e) {
            Log::error('Lỗi khi tạo chi nhánh: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi tạo chi nhánh',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Cập nhật thông tin một chi nhánh.
     */
    public function update(UpdateBranchRequest $request, string $id)
    {
        try {
            $branch = $this->branchService->updateBranch($id, $request->validated());
            return response()->json([
                'success' => true,
                'data' => new BranchResource($branch),
                'message' => 'Cập nhật chi nhánh thành công',
            ], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy chi nhánh',
            ], 404);
        } catch (Exception $e) {
            Log::error('Lỗi khi cập nhật chi nhánh: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi cập nhật chi nhánh',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Xóa một chi nhánh.
     */
   
    public function destroy(string $id)
    {
        try {
            $this->branchService->deleteBranch($id);
            return response()->json([
                'success' => true,
                'message' => 'Xóa chi nhánh thành công',
            ], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy chi nhánh',
            ], 404);
        } catch (Exception $e) {
            Log::error('Lỗi khi xóa chi nhánh: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Xóa nhiều chi nhánh cùng lúc.
     */
    public function multiDelete(MultiDeleteBranchRequest $request)
    {
        try {
            $deletedCount = $this->branchService->multiDelete($request->validated()['ids']);
            return response()->json([
                'success' => true,
                'message' => "Đã xóa thành công {$deletedCount} chi nhánh"
            ], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'error' => $e->getMessage(),
            ], 404);
        } catch (Exception $e) {
            Log::error('Lỗi khi xóa nhiều chi nhánh: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
