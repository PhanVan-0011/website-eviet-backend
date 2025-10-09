<?php

namespace App\Services;

use App\Models\ProductAttribute;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Models\CartItem;
use App\Models\AttributeValue;
use Exception;

class ProductAttributeService
{

    /**
     * Lấy danh sách các thuộc tính của một sản phẩm (phân trang thủ công).
     */
    public function getPaginatedAttributes(Request $request): array
    {
        try {
            $perPage = max(1, min(100, (int) $request->input('limit', 10)));
            $currentPage = max(1, (int) $request->input('page', 1));
            $productId = $request->input('product_id');
            $keyword = $request->input('keyword');
            $type = $request->input('type');

            $query = ProductAttribute::query()
                ->with('values')
                ->where('product_id', $productId);

            // Thêm logic tìm kiếm theo từ khóa (keyword)
            if ($keyword) {
                $query->where('name', 'like', "%{$keyword}%");
            }

            // Thêm logic lọc theo loại (type)
            if ($type) {
                $query->where('type', $type);
            }

            $query->orderBy('display_order', 'asc');

            $total = $query->count();
            $offset = ($currentPage - 1) * $perPage;
            $attributes = $query->skip($offset)->take($perPage)->get();

            $lastPage = (int) ceil($total / $perPage);

            return [
                'data' => $attributes,
                'page' => $currentPage,
                'total' => $total,
                'last_page' => $lastPage,
                'next_page' => $currentPage < $lastPage ? $currentPage + 1 : null,
                'prev_page' => $currentPage > 1 ? $currentPage - 1 : null,
            ];
        } catch (Exception $e) {
            Log::error('Lỗi khi lấy danh sách thuộc tính sản phẩm: ' . $e->getMessage());
            throw $e;
        }
    }
    /**
     * Lấy chi tiết một thuộc tính bằng ID.
     */
    public function getAttributeById(int $id): ProductAttribute
    {
        try {
            return ProductAttribute::with('values')->findOrFail($id);
        } catch (ModelNotFoundException $e) {
            Log::warning("Không tìm thấy thuộc tính sản phẩm với ID: {$id}");
            throw new ModelNotFoundException("Không tìm thấy thuộc tính sản phẩm.");
        }
    }
    /**
     * Tạo mới một thuộc tính và các giá trị của nó.
     */
    public function createAttribute(array $data): ProductAttribute
    {
        try {
            return DB::transaction(function () use ($data) {
                $attribute = ProductAttribute::create([
                    'product_id' => $data['product_id'],
                    'name' => $data['name'],
                    'type' => $data['type'],
                    'display_order' => $data['display_order'] ?? 0,
                ]);

                if (isset($data['values'])) {
                    $attribute->values()->createMany($data['values']);
                }

                return $attribute->load('values');
            });
        } catch (Exception $e) {
            Log::error('Lỗi khi tạo thuộc tính sản phẩm: ' . $e->getMessage());
            throw $e;
        }
    }

