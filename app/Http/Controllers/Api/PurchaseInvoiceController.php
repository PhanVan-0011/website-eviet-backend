<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\Api\PurchaseInvoice\StorePurchaseInvoiceRequest;
use App\Http\Requests\Api\PurchaseInvoice\UpdatePurchaseInvoiceRequest;
use App\Http\Requests\Api\PurchaseInvoice\MultiDeletePurchaseInvoiceRequest;
use App\Http\Resources\PurchaseInvoiceResource;
use App\Services\PurchaseInvoiceService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Exception;
use Illuminate\Support\Facades\Log;
use App\Models\Supplier;
use App\Models\PurchaseInvoice;

class PurchaseInvoiceController extends Controller
{
    protected PurchaseInvoiceService $invoiceService;

    public function __construct(PurchaseInvoiceService $invoiceService)
    {
        $this->invoiceService = $invoiceService;
    }

    /**
     * Lấy danh sách tất cả hóa đơn nhập hàng.
     */
    public function index(Request $request)
    {
        try {
            $data = $this->invoiceService->getAllInvoices($request);
            return response()->json([
                'success' => true,
                'data' => PurchaseInvoiceResource::collection($data['data']),
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
            return response()->json(['success' => false, 'message' => 'Lỗi khi lấy danh sách hóa đơn nhập', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Lấy chi tiết một hóa đơn nhập hàng.
     */
    public function show(string $id)
    {
        try {
            $invoice = $this->invoiceService->getInvoiceById($id);
            return response()->json(['success' => true, 'data' => new PurchaseInvoiceResource($invoice)], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(['success' => false, 'message' => 'Không tìm thấy hóa đơn nhập'], 404);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => 'Lỗi khi lấy chi tiết hóa đơn', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Tạo mới một hóa đơn nhập hàng.
     */
    public function store(StorePurchaseInvoiceRequest $request)
    {
        try {
            $invoice = $this->invoiceService->createInvoice($request->validated());
            return response()->json(['success' => true, 'data' => new PurchaseInvoiceResource($invoice), 'message' => 'Tạo hóa đơn nhập thành công'], 201);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => 'Lỗi khi tạo hóa đơn nhập', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Cập nhật hóa đơn nhập hàng.
     */
    public function update(UpdatePurchaseInvoiceRequest $request, string $id)
    {
        try {
            $invoice = $this->invoiceService->updateInvoice($id, $request->validated());
            return response()->json(['success' => true, 'data' => new PurchaseInvoiceResource($invoice), 'message' => 'Cập nhật hóa đơn nhập thành công'], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(['success' => false, 'message' => 'Không tìm thấy hóa đơn nhập'], 404);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => 'Lỗi khi cập nhật hóa đơn nhập', 'error' => $e->getMessage()], 500);
        }
    }

    public function getHistoryBySupplier(Request $request, string $supplierId)
    {
        try {
            // Kiểm tra xem nhà cung cấp có tồn tại không
            Supplier::findOrFail($supplierId);

            // Tạo một request mới và thêm supplier_id vào để tái sử dụng hàm getAllInvoices
            $filterRequest = new Request($request->query()); // Lấy các tham số query (page, limit, start_date...)
            $filterRequest->merge(['supplier_id' => $supplierId]); // Ép bộ lọc theo nhà cung cấp

            // Gọi hàm lấy danh sách với bộ lọc đã được thêm vào
            $data = $this->invoiceService->getAllInvoices($filterRequest);

            return response()->json([
                'success' => true,
                'data' => PurchaseInvoiceResource::collection($data['data']),
                'pagination' => [
                    'page' => $data['page'],
                    'total' => $data['total'],
                    'last_page' => $data['last_page'],
                    'next_page' => $data['next_page'],
                    'pre_page' => $data['pre_page'],
                ],
                'message' => 'Lấy lịch sử nhập hàng của nhà cung cấp thành công',
            ], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(['success' => false, 'message' => 'Không tìm thấy nhà cung cấp'], 404);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => 'Lỗi khi lấy lịch sử nhập hàng', 'error' => $e->getMessage()], 500);
        }
    }
    /**
     * HÀM MỚI: Hủy một phiếu nhập đã hoàn thành
     */
    public function cancel(string $id)
    {
        try {
            $invoice = PurchaseInvoice::findOrFail($id);
            if ($invoice->status == 'cancelled') {
                return response()->json([
                    'success' => false,
                    'message' => 'Phiếu nhập này đã được hủy trước đó'
                ], 400);
            }
            $data = ['status' => 'cancelled'];
            $invoice = $this->invoiceService->updateInvoice($id, $data);

            return response()->json(['success' => true, 'data' => new PurchaseInvoiceResource($invoice), 'message' => 'Hủy phiếu nhập thành công'], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(['success' => false, 'message' => 'Không tìm thấy hóa đơn nhập'], 404);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => 'Lỗi khi hủy phiếu nhập', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Xóa một hóa đơn nhập hàng.
     */
    public function destroy(string $id)
    {
        try {
            $this->invoiceService->deleteInvoice($id);
            return response()->json(['success' => true, 'message' => 'Xóa hóa đơn nhập thành công'], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(['success' => false, 'message' => 'Không tìm thấy hóa đơn nhập'], 404);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => 'Lỗi khi xóa hóa đơn nhập', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Xóa nhiều hóa đơn nhập hàng.
     */
    public function multiDelete(MultiDeletePurchaseInvoiceRequest $request)
    {
        try {
            $deletedCount = $this->invoiceService->multiDelete($request->validated()['ids']);
            return response()->json(['success' => true, 'message' => "Đã xóa thành công {$deletedCount} hóa đơn nhập"], 200);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => 'Lỗi khi xóa nhiều hóa đơn nhập', 'error' => $e->getMessage()], 500);
        }
    }
}
