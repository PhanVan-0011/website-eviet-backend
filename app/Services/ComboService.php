<?php

namespace App\Services;

use App\Models\Combo;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\QueryException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Exception;

class ComboService
{
    public function getAllCombos($request): array
    {
        try {
            $perPage = max(1, min(100, (int) $request->input('per_page', 10)));
            $currentPage = max(1, (int) $request->input('page', 1));
            $keyword = trim((string) $request->input('keyword', ''));
            $isActive = $request->input('is_active'); // bool hoặc null

            $minPrice = $request->input('min_price');
            $maxPrice = $request->input('max_price');

            $startDateFrom = $request->input('start_date_from');
            $startDateTo   = $request->input('start_date_to');

            $endDateFrom   = $request->input('end_date_from');
            $endDateTo     = $request->input('end_date_to');

            $query = Combo::query();

            if ($keyword !== '') {
                $query->where(function ($q) use ($keyword) {
                    $q->where('name', 'like', "%{$keyword}%")
                        ->orWhere('description', 'like', "%{$keyword}%")
                        ->orWhere('slug', 'like', "%{$keyword}%");
                });
            }

            if (!is_null($isActive)) {
                $query->where('is_active', filter_var($isActive, FILTER_VALIDATE_BOOLEAN));
            }
            // Lọc theo khoảng giá min và max
            if (!is_null($minPrice)) {
                $query->where('price', '>=', (float)$minPrice);
            }
            if (!is_null($maxPrice)) {
                $query->where('price', '<=', (float)$maxPrice);
            }

            // Lọc theo khoảng ngày bắt đầu
            if ($startDateFrom) {
                $query->where('start_date', '>=', $startDateFrom);
            }
            if ($startDateTo) {
                $query->where('start_date', '<=', $startDateTo);
            }
            // Lọc theo khoảng ngày kết thúc
            if ($endDateFrom) {
                $query->where('end_date', '>=', $endDateFrom);
            }
            if ($endDateTo) {
                $query->where('end_date', '<=', $endDateTo);
            }
            $query->orderBy('created_at', 'desc');

            $total = $query->count();
            $offset = ($currentPage - 1) * $perPage;
            $combos = $query->with(['items.product'])->skip($offset)->take($perPage)->get();

            $lastPage = (int) ceil($total / $perPage);
            $nextPage = $currentPage < $lastPage ? $currentPage + 1 : null;
            $prevPage = $currentPage > 1 ? $currentPage - 1 : null;

            return [
                'data' => $combos,
                'page' => $currentPage,
                'total' => $total,
                'last_page' => $lastPage,
                'next_page' => $nextPage,
                'prev_page' => $prevPage,
            ];
        } catch (\Exception $e) {
            Log::error('Lỗi khi lấy danh sách combo: ' . $e->getMessage(), [
                'request' => $request->all()
            ]);
            throw $e;
        }
    }


    public function getComboById(int $id): Combo
    {
        return Combo::with('items.product')->findOrFail($id);
    }


