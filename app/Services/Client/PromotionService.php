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
            ->where('start_date', '<=', $now)
            ->where(function ($query) use ($now) {
                $query->where('end_date', '>=', $now)
                      ->orWhereNull('end_date');
            })
            ->latest()
            ->take($limit)
            ->get();
    }
    /**
     *Tìm một khuyến mãi công khai theo ID.
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
            ->where('start_date', '<=', $now)
            ->where(function ($query) use ($now) {
                $query->where('end_date', '>=', $now)
                      ->orWhereNull('end_date');
            })
            ->with(['products', 'categories', 'combos'])
            ->findOrFail($id);
    }
}