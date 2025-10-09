<?php

namespace App\Services;
use App\Models\ProductUnitConversion;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Models\CartItem;
use App\Models\OrderDetail; 
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class ProductUnitConversionService
{
     /**
     * Lấy danh sách tất cả các quy tắc chuyển đổi cho một sản phẩm cụ thể.
     */
    public function getConversionsByProductId(int $productId)
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
    public function getConversionById(int $id)
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
    public function createConversion(array $data)
    {
        try {
            // Logic tạo đơn vị
            return ProductUnitConversion::create($data);
        } catch (Exception $e) {
            Log::error('Lỗi khi tạo quy tắc chuyển đổi đơn vị: ' . $e->getMessage());
            throw new Exception('Không thể tạo quy tắc chuyển đổi đơn vị.'); 
        }
    }

    /**
     * Cập nhật quy tắc chuyển đổi đơn vị.
     */
    public function updateConversion(int $id, array $data)
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
     * Xóa một quy tắc chuyển đổi đơn vị, kiểm tra ràng buộc sử dụng.
     */
    public function deleteConversion(int $id)
    {
        $conversion = ProductUnitConversion::findOrFail($id); 
        $unitName = $conversion->unit_name;
        
        // Kiểm tra sử dụng trong giỏ hàng (CartItem)
        $isUsedInCart = CartItem::where('product_id', $conversion->product_id)
            ->where('unit_of_measure', $unitName)
            ->exists();
            
        // Kiểm tra sử dụng trong chi tiết đơn hàng (OrderDetail)
        $isUsedInOrder = OrderDetail::where('product_id', $conversion->product_id)
            ->where('unit_of_measure', $unitName) 
            ->exists();

        if ($isUsedInCart || $isUsedInOrder) {
            throw new Exception("Không thể xóa đơn vị '{$unitName}'. Đơn vị này đã được sử dụng trong lịch sử đơn hàng hoặc giỏ hàng.");
        }
        //THỰC HIỆN XÓA (Trong Transaction)
        try {
            return DB::transaction(function () use ($conversion, $id) {
                $conversion->delete();
                return true;
            });
        } catch (ModelNotFoundException $e) {
            throw $e; 
        } catch (Exception $e) {
            Log::error("Lỗi khi xóa quy tắc chuyển đổi đơn vị (ID: {$id}): " . $e->getMessage());
            throw new Exception("Không thể xóa quy tắc chuyển đổi đơn vị ID {$id}.");
        }
    }
}