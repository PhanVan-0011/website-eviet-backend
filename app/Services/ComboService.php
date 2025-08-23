<?php

namespace App\Services;

use App\Models\Combo;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Arr;
use Illuminate\Http\UploadedFile;
use Exception;

class ComboService
{
    protected $imageService;

    public function __construct(ImageService $imageService)
    {
        $this->imageService = $imageService;
    }

    public function getAllCombos($request): array
    {
        try {
            $perPage = max(1, min(100, (int) $request->input('limit', 10)));
            $currentPage = max(1, (int) $request->input('page', 1));
            $keyword = trim((string) $request->input('keyword', ''));
            $isActive = $request->input('is_active');

            $minPrice = $request->input('min_price');
            $maxPrice = $request->input('max_price');

            //$query = Combo::query();
            $query = Combo::query()->with(['image', 'items']);

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
            if ($request->filled('start_date')) {
                $query->whereDate('start_date', '>=', $request->input('start_date'));
            }
            if ($request->filled('end_date')) {
                $query->whereDate('end_date', '<=', $request->input('end_date'));
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
        try {
            return Combo::with(['items.product', 'image'])->findOrFail($id);
        } catch (ModelNotFoundException $e) {
            throw $e;
        } catch (Exception $e) {
            Log::error("Lỗi khi lấy combo ID {$id}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Tạo một combo mới.
     */
    public function createCombo(array $data): Combo
    {
        try {
            return DB::transaction(function () use ($data) {
                // SỬA LẠI Ở ĐÂY: Xử lý file đơn lẻ
                $imageFile = Arr::pull($data, 'image_url');
                $items = Arr::pull($data, 'items', []);

                $combo = Combo::create($data);

                if ($imageFile instanceof UploadedFile) {
                    $pathData = $this->imageService->store($imageFile, 'combos', $combo->name);
                    if (!$pathData) {
                        throw new Exception("Không thể lưu file ảnh cho combo.");
                    }
                    $combo->image()->create([
                        'image_url' => $pathData,
                        'is_featured' => true
                    ]);
                }

                if (!empty($items)) {
                    $combo->items()->createMany($items);
                }
                return $combo->load(['items.product', 'image']);
            });
        } catch (Exception $e) {
            Log::error('Lỗi khi tạo combo: ' . $e->getMessage(), ['data' => Arr::except($data, 'image_url')]);
            throw new Exception('Không thể tạo combo. Vui lòng thử lại.');
        }
    }
    /**
     * Cập nhật một combo đã có.
     */
    public function updateCombo(int $id, array $data): Combo
    {
        $combo = $this->getComboById($id);

        try {
            return DB::transaction(function () use ($combo, $data) {
                // Lưu thông tin về việc có gửi image_url hay không trước khi pull
                $hasImageUrl = array_key_exists('image_url', $data);
                $imageFile = Arr::pull($data, 'image_url');
                $items = Arr::pull($data, 'items');

                $combo->update($data);

                // Xử lý cập nhật ảnh
                $this->handleImageUpdate($combo, $imageFile, $hasImageUrl);

                // Xử lý cập nhật items
                if (!is_null($items)) {
                    $combo->items()->delete();
                    if (!empty($items)) {
                        $combo->items()->createMany($items);
                    }
                }

                $combo->load(['items.product', 'image']);
                return $combo;
            });
        } catch (Exception $e) {
            Log::error("Lỗi khi cập nhật combo ID {$id}: " . $e->getMessage(), ['data' => Arr::except($data, 'image_url')]);
            throw new Exception("Không thể cập nhật combo. Vui lòng thử lại.");
        }
    }

    /**
     * Xử lý cập nhật ảnh cho combo
     */
    private function handleImageUpdate(Combo $combo, $imageFile, bool $hasImageUrl): void
    {
        // Trường hợp 1: Có file ảnh mới được upload
        if ($imageFile instanceof UploadedFile) {
            $imageSlug = $combo->name;

            // Upload ảnh mới trước
            $pathData = $this->imageService->store($imageFile, 'combos', $imageSlug);
            if (!$pathData) {
                throw new Exception("Không thể upload ảnh mới. Vui lòng thử lại.");
            }

            // Xóa ảnh cũ sau khi upload thành công
            if ($combo->image) {
                $this->imageService->delete($combo->image->image_url, 'combos');
                $combo->image->delete();
            }

            // Tạo record ảnh mới
            $combo->image()->create([
                'image_url' => $pathData,
                'is_featured' => true
            ]);
        }
        // Trường hợp 2: Yêu cầu xóa ảnh hiện tại (gửi null hoặc empty string)
        elseif ($hasImageUrl && (is_null($imageFile) || $imageFile === '')) {
            if ($combo->image) {
                $this->imageService->delete($combo->image->image_url, 'combos');
                $combo->image->delete();
            }
        }
        // Trường hợp 3: Không có thay đổi ảnh (không gửi image_url) - không làm gì
    }
    /**
     * Xóa một combo dựa trên một chuỗi các ID.
     */
    public function deleteCombo(int $id): bool
    {
        try {
            $combo = $this->getComboById($id);

            if ($combo->orderDetails()->exists()) {
                throw new Exception("Không thể xóa combo {$combo->name} vì đã phát sinh đơn hàng.");
            }

            return DB::transaction(function () use ($combo) {
                if ($combo->image) {
                    $this->imageService->delete($combo->image->image_url, 'combos');
                    $combo->image->delete();
                }

                $combo->items()->delete();
                return $combo->delete();
            });
        } catch (ModelNotFoundException $e) {
            throw $e;
        } catch (Exception $e) {
            Log::error("Lỗi khi xóa combo ID {$id}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Xóa nhiều combo dựa trên một chuỗi các ID.
     */
    public function deleteMultiple(string $ids): int
    {
        $idArray = array_filter(array_map('intval', explode(',', $ids)));

        if (empty($idArray)) {
            return 0;
        }
        $combosToDelete = Combo::with('image')->whereIn('id', $idArray)->get();

        if (count($combosToDelete) !== count($idArray)) {
            $foundIds = $combosToDelete->pluck('id')->all();
            $missingIds = array_diff($idArray, $foundIds);
            throw new ModelNotFoundException('Một hoặc nhiều combo không tồn tại: ' . implode(', ', $missingIds));
        }
        $combosWithOrders = $combosToDelete->filter(function ($combo) {
            return $combo->orderDetails()->exists();
        });

        if ($combosWithOrders->isNotEmpty()) {
            $names = $combosWithOrders->pluck('name')->implode(', ');
            throw new Exception("Không thể xóa các combo sau vì đã phát sinh đơn hàng: {$names}.");
        }

        $deletedCount = 0;
        DB::transaction(function () use ($combosToDelete, &$deletedCount) {
            foreach ($combosToDelete as $combo) {
                if ($combo->image) {
                    $this->imageService->delete($combo->image->image_url, 'combos');
                    $combo->image->delete();
                }

                $combo->items()->delete();
                if ($combo->delete()) {
                    $deletedCount++;
                }
            }
        });

        return $deletedCount;
    }
}
