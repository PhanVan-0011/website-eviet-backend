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

            $query = Combo::query();

            if ($keyword !== '') {
                $query->where(function ($q) use ($keyword) {
                    $q->where('name', 'like', "%{$keyword}%")
                        ->orWhere('description', 'like', "%{$keyword}%");
                });
            }

            if (!is_null($isActive)) {
                $query->where('is_active', filter_var($isActive, FILTER_VALIDATE_BOOLEAN));
            }

            $query->orderBy('created_at', 'desc');

            $total = $query->count();
            $offset = ($currentPage - 1) * $perPage;
            $combos = $query->skip($offset)->take($perPage)->get();

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
        return Combo::with('items')->findOrFail($id);
    }


    public function createCombo(array $data): Combo
    {
        try {
             // Tự động tạo slug từ title nếu không có
            if (empty($data['slug'])) {
                $data['slug'] = Str::slug($data['name']);
            }

            // Kiểm tra slug đã tồn tại chưa
            if (Combo::where('slug', $data['slug'])->exists()) {
                throw new Exception('Slug đã tồn tại. Vui lòng chọn tiêu đề hoặc slug khác.');
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

            return Combo::create($data);
        } catch (QueryException $e) {
            Log::error('Lỗi khi tạo combo: ' . $e->getMessage());
            throw $e;
        } catch (Exception $e) {
            Log::error('Lỗi không xác định khi tạo combo: ' . $e->getMessage());
            throw $e;
        }
    }


    public function updateCombo(int $id, array $data)
    {
        try {
            $combo = Combo::findOrFail($id);

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
            return $combo;
        } catch (QueryException $e) {
            Log::error('Lỗi khi cập nhật combo: ' . $e->getMessage());
            throw $e;
        } catch (Exception $e) {
            Log::error('Lỗi không xác định khi cập nhật combo: ' . $e->getMessage());
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
