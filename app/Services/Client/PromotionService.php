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
        $limit = $request->input('limit', 3);

        return Promotion::where('is_active', true)
            // Bắt đầu trước hoặc bằng thời điểm hiện tại
            ->where('start_date', '<=', $now)
            // Kết thúc sau hoặc bằng thời điểm hiện tại (hoặc không có ngày kết thúc)
            ->where(function ($query) use ($now) {
                $query->where('end_date', '>=', $now)
                      ->orWhereNull('end_date');
            })
            ->latest()
            ->take($limit)
            ->get();
    }
}