<?php

namespace App\Services\Client;
use App\Models\Combo;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Carbon\Carbon;

class ComboService
{
   /**
     * Lấy danh sách combo công khai với phân trang.
     *
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function getPublicCombos(Request $request): array
    {
        $perPage = max(1, min(100, (int) $request->input('limit', 10)));
        $currentPage = max(1, (int) $request->input('page', 1));
        $currentTime = Carbon::now(); // Lấy thời gian hiện tại, bao gồm cả giờ, phút, giây

        //combo phải đang hoạt động
        $query = Combo::where('is_active', true)
            // Combo đã bắt đầu (so sánh cả ngày và giờ)
            ->where(function ($q) use ($currentTime) {
                $q->where('start_date', '<=', $currentTime)
                  ->orWhereNull('start_date');
            })
            // Combo chưa kết thúc (so sánh cả ngày và giờ)
            ->where(function ($q) use ($currentTime) {
                $q->where('end_date', '>=', $currentTime)
                  ->orWhereNull('end_date');
            })
            ->with(['image']);


        $query->orderBy('created_at', 'desc');

        // Phân trang
        $total = $query->count();
        $combos = $query->skip(($currentPage - 1) * $perPage)->take($perPage)->get();

        return [
            'data' => $combos,
            'pagination' => [
                'page' => $currentPage,
                'total' => $total,
                'last_page' => (int) ceil($total / $perPage),
                'next_page' => $currentPage < ceil($total / $perPage) ? $currentPage + 1 : null,
                'prev_page' => $currentPage > 1 ? $currentPage - 1 : null,
            ]
        ];
    }

    /**
     * Tìm một combo công khai theo ID.
     *
     * @param int $id
     * @return \App\Models\Combo
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function findPublicComboById(int $id): Combo
    {
               $currentTime = Carbon::now(); // Lấy thời gian hiện tại, bao gồm cả giờ, phút, giây

        // Tìm combo theo ID và phải đang hoạt động, trong thời gian áp dụng (so sánh cả ngày và giờ)
        return Combo::where('is_active', true)
            ->where(function ($q) use ($currentTime) {
                $q->where('start_date', '<=', $currentTime)
                  ->orWhereNull('start_date');
            })
            ->where(function ($q) use ($currentTime) {
                $q->where('end_date', '>=', $currentTime)
                  ->orWhereNull('end_date');
            })
            ->with(['image', 'items.product.featuredImage']) // Lấy cả sản phẩm bên trong combo
            ->findOrFail($id);
    }
}