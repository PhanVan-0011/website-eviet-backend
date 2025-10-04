<?php

namespace App\Services;
use App\Models\ProductUnitConversion;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class ProductUnitConversionService
{
     /**
     * Lấy danh sách tất cả các quy tắc chuyển đổi cho một sản phẩm cụ thể.
     */
    public function getConversionsByProductId(int $productId): Collection
    {
        try {
            return ProductUnitConversion::where('product_id', $productId)->get();
        } catch (Exception $e) {
            Log::error('Lỗi khi lấy danh sách quy tắc chuyển đổi cho sản phẩm ID ' . $productId . ': ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Lấy chi tiết một quy tắc chuyển đổi.
     */
    public function getConversionById(int $id): ProductUnitConversion
    {
        try {
            return ProductUnitConversion::findOrFail($id);
        } catch (ModelNotFoundException $e) {
            throw $e; 
        } catch (Exception $e) {
            Log::error('Lỗi khi lấy chi tiết quy tắc chuyển đổi ID ' . $id . ': ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Tạo mới một quy tắc chuyển đổi đơn vị.
     */
    public function createConversion(array $data): ProductUnitConversion
    {
        try {
            return ProductUnitConversion::create($data);
        } catch (Exception $e) {
            Log::error('Lỗi khi tạo quy tắc chuyển đổi đơn vị: ' . $e->getMessage());
            throw new Exception('Không thể tạo quy tắc chuyển đổi đơn vị.'); 
        }
    }

    /**
     * Cập nhật quy tắc chuyển đổi đơn vị.
     */
    public function updateConversion(int $id, array $data): ProductUnitConversion
    {
        try {
            $conversion = $this->getConversionById($id);
            $conversion->update($data);
            return $conversion;
        } catch (ModelNotFoundException $e) {
            throw $e; 
        } catch (Exception $e) {
            Log::error("Lỗi khi cập nhật quy tắc chuyển đổi đơn vị (ID: {$id}): " . $e->getMessage());
            throw new Exception("Không thể cập nhật quy tắc chuyển đổi đơn vị ID {$id}.");
        }
    }

    /**
     * Xóa một quy tắc chuyển đổi đơn vị, kiểm tra ràng buộc khóa ngoại.
     */
    public function deleteConversion(int $id): bool
    {
        $conversion = ProductUnitConversion::findOrFail($id); 
        try {
            return DB::transaction(function () use ($conversion, $id) {
                $conversion->delete();
                return true;
            });
       } catch (ModelNotFoundException $e) {
            throw $e; 

        } catch (QueryException $e) {
            if ($e->getCode() === '23000') {
                throw new Exception("Không thể xóa quy tắc đơn vị này. Quy tắc đang được sử dụng trong các dữ liệu lịch sử hoặc cấu hình khác.");
            }
            Log::error("Lỗi Query không xác định khi xóa quy tắc đơn vị ID {$id}: " . $e->getMessage());
            throw new Exception("Lỗi CSDL khi xóa quy tắc đơn vị ID {$id}.");
        } catch (Exception $e) {
            Log::error("Lỗi khi xóa quy tắc chuyển đổi đơn vị (ID: {$id}): " . $e->getMessage());
            throw new Exception("Không thể xóa quy tắc chuyển đổi đơn vị ID {$id}.");
        }
    }
}