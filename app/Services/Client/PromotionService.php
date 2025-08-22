<?php

namespace App\Services\Client;

use App\Models\Promotion;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Carbon\Carbon;

class PromotionService
{
    /**
     * Lấy danh sách các chương trình khuyến mãi đang hoạt động cho client.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Support\Collection
     */
    public function getActivePromotions(Request $request): Collection
    {
        $now = Carbon::now();
        $limit = $request->input('limit', 10); // Mặc định lấy 10 khuyến mãi

        return Promotion::where('is_active', true)
            // Điều kiện 1: Ngày bắt đầu phải nhỏ hơn hoặc bằng thời điểm hiện tại
            ->where(function ($query) use ($now) {
                $query->where('start_date', '<=', $now)
                    ->orWhereNull('start_date');
            })
            // Điều kiện 2: Ngày kết thúc phải lớn hơn hoặc bằng thời điểm hiện tại, HOẶC không có ngày kết thúc
            ->where(function ($query) use ($now) {
                $query->where('end_date', '>=', $now)
                    ->orWhereNull('end_date');
            })
            ->with('image') // Tải kèm ảnh đại diện của khuyến mãi
            ->latest()
            ->take($limit)
            ->get();
    }
    /**
     * Tìm một khuyến mãi công khai theo ID.
     *
     * @param int $id
     * @return \App\Models\Promotion
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function findPublicPromotionById(int $id): Promotion
    {
        $now = Carbon::now();

        // Tìm khuyến mãi theo ID và phải đang hoạt động, trong thời gian áp dụng
        return Promotion::where('is_active', true)
            ->where(function ($query) use ($now) {
                $query->where('start_date', '<=', $now)
                    ->orWhereNull('start_date');
            })
            ->where(function ($query) use ($now) {
                $query->where('end_date', '>=', $now)
                    ->orWhereNull('end_date');
            })
            // Tải tất cả các quan hệ cần thiết để hiển thị chi tiết
            ->with(['image', 'products', 'categories', 'combos'])
            ->findOrFail($id);
    }
}
