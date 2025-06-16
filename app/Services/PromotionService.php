<?php

namespace App\Services;

use App\Models\Promotion;
use Illuminate\Support\Facades\DB;

class PromotionService
{
    public function createPromotion(array $data): Promotion
    {
        return DB::transaction(function () use ($data) {

            $relatedIds = $this->extractRelatedIds($data);

            //Tạo bản ghi khuyến mãi chính trong bảng `promotions`.
            $promotion = Promotion::create($data);

            //Gắn các liên kết với sản phẩm, danh mục, combo (nếu có).
            $this->attachRelations($promotion, $relatedIds);

            //Trả về đối tượng Promotion vừa tạo, load sẵn các quan hệ để hiển thị.
            return $promotion->load(['products', 'categories', 'combos']);
        });
    }
    /**
     * Cập nhật một chương trình khuyến mãi đã có.
     *
     */
    public function updatePromotion(Promotion $promotion, array $data): Promotion
    {
        return DB::transaction(function () use ($promotion, $data) {
            $relatedIds = $this->extractRelatedIds($data);

            //Cập nhật các trường thông tin chính trong bảng `promotions`.
            $promotion->update($data);

            // Đồng bộ hóa các liên kết.
            // sync() là phương thức rất mạnh mẽ, nó sẽ tự động:
            // - Thêm các liên kết mới.
            // - Xóa các liên kết không còn được gửi lên.
            // - Giữ nguyên các liên kết đã có.
            $this->syncRelations($promotion, $relatedIds);

            // Trả về đối tượng Promotion đã được làm mới với các quan hệ mới nhất.
            return $promotion->fresh(['products', 'categories', 'combos']);
        });
    }

    /**
     * Xóa một chương trình khuyến mãi một cách an toàn.
     *
     */
    public function deletePromotion(Promotion $promotion)
    {
        // Kiểm tra xem khuyến mãi này đã từng được áp dụng cho đơn hàng nào chưa.
        if ($promotion->appliedOrders()->exists()) {
            // Nếu đã được dùng, không xóa vĩnh viễn để bảo toàn dữ liệu lịch sử.
            // Thay vào đó, chúng ta chỉ vô hiệu hóa nó.
            $promotion->is_active = false;
            $promotion->save();
            return false; // Báo hiệu đây là hành động "vô hiệu hóa".
        }

        // Nếu khuyến mãi chưa từng được sử dụng, có thể xóa vĩnh viễn.
        return $promotion->delete(); // Báo hiệu đây là hành động "xóa vĩnh viễn".
    }

    /**
     * Hàm hỗ trợ để tách các mảng ID ra khỏi dữ liệu chính.
     */
    private function extractRelatedIds(array &$data): array
    {
        $relatedIds = [
            'products' => $data['product_ids'] ?? null,
            'categories' => $data['category_ids'] ?? null,
            'combos' => $data['combo_ids'] ?? null,
        ];
        // Xóa các key này khỏi mảng data gốc để không gây lỗi khi dùng create() hoặc update().
        unset($data['product_ids'], $data['category_ids'], $data['combo_ids']);
        return $relatedIds;
    }

    /**
     * Hàm hỗ trợ để gắn các mối quan hệ khi tạo mới.
     */
    private function attachRelations(Promotion $promotion, array $relatedIds): void
    {
        if (!is_null($relatedIds['products'])) {
            $promotion->products()->attach($relatedIds['products']);
        }
        if (!is_null($relatedIds['categories'])) {
            $promotion->categories()->attach($relatedIds['categories']);
        }
        if (!is_null($relatedIds['combos'])) {
            $promotion->combos()->attach($relatedIds['combos']);
        }
    }

    /**
     * Hàm hỗ trợ để đồng bộ hóa các mối quan hệ khi cập nhật.
     */
    private function syncRelations(Promotion $promotion, array $relatedIds): void
    {
        // Chỉ sync nếu key tồn tại trong mảng data, cho phép cập nhật riêng lẻ.
        if (array_key_exists('products', $relatedIds)) {
            $promotion->products()->sync($relatedIds['products'] ?? []);
        }
        if (array_key_exists('categories', $relatedIds)) {
            $promotion->categories()->sync($relatedIds['categories'] ?? []);
        }
        if (array_key_exists('combos', $relatedIds)) {
            $promotion->combos()->sync($relatedIds['combos'] ?? []);
        }
    }
}
