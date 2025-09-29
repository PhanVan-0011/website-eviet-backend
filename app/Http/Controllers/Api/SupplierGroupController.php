<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\SupplierGroups\StoreSupplierGroupRequest;
use App\Http\Requests\Api\SupplierGroups\UpdateSupplierGroupRequest;
use App\Http\Requests\Api\SupplierGroups\MultiDeleteSupplierGroupRequest;
use App\Http\Resources\SupplierGroupResource;
use App\Services\SupplierGroupService;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Exception;
use Illuminate\Support\Facades\Log;

class SupplierGroupController extends Controller
{
     protected SupplierGroupService $supplierGroupService;

    public function __construct(SupplierGroupService $supplierGroupService)
    {
        $this->supplierGroupService = $supplierGroupService;
    }

    /**
     * Lấy danh sách tất cả các nhóm nhà cung cấp.
     */
    public function index(Request $request)
    {
        try {
            $data = $this->supplierGroupService->getAllSupplierGroups($request);
            return response()->json([
                'success' => true,
                'data' => SupplierGroupResource::collection($data['data']),
                'pagination' => [
                    'page' => $data['page'],
                    'total' => $data['total'],
                    'last_page' => $data['last_page'],
                    'next_page' => $data['next_page'],
                    'pre_page' => $data['pre_page'],
                ],
                'message' => 'Lấy danh sách nhóm nhà cung cấp thành công',
            ], 200);
        } catch (Exception $e) {
            Log::error('Lỗi khi lấy danh sách nhóm nhà cung cấp: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi lấy danh sách nhóm nhà cung cấp',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Lấy thông tin chi tiết một nhóm nhà cung cấp.
     */
    public function show(string $id)
    {
        try {
            $supplierGroup = $this->supplierGroupService->getSupplierGroupById($id);
            return response()->json([
                'success' => true,
                'data' => new SupplierGroupResource($supplierGroup),
                'message' => 'Lấy thông tin nhóm nhà cung cấp thành công',
            ], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy nhóm nhà cung cấp',
            ], 404);
        } catch (Exception $e) {
            Log::error('Lỗi khi lấy thông tin nhóm nhà cung cấp: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi lấy thông tin nhóm nhà cung cấp',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Tạo mới một nhóm nhà cung cấp.
     */
    public function store(StoreSupplierGroupRequest $request)
    {
        try {
            $supplierGroup = $this->supplierGroupService->createSupplierGroup($request->validated());
            return response()->json([
                'success' => true,
                'data' => new SupplierGroupResource($supplierGroup),
                'message' => 'Tạo nhóm nhà cung cấp thành công',
            ], 201);
        } catch (Exception $e) {
            Log::error('Lỗi khi tạo nhóm nhà cung cấp: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi tạo nhóm nhà cung cấp',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Cập nhật thông tin một nhóm nhà cung cấp.
     */
    public function update(UpdateSupplierGroupRequest $request, string $id)
    {
        try {
            $supplierGroup = $this->supplierGroupService->updateSupplierGroup($id, $request->validated());
            return response()->json([
                'success' => true,
                'data' => new SupplierGroupResource($supplierGroup),
                'message' => 'Cập nhật nhóm nhà cung cấp thành công',
            ], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy nhóm nhà cung cấp',
            ], 404);
        } catch (Exception $e) {
            Log::error('Lỗi khi cập nhật nhóm nhà cung cấp: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi cập nhật nhóm nhà cung cấp',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Xóa một nhóm nhà cung cấp.
     */
    public function destroy(string $id)
    {
        try {
            $this->supplierGroupService->deleteSupplierGroup($id);
            return response()->json([
                'success' => true,
                'message' => 'Xóa nhóm nhà cung cấp thành công',
            ], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy nhóm nhà cung cấp',
            ], 404);
        } catch (Exception $e) {
            Log::error('Lỗi khi xóa nhóm nhà cung cấp: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Xóa nhiều nhóm nhà cung cấp cùng lúc.
     */
    public function multiDelete(MultiDeleteSupplierGroupRequest $request)
    {
        try {
            $deletedCount = $this->supplierGroupService->multiDelete($request->validated()['ids']);
            return response()->json([
                'success' => true,
                'message' => "Đã xóa thành công {$deletedCount} nhóm nhà cung cấp"
            ], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 404);
        } catch (Exception $e) {
            Log::error('Lỗi khi xóa nhiều nhóm nhà cung cấp: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi xóa nhiều nhóm nhà cung cấp',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
