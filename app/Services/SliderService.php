<?php

namespace App\Services;

use App\Models\Slider;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\QueryException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Exception;

class SliderService
{
    public function getAllSliders($request): array
    {
        try {
            // 1. Chuẩn hóa tham số
            $perPage = max(1, min(100, (int) $request->input('per_page', 10)));
            $currentPage = max(1, (int) $request->input('page', 1));
            $keyword = trim((string) $request->input('keyword', ''));
            $isActive = $request->input('is_active'); // bool hoặc null
            $linkType = $request->input('link_type'); // string hoặc null

            // 2. Khởi tạo query
            $query = Slider::query();

            // 3. Tìm kiếm theo từ khóa
            if ($keyword !== '') {
                $query->where(function ($q) use ($keyword) {
                    $q->where('title', 'like', "%{$keyword}%")
                        ->orWhere('description', 'like', "%{$keyword}%");
                });
            }

            // 4. Lọc theo trạng thái hoạt động
            if (!is_null($isActive)) {
                $query->where('is_active', filter_var($isActive, FILTER_VALIDATE_BOOLEAN));
            }

            // 5. Lọc theo loại liên kết
            if (!empty($linkType)) {
                $query->where('link_type', $linkType);
            }

            // 6. Sắp xếp
            // Sắp xếp theo thời gian tạo mới nhất
            $query->orderBy('created_at', 'desc');
            // 7. Tính tổng số
            $total = $query->count();

            // 8. Phân trang thủ công
            $offset = ($currentPage - 1) * $perPage;
            $sliders = $query->with('combo')->skip($offset)->take($perPage)->get([
                'id',
                'title',
                'description',
                'image_url',
                'link_url',
                'display_order',
                'is_active',
                'link_type',
                'combo_id',
                'created_at',
                'updated_at',

            ]);

            // 9. Tính thông tin trang
            $lastPage = (int) ceil($total / $perPage);
            $nextPage = $currentPage < $lastPage ? $currentPage + 1 : null;
            $prevPage = $currentPage > 1 ? $currentPage - 1 : null;

            // 10. Trả kết quả
            return [
                'data' => $sliders,
                'page' => $currentPage,
                'total' => $total,
                'last_page' => $lastPage,
                'next_page' => $nextPage,
                'prev_page' => $prevPage,
            ];
        } catch (\Exception $e) {
            Log::error('Error retrieving sliders: ' . $e->getMessage(), [
                'request' => $request->all()
            ]);
            throw $e;
        }
    }

    public function getSliderById(int $id): Slider
    {
        return Slider::with('combo')->findOrFail($id);
    }

    public function createSlider(array $data): Slider
    {
        try {
            // Xử lý upload ảnh nếu có file ảnh truyền lên
            if (isset($data['image_url']) && $data['image_url'] instanceof \Illuminate\Http\UploadedFile) {
                $year = now()->format('Y');
                $month = now()->format('m');
                $slug = Str::slug($data['title'] ?? 'slider');
                $path = "sliders_images/{$year}/{$month}";
                $filename = uniqid($slug . '-') . '.' . $data['image_url']->getClientOriginalExtension();

                // Lưu ảnh vào storage
                $fullPath = $data['image_url']->storeAs($path, $filename, 'public');
                $data['image_url'] = $fullPath; // lưu path tương đối trong DB
            } else {
                unset($data['image_url']);
            }
            // Gán combo_id nếu có
            if (empty($data['combo_id'])) {
                unset($data['combo_id']); // Không ghi đè nếu không truyền
            }
            // Tạo mới slider
            $slider = Slider::create($data);
            // Load combo nếu có
            return $slider->load('combo');
        } catch (QueryException $e) {
            Log::error('Error creating product: ' . $e->getMessage());
            throw $e;
        } catch (Exception $e) {
            Log::error('Unexpected error creating product: ' . $e->getMessage());
            throw $e;
        }
    }

    public function updateSlider(int $id, array $data)
    {
        try {
            $slider = Slider::findOrFail($id);

            // Xử lý upload ảnh nếu có file ảnh mới truyền lên
            if (isset($data['image_url']) && $data['image_url'] instanceof \Illuminate\Http\UploadedFile) {
                // Xóa ảnh cũ nếu tồn tại
                if ($slider->image_url && Storage::disk('public')->exists($slider->image_url)) {
                    Storage::disk('public')->delete($slider->image_url);
                }
                $year = now()->format('Y');
                $month = now()->format('m');
                $slug = \Illuminate\Support\Str::slug($data['title'] ?? 'slider');
                $path = "sliders_images/{$year}/{$month}";
                $filename = uniqid($slug . '-') . '.' . $data['image_url']->getClientOriginalExtension();
                $fullPath = $data['image_url']->storeAs($path, $filename, 'public');
                $data['image_url'] = $fullPath;
            } else {
                // Nếu không có file mới, loại bỏ key để không ghi đè
                unset($data['image_url']);
            }
            // Gán combo_id nếu có
            if (empty($data['combo_id'])) {
                unset($data['combo_id']); // Không ghi đè nếu không truyền
            }

        // Cập nhật dữ liệu
        $slider->update($data);
        // Load combo chi tiết sau khi update
        return $slider->load('combo');

        } catch (QueryException $e) {
            Log::error('Lỗi khi cập nhật slider: ' . $e->getMessage());
            throw $e;
        } catch (Exception $e) {
            Log::error('Lỗi không xác định khi cập nhật slider: ' . $e->getMessage());
            throw $e;
        }
    }
    public function delete(int $id)
    {
        try {
            $slider = Slider::findOrFail($id);

            // Xóa ảnh nếu có
            if ($slider->image_url && Storage::disk('public')->exists($slider->image_url)) {
                Storage::disk('public')->delete($slider->image_url);
            }

            return $slider->delete();
        } catch (ModelNotFoundException $e) {
            Log::warning("Slider ID {$id} không tồn tại.");
            return false;
        } catch (\Exception $e) {
            Log::error("Lỗi khi xóa slider ID {$id}: " . $e->getMessage());
            throw $e;
        }
    }
    public function deleteMultiple($ids)
    {
         $ids = array_map('intval', explode(',', $ids));
         $sliders = Slider::whereIn('id', $ids)->get();

            $existingIds = Slider::whereIn('id', $ids)->pluck('id')->toArray();
            $nonExistingIds = array_diff($ids, $existingIds);

            if (!empty($nonExistingIds)) {
                Log::error('IDs not found for deletion: ' . implode(',', $nonExistingIds));
                throw new ModelNotFoundException('ID cần xóa không tồn tại trong hệ thống');
            }
        $deletedCount = 0;
        $sliders = Slider::whereIn('id', $ids)->get();

        foreach ($sliders as $slider) {
            try {
                // Xoá ảnh nếu có
                if ($slider->image_url && Storage::disk('public')->exists($slider->image_url)) {
                    Storage::disk('public')->delete($slider->image_url);
                }

                $slider->delete();
                $deletedCount++;
            } catch (\Exception $e) {
                Log::error("Lỗi khi xóa slider ID {$slider->id}: " . $e->getMessage());
            }
        }
        return $deletedCount;
    }
}