    public function createCombo(array $data): Combo
    {
        try {
            // Tự động tạo slug 
            if (empty($data['slug'])) {
                $data['slug'] = Str::slug($data['name']);
            }

            // Kiểm tra slug trùng
            if (Combo::where('slug', $data['slug'])->exists()) {
                throw new Exception('Slug đã tồn tại. Vui lòng chọn tên hoặc slug khác.');
            }
            // Xử lý ảnh nếu có
            if (isset($data['image_url']) && $data['image_url'] instanceof \Illuminate\Http\UploadedFile) {
                $year = now()->format('Y');
                $month = now()->format('m');
                $slug = Str::slug($data['name'] ?? 'combo');
                $path = "combos_images/{$year}/{$month}";
                $filename = uniqid($slug . '-') . '.' . $data['image_url']->getClientOriginalExtension();
                $fullPath = $data['image_url']->storeAs($path, $filename, 'public');
                $data['image_url'] = $fullPath;
            } else {
                unset($data['image_url']);
            }
            // Lấy items ra và bỏ khỏi $data trước khi tạo combo
            $items = $data['items'] ?? [];
            unset($data['items']);
            // Tạo combo
            $combo = Combo::create($data);
            // Ghi combo_items nếu có
            foreach ($items as $item) {
                $combo->items()->create([
                    'product_id' => $item['product_id'],
                    'quantity'   => $item['quantity'],
                ]);
            }
            return $combo->load('items.product');
        } catch (QueryException $e) {
            Log::error('Lỗi khi tạo combo: ' . $e->getMessage());
            throw $e;
        }
    }
    public function updateCombo(int $id, array $data)
    {
        try {
            $combo = Combo::with('items')->findOrFail($id);
            // Tạo lại slug nếu không có
            if (empty($data['slug'])) {
                $data['slug'] = Str::slug($data['name']);
            }
            // Kiểm tra trùng slug (trừ chính combo đang cập nhật)
            if (Combo::where('slug', $data['slug'])->where('id', '!=', $id)->exists()) {
                throw new \Exception('Slug đã tồn tại.');
            }
            //Xử lý ảnh
            if (isset($data['image_url']) && $data['image_url'] instanceof \Illuminate\Http\UploadedFile) {
                if ($combo->image_url && Storage::disk('public')->exists($combo->image_url)) {
                    Storage::disk('public')->delete($combo->image_url);
                }

                $year = now()->format('Y');
                $month = now()->format('m');
                $slug = Str::slug($data['name'] ?? 'combo');
                $path = "combos_images/{$year}/{$month}";
                $filename = uniqid($slug . '-') . '.' . $data['image_url']->getClientOriginalExtension();
                $fullPath = $data['image_url']->storeAs($path, $filename, 'public');
                $data['image_url'] = $fullPath;
            } else {
                unset($data['image_url']);
            }

            $combo->update($data);
            // Cập nhật combo items nếu có
            if (!empty($data['items'])) {
                $combo->items()->delete();
                foreach ($data['items'] as $item) {
                    $combo->items()->create([
                        'product_id' => $item['product_id'],
                        'quantity'   => $item['quantity'],
                    ]);
                }
            }
            return $combo->fresh('items.product');
        } catch (QueryException $e) {
            Log::error('Lỗi khi cập nhật combo: ' . $e->getMessage());
            throw $e;
        }
    }

    public function delete(int $id)
    {
        try {
            $combo = Combo::findOrFail($id);

            if ($combo->image_url && Storage::disk('public')->exists($combo->image_url)) {
                Storage::disk('public')->delete($combo->image_url);
            }

            return $combo->delete();
        } catch (ModelNotFoundException $e) {
            Log::warning("Combo ID {$id} không tồn tại.");
            return false;
        } catch (Exception $e) {
            Log::error("Lỗi khi xóa combo ID {$id}: " . $e->getMessage());
            throw $e;
        }
    }

    public function deleteMultiple($ids)
    {
        $ids = array_map('intval', explode(',', $ids));

        $existingIds = Combo::whereIn('id', $ids)->pluck('id')->toArray();
        $nonExistingIds = array_diff($ids, $existingIds);

        if (!empty($nonExistingIds)) {
            Log::error('IDs combo không tồn tại: ' . implode(',', $nonExistingIds));
            throw new ModelNotFoundException('Một hoặc nhiều combo không tồn tại.');
        }

        $deletedCount = 0;
        $combos = Combo::whereIn('id', $ids)->get();

        foreach ($combos as $combo) {
            try {
                if ($combo->image_url && Storage::disk('public')->exists($combo->image_url)) {
                    Storage::disk('public')->delete($combo->image_url);
                }

                $combo->delete();
                $deletedCount++;
            } catch (Exception $e) {
                Log::error("Lỗi khi xóa combo ID {$combo->id}: " . $e->getMessage());
            }
        }

        return $deletedCount;
    }
}
