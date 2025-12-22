<?php

namespace App\Services;

use App\Models\Combo;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use App\Models\Branch;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Arr;
use Illuminate\Http\UploadedFile;
use Illuminate\Http\Request;
use Exception;
use App\Services\BranchAccessService;

class ComboService
{
    protected $imageService;

    public function __construct(ImageService $imageService)
    {
        $this->imageService = $imageService;
    }

    /**
     * Lấy danh sách combo với bộ lọc và phân trang.
     */
    public function getAllCombos(Request $request): array
    {
        try {
            $perPage = max(1, min(100, (int) $request->input('limit', 10)));
            $currentPage = max(1, (int) $request->input('page', 1));

            // Load các quan hệ cần thiết ngay từ đầu
            $query = Combo::query()->with([
                'image',
                'branches',
                'items.featuredImage',
                'items.attributes.values' 
                
            ]);

            // Tìm kiếm theo tên hoặc mã combo
            if ($request->filled('keyword')) {
                $keyword = $request->input('keyword');
                $query->where(function ($q) use ($keyword) {
                    $q->where('name', 'like', "%{$keyword}%")
                        ->orWhere('combo_code', 'like', "%{$keyword}%");
                });
            }

            // Apply branch filter tự động (theo role)
            // Chỉ lấy combos áp dụng cho branches mà user có quyền
            $branchIds = BranchAccessService::getAccessibleBranchIds();
            if (!empty($branchIds)) {
                $query->where(function ($q) use ($branchIds) {
                    $q->where('applies_to_all_branches', true) // Combos áp dụng cho tất cả branches
                        ->orWhereHas('branches', function ($subQuery) use ($branchIds) {
                            // Hoặc combo được gán cho các branches này VÀ đang active tại branch đó
                            $subQuery->whereIn('branches.id', $branchIds)
                                ->where('branch_combo.is_active', true);
                        });
                });
            } else {
                // Nếu user không có quyền với branch nào, chỉ lấy combos áp dụng cho tất cả
                $query->where('applies_to_all_branches', true);
            }
            
            // Mặc định chỉ lấy combos active (hoặc theo filter is_active từ request nếu có)
            $isActive = $request->filled('is_active') ? $request->boolean('is_active') : true;
            $query->where('is_active', $isActive);
            
            // Nếu user chọn filter branch_id cụ thể (và user có quyền với branch đó)
            if ($request->filled('branch_id')) {
                $branchId = $request->input('branch_id');
                if (BranchAccessService::hasAccessToBranch($branchId)) {
                $query->where(function ($q) use ($branchId) {
                        $q->where('applies_to_all_branches', true)
                        ->orWhereHas('branches', function ($subQuery) use ($branchId) {
                                $subQuery->where('branches.id', $branchId)
                                    ->where('branch_combo.is_active', true);
                        });
                });
                }
            }


            // Lọc theo khoảng ngày áp dụng (start_date và end_date)
            if ($request->filled('start_date')) {
                $query->whereDate('start_date', '>=', $request->input('start_date'));
            }
            if ($request->filled('end_date')) {
                $query->whereDate('end_date', '<=', $request->input('end_date'));
            }

            $query->orderBy('created_at', 'desc');

            $total = $query->count();
            $offset = ($currentPage - 1) * $perPage;
            // Load items.featuredImage để hiển thị ảnh sản phẩm con
            $combos = $query->skip($offset)->take($perPage)->get(); 

            return [
                'data' => $combos,
                'page' => $currentPage,
                'total' => $total,
                'last_page' => (int) ceil($total / $perPage),
                'next_page' => $currentPage < (int) ceil($total / $perPage) ? $currentPage + 1 : null,
                'prev_page' => $currentPage > 1 ? $currentPage - 1 : null,
            ];
        } catch (Exception $e) {
            Log::error('Lỗi khi lấy danh sách combo: ' . $e->getMessage(), [
                'request' => $request->all()
            ]);
            throw $e;
        }
    }