     public function updateAttribute(int $id, array $data): ProductAttribute
    {
        $attribute = $this->getAttributeById($id);
        $submittedValueIds = [];
        $valuesToDelete = [];
        $existingValueIds = $attribute->values->pluck('id')->toArray();
        
        try {
            return DB::transaction(function () use ($attribute, $data, &$submittedValueIds, &$valuesToDelete, $existingValueIds) {
                // 1. Cập nhật thông tin chính của thuộc tính
                $attribute->update($data);

                if (isset($data['values'])) {
                    foreach ($data['values'] as $valueData) {
                        if (isset($valueData['id'])) {
                            $valueId = $valueData['id'];
                            $submittedValueIds[] = $valueId;
                            
                            // 1a. Cập nhật value đã có
                            $value = $attribute->values()->find($valueId);
                            if ($value) {
                                $value->update($valueData);
                            }
                        } else {
                            // 1b. Tạo value mới
                            $attribute->values()->create($valueData);
                        }
                    }
                }
                
                // 2. XÁC ĐỊNH VÀ KIỂM TRA giá trị CẦN XÓA (Logic SYNC)
                
                // Lọc ra các ID giá trị CÓ trong DB nhưng KHÔNG có trong payload
                $valueIdsToKeep = $submittedValueIds;
                $valueIdsToDelete = array_diff($existingValueIds, $valueIdsToKeep);

                if (!empty($valueIdsToDelete)) {
                    $valuesToDelete = $attribute->values()->whereIn('id', $valueIdsToDelete)->get();

                    foreach ($valuesToDelete as $value) {
                        // Kiểm tra ràng buộc trước khi xóa (Nghiệp vụ quan trọng)
                        $isUsed = CartItem::whereJsonContains('attributes', ['value' => $value->value])
                                          ->whereJsonContains('attributes', ['name' => $attribute->name])
                                          ->exists();

                        if ($isUsed) {
                            // Ném lỗi nghiệp vụ nếu giá trị đang được sử dụng
                            throw new Exception("Không thể xóa giá trị '{$value->value}' (ID: {$value->id}) vì đang được sử dụng trong giỏ hàng hoặc đơn hàng.");
                        }
                    }

                    // Thực hiện xóa các giá trị đã kiểm tra và hợp lệ
                    $attribute->values()->whereIn('id', $valueIdsToDelete)->delete();
                }

                return $attribute->load('values');
            });
        } catch (Exception $e) {
            Log::error("Lỗi khi cập nhật thuộc tính sản phẩm ID {$id}: " . $e->getMessage());
            throw $e;
        }
    }
    /**
     * Xóa một thuộc tính.
     */
    public function deleteAttribute(int $id): void
    {
        $attribute = $this->getAttributeById($id);

        // Kiểm tra xem thuộc tính có đang được sử dụng trong giỏ hàng nào không
        $isUsed = CartItem::whereJsonContains('attributes', ['name' => $attribute->name])->exists();
        if ($isUsed) {
            throw new Exception("Không thể xóa thuộc tính '{$attribute->name}' vì đang được sử dụng trong giỏ hàng hoặc đơn hàng.");
        }

        try {
            $attribute->delete();
        } catch (Exception $e) {
            Log::error("Lỗi khi xóa thuộc tính sản phẩm ID {$id}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Xóa nhiều thuộc tính dựa trên danh sách ID.
     */
    public function deleteMultiple(array $ids): int
    {
        $attributes = ProductAttribute::whereIn('id', $ids)->get();
        if ($attributes->count() !== count(array_unique($ids))) {
            throw new ModelNotFoundException('Một hoặc nhiều thuộc tính không tồn tại.');
        }

        // Kiểm tra ràng buộc dữ liệu phát sinh
        foreach ($attributes as $attribute) {
            $isUsed = CartItem::whereJsonContains('attributes', ['name' => $attribute->name])->exists();
            if ($isUsed) {
                throw new Exception("Không thể xóa thuộc tính '{$attribute->name}' (ID: {$attribute->id}) vì đang được sử dụng.");
            }
        }

        try {
            return ProductAttribute::whereIn('id', $ids)->delete();
        } catch (Exception $e) {
            Log::error("Lỗi khi xóa nhiều thuộc tính sản phẩm: " . $e->getMessage());
            throw $e;
        }
    }

    public function deleteAttributeValue(int $id): void
    {
        $value = AttributeValue::with('productAttribute')->findOrFail($id);

        // Logic kiểm tra ràng buộc (Nghiệp vụ)
        $isUsed = CartItem::whereJsonContains('attributes', ['value' => $value->value])
            ->whereJsonContains('attributes', ['name' => $value->productAttribute->name])
            ->exists();

        if ($isUsed) {
            throw new Exception("Không thể xóa giá trị '{$value->value}' vì đang được sử dụng.");
        }

        try {
            $value->delete(); // Thao tác DB (sau khi kiểm tra nghiệp vụ)
        } catch (Exception $e) {
            Log::error("Lỗi khi xóa giá trị thuộc tính ID {$id}: " . $e->getMessage());
            throw $e;
        }
    }
}
