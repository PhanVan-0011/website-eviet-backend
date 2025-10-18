<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\Branch;

class ProductResource extends JsonResource
{
    /**
     * Định dạng dữ liệu trả về cho sản phẩm
     *
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'product_code' => $this->product_code,
            'name' => $this->name,
            'description' => $this->description,
            'status' => (int) $this->status,
            
            // Các cột giá và đơn vị tính mới
            'base_unit' => $this->base_unit, 
            'cost_price' => (float) $this->cost_price, 
            'base_store_price' => (float) $this->base_store_price, 
            'base_app_price' => (float) $this->base_app_price, 
            'is_sales_unit' => (bool) $this->is_sales_unit,
            
            'total_stock_quantity' => $this->whenLoaded('branches', function () {
                // Tính tổng quantity từ tất cả các chi nhánh được liên kết
                return $this->branches->sum('pivot.quantity');
            }),
            
            // Quan hệ phức tạp
            'images' => ImageResource::collection($this->whenLoaded('images')),
            'featured_image' => new ImageResource($this->whenLoaded('featuredImage')),
            'categories' => CategoryResource::collection($this->whenLoaded('categories')),
            
            // Tích hợp Thuộc tính và Đơn vị tính quy đổi
            'attributes' => ProductAttributeResource::collection($this->whenLoaded('attributes')),
            'unit_conversions' => ProductUnitConversionResource::collection($this->whenLoaded('unitConversions')),

            //'special_prices' => ProductPriceResource::collection($this->whenLoaded('prices')),

            //Chi nhánh
            'branches' => BranchResource::collection($this->whenLoaded('branches')), 
            // Kiếm tra trả về loại chọn chi nhánh toàn bộ hoặc 1
            'applies_to_all_branches' => $this->whenLoaded('branches', function () {
                            // Đếm tổng số chi nhánh đang hoạt động trong toàn hệ thống
                            $totalActiveBranches = Branch::where('active', true)->count();
                            // So sánh với số chi nhánh mà sản phẩm này đang được liên kết
                            // Điều kiện count() > 0 để xử lý trường hợp không có chi nhánh nào trong hệ thống
                            return ($this->branches->count() > 0) && ($this->branches->count() == $totalActiveBranches);
                        }),

            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),
            
            'promotions' => $this->whenLoaded('promotions', function () {
                // Trả về một danh sách khuyến mãi 
                return $this->promotions->map(fn($promo) => [
                    'id' => $promo->id,
                    'name' => $promo->name,
                    'code' => $promo->code,
                ]);
            }),
        ];
    }
}