    /**
     * Lấy chi tiết một combo.
     */
    public function getComboById(string $id): Combo // Sửa kiểu $id
    {
        try {
            // Load items.featuredImage và image (ảnh của combo)
            return Combo::with([
                'items.featuredImage',
                'items.attributes.values',
                'branches',
                'image',
                'timeSlots'
            ])->findOrFail($id);
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
        return DB::transaction(function () use ($data) {
            try {
                if (empty($data['combo_code'])) {
                    $data['combo_code'] = $this->_generateUniqueComboCode();
                }

                $itemsData = Arr::pull($data, 'items', []); 
                $branchIds = Arr::pull($data, 'branch_ids', []);
                $imageFile = Arr::pull($data, 'image_url', null);

                $isFlexibleTime = Arr::pull($data, 'is_flexible_time', true);
                $timeSlotIds = Arr::pull($data, 'time_slot_ids', []);
                $data['is_flexible_time'] = $isFlexibleTime;

                $combo = Combo::create($data);


                if (!empty($itemsData)) {
                    $syncData = [];
                    foreach ($itemsData as $item) {
                        $syncData[$item['product_id']] = ['quantity' => $item['quantity']];
                    }
                    $combo->items()->sync($syncData);
                }

                // Đồng bộ chi nhánh
                if ($combo->applies_to_all_branches) {
                    $combo->branches()->sync([]); 
                } else {
                    $combo->branches()->sync($branchIds); 
                }

                if (!$isFlexibleTime) {
                    $combo->timeSlots()->sync($timeSlotIds);
                }

                // Xử lý ảnh
                if ($imageFile instanceof UploadedFile) { 
                    $path = $this->imageService->store($imageFile, 'combos', $combo->name);
                    if ($path) {
                        $combo->image()->create([
                            'image_url' => $path,
                            'is_featured' => true 
                        ]);
                    } else {
                         throw new Exception("Không thể lưu file ảnh cho combo.");
                    }
                }

                Log::info("Đã tạo combo mới [ID: {$combo->id}, Code: {$combo->combo_code}]");
                return $combo->load(['items.featuredImage', 'items.attributes.values', 'branches', 'image','timeSlots']);

            } catch (Exception $e) {
                Log::error('Lỗi khi tạo combo: ' . $e->getMessage(), ['data' => Arr::except($data, ['image_url', 'items'])]); 
                throw $e; 
            }
        });
    }
     /**
     * Cập nhật một combo đã có. (SỬA LẠI HOÀN TOÀN)
     */
    public function updateCombo(string $id, array $data): Combo 
    {
         return DB::transaction(function () use ($id, $data) {
            try {
                $combo = Combo::findOrFail($id); 
                $hasImageUrl = array_key_exists('image_url', $data);
                
                $itemsData = Arr::pull($data, 'items', null); 
                $branchIds = Arr::pull($data, 'branch_ids', null); 
                $imageFile = Arr::pull($data, 'image_url'); 

                $timeLogicHasBeenSent = Arr::has($data, 'is_flexible_time') || Arr::has($data, 'time_slot_ids');
                $isFlexibleTime = $data['is_flexible_time'] ?? $combo->is_flexible_time;
                $timeSlotIds = Arr::pull($data, 'time_slot_ids', null);

                $combo->update($data);

                // Xử lý cập nhật ảnh với logic kiểm tra key
                $this->_handleImageUpdate($combo, $imageFile, $hasImageUrl);

                if ($itemsData !== null) {
                    $syncData = [];
                    foreach ($itemsData as $item) {
                        $syncData[$item['product_id']] = ['quantity' => $item['quantity']];
                    }
                    $combo->items()->sync($syncData);
                }

                 if ($combo->wasChanged('applies_to_all_branches') || $branchIds !== null) {
                    if ($combo->applies_to_all_branches) {
                        $combo->branches()->sync([]);
                    } elseif ($branchIds !== null) { 
                        $combo->branches()->sync($branchIds);
                    }
                }
                if ($timeLogicHasBeenSent) {
                    if ($isFlexibleTime === true) {
                        $combo->timeSlots()->sync([]);
                    } else {
                        $combo->timeSlots()->sync($timeSlotIds ?? []);
                    }
                }

                Log::info("Đã cập nhật combo [ID: {$combo->id}, Code: {$combo->combo_code}]");
                
                return $combo->refresh()->load(['items.featuredImage', 'items.attributes.values', 'branches', 'image','timeSlots']);

            } catch (Exception $e) {
                Log::error("Lỗi khi cập nhật combo ID {$id}: " . $e->getMessage(), ['data' => Arr::except($data, ['image_url', 'items'])]);
                throw $e;
            }
        });
    }

    /**
     * Xử lý cập nhật ảnh duy nhất cho combo (Sửa lại)
     */
    private function _handleImageUpdate(Combo $combo, $imageFile, bool $hasImageUrl): void
    {
        // Có file ảnh mới được upload
        if ($imageFile instanceof UploadedFile) {
            $path = $this->imageService->store($imageFile, 'combos', $combo->name);
            if (!$path) {
                throw new Exception("Không thể upload ảnh mới.");
            }
            if ($combo->image) {
                $this->imageService->delete($combo->image->image_url, 'combos');
                $combo->image->delete(); 
            }
            $combo->image()->create([
                'image_url' => $path,
                'is_featured' => true
            ]);
        }
        elseif ($hasImageUrl && $imageFile === null) { 
             if ($combo->image) {
                $this->imageService->delete($combo->image->image_url, 'combos');
                $combo->image->delete();
            }
        }
    }
        /**
     * Xóa một combo.
     */
    public function deleteCombo(string $id): bool 
    {
         return DB::transaction(function () use ($id) {
            try {
                $combo = Combo::with(['image', 'orderDetails'])->findOrFail($id);

                if ($combo->orderDetails()->exists()) { 
                    throw new Exception("Không thể xóa combo '{$combo->name}' vì đã phát sinh trong đơn hàng.");
                }
                if ($combo->image) {
                    $this->imageService->delete($combo->image->image_url, 'combos');
                    $combo->image()->delete(); 
                }
                $combo->timeSlots()->detach();
                $combo->branches()->detach();
                $combo->items()->detach();


                $isDeleted = $combo->delete();

                if ($isDeleted) {
                    Log::warning("Đã xóa combo [ID: {$id}, Code: {$combo->combo_code}]");
                }
                return $isDeleted;

            } catch (ModelNotFoundException $e) {
                throw $e; 
            } catch (Exception $e) {
                Log::error("Lỗi khi xóa combo ID {$id}: " . $e->getMessage());
                throw $e; 
            }
        });
    }

    /**
     * Xóa nhiều combo.
     */
    public function multiDeleteCombos(array $ids): int 
    {
        $usedInOrders = DB::table('order_details')->whereIn('combo_id', $ids)->exists();
        if ($usedInOrders) {
            throw new Exception("Một hoặc nhiều combo đã phát sinh đơn hàng và không thể xóa.");
        }

        DB::table('item_time_slots')->whereIn('combo_id', $ids)->whereNull('product_id')->delete();
        $deletedCount = 0;
        foreach ($ids as $id) {
            try {
                if ($this->deleteCombo((string)$id)) { 
                    $deletedCount++;
                }
            } catch (Exception $e) {
                 Log::error("Lỗi khi xóa nhiều combo - ID: {$id}: " . $e->getMessage());
            }
        }
        return $deletedCount;
    }
    /**
     * Hàm nội bộ để sinh mã combo duy nhất.
     */
    private function _generateUniqueComboCode(): string
    {
        $prefix = 'CB';
        // Tìm combo có mã lớn nhất
        $lastCombo = Combo::where('combo_code', 'LIKE', "{$prefix}%")
            ->orderByRaw('CAST(SUBSTRING(combo_code, ' . (strlen($prefix) + 1) . ') AS UNSIGNED) DESC')
            ->first();
        $nextId = $lastCombo ? ((int)substr($lastCombo->combo_code, strlen($prefix)) + 1) : 1;
        return $prefix . str_pad($nextId, 6, '0', STR_PAD_LEFT);
    }
}
