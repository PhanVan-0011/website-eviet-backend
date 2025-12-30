<?php

namespace App\Services;
use App\Models\PickupLocation;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Services\BranchAccessService;

class PickupLocationService
{
   /**
     * Lấy danh sách điểm nhận hàng (Phân trang thủ công, Lọc, Tìm kiếm)
     */
    public function getAllLocations($request)
    {
        try {
            $perPage = max(1, min(100, (int) $request->input('limit', 10)));
            $currentPage = max(1, (int) $request->input('page', 1));

            $query = PickupLocation::query()->with('branch');

            // Apply branch filter (tự động theo role)
            BranchAccessService::applyBranchFilter($query);

            // Lọc theo chi nhánh (nếu user có quyền)
            if ($request->filled('branch_id')) {
                $branchId = $request->input('branch_id');
                if (BranchAccessService::hasAccessToBranch($branchId)) {
                    $query->where('branch_id', $branchId);
                }
            }

            // Tìm kiếm theo tên
            if ($request->filled('keyword')) {
                $keyword = $request->input('keyword');
                $query->where('name', 'like', '%' . $keyword . '%');
            }

            // Lọc theo trạng thái
            if ($request->filled('is_active')) {
                $query->where('is_active', $request->boolean('is_active'));
            }

            // Sắp xếp: Chi nhánh trước, tên sau
            $query->orderBy('created_at', 'desc');
            // 3. XỬ LÝ PHÂN TRANG THỦ CÔNG
            $total = $query->count();
            $offset = ($currentPage - 1) * $perPage;
            
            $locations = $query->skip($offset)->take($perPage)->get();

            // Tính toán metadata
            $lastPage = (int) ceil($total / $perPage);
            $nextPage = $currentPage < $lastPage ? $currentPage + 1 : null;
            $prevPage = $currentPage > 1 ? $currentPage - 1 : null;

            return [
                'data' => $locations,
                'page' => $currentPage,
                'total' => $total,
                'last_page' => $lastPage,
                'next_page' => $nextPage,
                'pre_page' => $prevPage,
            ];
        } catch (Exception $e) {
            Log::error('Lỗi PickupLocationService::getAllLocations: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Lấy chi tiết theo ID
     */
    public function getPickupLocationById(int $id)
    {
        try {
            return PickupLocation::with('branch')->findOrFail($id);
        } catch (ModelNotFoundException $e) {
            Log::warning("Không tìm thấy điểm nhận hàng ID: {$id}");
            throw $e;
        }
    }

   /**
     * Tạo mới
     */
    public function createLocation(array $data)
    {
        try {
            $location = PickupLocation::create($data);
            // Load quan hệ branch để trả về Resource đầy đủ ngay lập tức
            return $location->load('branch');
        } catch (Exception $e) {
            Log::error('Lỗi khi tạo điểm nhận hàng: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Cập nhật
     */
    public function updateLocation(int $id, array $data)
    {
        try {
            $location = $this->getPickupLocationById($id);
            $location->update($data);
            return $location;
        } catch (Exception $e) {
            Log::error("Lỗi khi cập nhật điểm nhận hàng ID {$id}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Xóa một điểm nhận hàng (CÓ KIỂM TRA ĐƠN HÀNG TỒN TẠI)
     */
    public function deleteLocation(int $id)
    {
        $location = $this->getPickupLocationById($id);
        
        return DB::transaction(function () use ($location) {
            try {
                // Kiểm tra ràng buộc: Nếu đã có đơn hàng sử dụng điểm này thì KHÔNG ĐƯỢC XÓA
                // Lưu ý: Cần đảm bảo Model PickupLocation có relation orders()
                if ($location->orders()->exists()) {
                    throw new Exception("Không thể xóa điểm nhận hàng '{$location->name}' vì đã có đơn hàng sử dụng.");
                }

                $isDeleted = $location->delete();
                
                if ($isDeleted) {
                    Log::info("Đã xóa điểm nhận hàng ID: {$location->id}");
                }
                
                return $isDeleted;
            } catch (Exception $e) {
                Log::error("Lỗi khi xóa điểm nhận hàng ID {$location->id}: " . $e->getMessage());
                throw $e;
            }
        });
    }

    /**
     * Xóa nhiều điểm nhận hàng (CÓ KIỂM TRA ĐƠN HÀNG)
     */
    public function multiDeleteLocations(array $ids): int
    {
        return DB::transaction(function () use ($ids) {
            try {
                $locationsToDelete = PickupLocation::whereIn('id', $ids)->get();
                
                // 1. Kiểm tra trước xem có điểm nào đang dính đơn hàng không
                $lockedLocations = [];
                foreach ($locationsToDelete as $location) {
                    if ($location->orders()->exists()) {
                        $lockedLocations[] = $location->name;
                    }
                }

                // Nếu có bất kỳ điểm nào bị khóa, ném lỗi và không xóa gì cả
                if (!empty($lockedLocations)) {
                    $names = implode(', ', $lockedLocations);
                    throw new Exception("Không thể xóa các điểm nhận hàng sau vì đang có đơn hàng sử dụng: {$names}");
                }

                // 2. Nếu an toàn, tiến hành xóa
                $deletedCount = 0;
                foreach ($locationsToDelete as $location) {
                    if ($location->delete()) {
                        $deletedCount++;
                    }
                }

                if ($deletedCount > 0) {
                    Log::info("Đã xóa {$deletedCount} điểm nhận hàng.");
                }
                
                return $deletedCount;
            } catch (Exception $e) {
                Log::error('Lỗi khi xóa nhiều điểm nhận hàng: ' . $e->getMessage());
                throw $e;
            }
        });
    }
}