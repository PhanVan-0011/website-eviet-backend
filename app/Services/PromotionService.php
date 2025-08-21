<?php

namespace App\Services;

use App\Models\Promotion;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Requests\Api\Promotion\GetPromotionsRequest;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Exception;
class PromotionService
{
    protected ImageService $imageService;

    public function __construct(ImageService $imageService)
    {
        $this->imageService = $imageService;
    }
    public function getAllPromotions(GetPromotionsRequest $request): array
    {
        try {
            $perPage = max(1, min(100, (int) $request->input('limit', 10)));
            $currentPage = max(1, (int) $request->input('page', 1));

            $query = Promotion::with('image');

            if ($request->filled('keyword')) {
                $keyword = $request->input('keyword');
                $query->where(function ($q) use ($keyword) {
                    $q->where('name', 'like', "%{$keyword}%")
                        ->orWhere('code', 'like', "%{$keyword}%");
                });
            }

            // Lọc theo loại khuyến mãi
            if ($request->filled('type')) {
                $query->where('type', $request->input('type'));
            }

            // Lọc theo phạm vi áp dụng
            if ($request->filled('application_type')) {
                $query->where('application_type', $request->input('application_type'));
            }

            //Lọc trực tiếp theo cột 'is_active'
            if ($request->filled('is_active')) {
                $query->where('is_active', $request->boolean('is_active'));
            }
            //Lọc theo khoảng thời gian 
            if ($request->filled('start_date')) {
                $query->whereDate('start_date', '>=', $request->input('start_date'));
            }
            // Lọc các khuyến mãi KẾT THÚC trước ngày end_date
            if ($request->filled('end_date')) {
                $query->whereDate('end_date', '<=', $request->input('end_date'));
            }
            // Sắp xếp theo khuyến mãi mới nhất
            $query->latest('created_at');

            // 4. Phân trang thủ công (giữ nguyên logic của bạn)
            $total = $query->count();
            $offset = ($currentPage - 1) * $perPage;
            // Lấy dữ liệu với các quan hệ cần thiết
            $promotions = $query->with(['products:id,name', 'categories:id,name', 'combos:id,name'])
                ->skip($offset)
                ->take($perPage)
                ->get();

            $lastPage = (int) ceil($total / $perPage);
            $nextPage = $currentPage < $lastPage ? $currentPage + 1 : null;
            $prevPage = $currentPage > 1 ? $currentPage - 1 : null;

            // Trả về kết quả dưới dạng mảng phẳng
            return [
                'data' => $promotions,
                'page' => $currentPage,
                'total' => $total,
                'last_page' => $lastPage,
                'next_page' => $nextPage,
                'pre_page' => $prevPage,
            ];
        } catch (\Exception $e) {
            Log::error('Lỗi lấy danh sách khuyến mãi: ' . $e->getMessage());
            throw $e;
        }
    }
    public function getPromotionById(int $id): Promotion
    {
        try {
            return Promotion::with('image')->findOrFail($id);
        } catch (ModelNotFoundException $e) {
            Log::warning("Khuyến mãi không tồn tại, ID: {$id}");
            throw $e;
        }
    }
    public function createPromotion(array $data): Promotion
    {
        try {
            return DB::transaction(function () use ($data) {

                $imageFile = Arr::pull($data, 'image_url');

                $promotion = Promotion::create($data);

                // Xử lý lưu ảnh
                if ($imageFile instanceof UploadedFile) {
                   
                    $basePath = $this->imageService->store($imageFile, 'promotions', $promotion->name);
                    if ($basePath) {
                        $promotion->image()->create(['image_url' => $basePath]);
                    }
                }

                if ($promotion->application_type === 'products' && !empty($data['product_ids'])) {
                    $promotion->products()->attach($data['product_ids']);
                }
                if ($promotion->application_type === 'categories' && !empty($data['category_ids'])) {
                    $promotion->categories()->attach($data['category_ids']);
                }
                if ($promotion->application_type === 'combos' && !empty($data['combo_ids'])) {
                    $promotion->combos()->attach($data['combo_ids']);
                }

                return $promotion->load('image');
            });
        } catch (\Exception $e) {
            Log::error('Lỗi khi tạo khuyến mãi: ' . $e->getMessage());
            throw $e;
        }
    }
    /**
     * Cập nhật một chương trình khuyến mãi đã có.
     *
     */
    public function updatePromotion(Promotion $promotion, array $data): Promotion
    {
        try {
            return DB::transaction(function () use ($promotion, $data) {

                $imageFile = Arr::pull($data, 'image_url');
                if ($imageFile instanceof UploadedFile) {
                    // Xóa ảnh cũ nếu tồn tại
                    if ($oldImage = $promotion->image) {
                        $this->imageService->delete($oldImage->image_url, 'promotions');
                        $oldImage->delete();
                    }
                    // Lưu ảnh mới
                    $basePath = $this->imageService->store($imageFile, 'promotions', $data['name'] ?? $promotion->name);
                    if ($basePath) {
                        $promotion->image()->create([
                            'image_url'   => $basePath,
                        ]);
                    }
                }
                
                $promotion->update($data);
                $newApplicationType = $promotion->application_type;

                // Đồng bộ hóa các liên kết một cách cẩn thận
                $promotion->products()->sync($newApplicationType === 'products' ? ($data['product_ids'] ?? []) : []);
                $promotion->categories()->sync($newApplicationType === 'categories' ? ($data['category_ids'] ?? []) : []);
                $promotion->combos()->sync($newApplicationType === 'combos' ? ($data['combo_ids'] ?? []) : []);

                //Trả về đối tượng Promotion đã được làm mới với các quan hệ mới nhất
                return $promotion->fresh(['products', 'categories', 'combos',"image"]);
            });
        } catch (\Exception $e) {
            Log::error('Lỗi khi tạo khuyến mãi: ' . $e->getMessage());
            throw $e;
        }
    }
    /**
     * Xóa một chương trình khuyến mãi một cách an toàn.
     *
     */
    public function deletePromotion(Promotion $promotion)
    {
        // Kiểm tra xem khuyến mãi này đã từng được áp dụng cho đơn hàng nào chưa.
        try {
            if ($promotion->appliedOrders()->exists()) {
                // Nếu đã được dùng, không xóa vĩnh viễn để bảo toàn dữ liệu lịch sử.
                // Tiến hành vô hiệu hóa
                $promotion->is_active = false;
                $promotion->save();
                return false;
            }

            return DB::transaction(function () use ($promotion) {
                // Xóa ảnh liên quan
                if ($image = $promotion->image) {
                    $this->imageService->delete($image->image_url, 'promotions');
                    $image->delete();
                }

                // Xóa các liên kết trong bảng pivot
                $promotion->products()->detach();
                $promotion->categories()->detach();
                $promotion->combos()->detach();

                $promotion->delete();
                Log::warning("Khuyến mãi ID {$promotion->id} đã được xóa vĩnh viễn.");
                return true; // Báo hiệu là đã xóa vĩnh viễn (hard delete)
            });
        } catch (\Exception $e) {
            Log::error('Lỗi khi tạo khuyến mãi: ' . $e->getMessage());
            throw $e;
        }
    }
    public function deleteMultiplePromotions(array $promotionIds): array
    {
        $hardDeletedCount = 0;
        $softDeletedCount = 0;
        $failedPromotions = [];

        $promotions = Promotion::with('image')->whereIn('id', $promotionIds)->get();

        foreach ($promotions as $promotion) {
            try {
                $isHardDeleted = $this->deletePromotion($promotion);
                if ($isHardDeleted) {
                    $hardDeletedCount++;
                } else {
                    $softDeletedCount++;
                }
            } catch (Exception $e) {
                Log::error("Lỗi khi xử lý xóa khuyến mãi ID {$promotion->id}: " . $e->getMessage());
                $failedPromotions[] = [
                    'id' => $promotion->id,
                    'code' => $promotion->code,
                    'reason' => 'Lỗi hệ thống khi xử lý.',
                ];
            }
        }

        return [
            'hard_deleted_count' => $hardDeletedCount,
            'soft_deleted_count' => $softDeletedCount,
            'failed_promotions' => $failedPromotions,
        ];
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
