<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Carbon\Carbon;

class PromotionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // Hàm để xác định trạng thái động của khuyến mãi
        $now = Carbon::now();
        $startDate = new Carbon($this->start_date);
        $endDate = $this->end_date ? new Carbon($this->end_date) : null;
        $status = 'Đã tắt';
        if ($this->is_active) {
            if ($endDate && $now->gt($endDate)) {
                $status = 'Đã kết thúc';
            } elseif ($now->lt($startDate)) {
                $status = 'Sắp diễn ra';
            } else {
                $status = 'Đang diễn ra';
            }
        }

        return [
            'id' => $this->id,
            'name' => $this->name,
            'code' => $this->code,
            'description' => $this->description,
            'application_type' => $this->application_type,
            'type' => $this->type,
            'value' => floatval($this->value),
             // Lấy ảnh trực tiếp từ mối quan hệ 'image'
            'image' => $this->whenLoaded('image', function () {
                return $this->image ? new ImageResource($this->image) : null;
            }),
            'conditions' => [
                'min_order_value' => $this->whenNotNull(floatval($this->min_order_value)),
                'max_discount_amount' => $this->whenNotNull(floatval($this->max_discount_amount)),
            ],
            'usage_limits' => [
                'max_usage' => $this->whenNotNull($this->max_usage),
                'max_usage_per_user' => $this->whenNotNull($this->max_usage_per_user),
            ],
            'is_combinable' => $this->is_combinable,
            'is_active' => $this->is_active,
            'status_text' => $status, // Trạng thái được tính toán
            'dates' => [
                'start_date' => $this->start_date->toIso8601String(),
                'end_date' => $this->whenNotNull($this->end_date?->toIso8601String()),
            ],

            // Trả về một danh sách sản phẩm đơn giản (chỉ ID và Tên)
            'products' => $this->whenLoaded('products', function () {
                return $this->products->map(fn($product) => [
                    'id' => $product->id,
                    'name' => $product->name,
                ]);
            }),

            // Trả về một danh sách danh mục đơn giản
            'categories' => $this->whenLoaded('categories', function () {
                return $this->categories->map(fn($category) => [
                    'id' => $category->id,
                    'name' => $category->name,
                ]);
            }),
            
            // Trả về một danh sách combo đơn giản
            'combos' => $this->whenLoaded('combos', function () {
                return $this->combos->map(fn($combo) => [
                    'id' => $combo->id,
                    'name' => $combo->name,
                ]);
            }),
        ];
    }
}
