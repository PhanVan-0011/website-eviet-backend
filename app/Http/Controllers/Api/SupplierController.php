<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Suppliers\MultiDeleteSuppliersRequest;
use App\Http\Requests\Api\Suppliers\StoreSupplierRequest;
use App\Http\Requests\Api\Suppliers\UpdateSupplierRequest;
use App\Http\Resources\SupplierResource;
use App\Services\SupplierService;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Exception;
use Illuminate\Support\Facades\Log;

class SupplierController extends Controller
{
     protected SupplierService $supplierService;

    public function __construct(SupplierService $supplierService)
    {
        $this->supplierService = $supplierService;
    }

    /**
     * Lấy danh sách tất cả nhà cung cấp.
     */
    public function index(Request $request)
    {
        try {
            $data = $this->supplierService->getAllSuppliers($request);
            return response()->json([
                'success' => true,
                'data' => SupplierResource::collection($data['data']),
                'pagination' => [
                    'page' => $data['page'],
                    'total' => $data['total'],
                    'last_page' => $data['last_page'],
                    'next_page' => $data['next_page'],
                    'pre_page' => $data['pre_page'],
                ],
                'message' => 'Lấy danh sách nhà cung cấp thành công',
            ], 200);
        } catch (Exception $e) {
            Log::error('Lỗi khi lấy danh sách nhà cung cấp: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi lấy danh sách nhà cung cấp',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Lấy thông tin chi tiết một nhà cung cấp.
     */
    public function show(string $id)
    {
        try {
            $supplier = $this->supplierService->getSupplierById($id);
            return response()->json([
                'success' => true,
                'data' => new SupplierResource($supplier),
                'message' => 'Lấy thông tin nhà cung cấp thành công',
            ], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy nhà cung cấp',
            ], 404);
        } catch (Exception $e) {
            Log::error('Lỗi khi lấy thông tin nhà cung cấp: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi lấy thông tin nhà cung cấp',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Tạo mới một nhà cung cấp.
     */
    public function store(StoreSupplierRequest $request)
    {
        try {
            $supplier = $this->supplierService->createSupplier($request->validated());
            return response()->json([
                'success' => true,
                'data' => new SupplierResource($supplier),
                'message' => 'Tạo nhà cung cấp thành công',
            ], 201);
        } catch (Exception $e) {
            Log::error('Lỗi khi tạo nhà cung cấp: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi tạo nhà cung cấp',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Cập nhật thông tin một nhà cung cấp.
     */
    public function update(UpdateSupplierRequest $request, string $id)
    {
        try {
            $supplier = $this->supplierService->updateSupplier($id, $request->validated());
            return response()->json([
                'success' => true,
                'data' => new SupplierResource($supplier),
                'message' => 'Cập nhật nhà cung cấp thành công',
            ], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy nhà cung cấp',
            ], 404);
        } catch (Exception $e) {
            Log::error('Lỗi khi cập nhật nhà cung cấp: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi cập nhật nhà cung cấp',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    
    /**
     * Xóa một nhà cung cấp.
     */
    public function destroy(string $id)
    {
        try {
            $this->supplierService->deleteSupplier($id);
            return response()->json([
                'success' => true,
                'message' => 'Xóa nhà cung cấp thành công',
            ], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy nhà cung cấp',
            ], 404);
        } catch (Exception $e) {
            Log::error('Lỗi khi xóa nhà cung cấp: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Xóa nhiều nhà cung cấp cùng lúc.
     */
    public function multiDelete(MultiDeleteSuppliersRequest $request)
    {
        try {
            $deletedCount = $this->supplierService->multiDelete($request->validated()['ids']);
            return response()->json([
                'success' => true,
                'message' => "Đã xóa thành công {$deletedCount} nhà cung cấp"
            ], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 404);
        } catch (Exception $e) {
            Log::error('Lỗi khi xóa nhiều nhà cung cấp: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi xóa nhiều nhà cung cấp',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
