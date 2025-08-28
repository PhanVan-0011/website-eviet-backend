<?php

namespace App\Services;
use App\Models\ProductAttribute;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Models\CartItem;         
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

    /**
     * Cập nhật một thuộc tính và các giá trị của nó (chỉ cập nhật, không xóa).
     */
    public function updateAttribute(int $id, array $data): ProductAttribute
    {
        $attribute = $this->getAttributeById($id);
        try {
            return DB::transaction(function () use ($attribute, $data) {
                // Cập nhật thông tin chính của thuộc tính
                $attribute->update($data);

                // Chỉ xử lý thêm/sửa các giá trị được gửi lên
                if (isset($data['values'])) {
                    foreach ($data['values'] as $valueData) {
                        if (isset($valueData['id'])) {
                            // Cập nhật value đã có
                            $value = $attribute->values()->find($valueData['id']);
                            if ($value) {
                                $value->update($valueData);
                            }
                        } else {
                            // Tạo value mới
                            $attribute->values()->create($valueData);
                        }
                    }
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
    
}