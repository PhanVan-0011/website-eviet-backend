<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\BranchProductStock;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Arr;
use App\Services\BranchAccessService;

class BranchService
{
    /**
     * Lấy danh sách tất cả chi nhánh với phân trang thủ công, tìm kiếm và sắp xếp.
     */
    public function getAllBranches($request)
    {
        try {
            // Chuẩn hóa các tham số đầu vào
            $perPage = max(1, min(100, (int) $request->input('limit', 10)));
            $currentPage = max(1, (int) $request->input('page', 1));
            $keyword = (string) $request->input('keyword', '');

            // Lấy thông tin user hiện tại
            $user = auth()->user();

            // Xác định is_admin: Sử dụng BranchAccessService để nhất quán với config
            $isAdmin = BranchAccessService::canViewAllBranches($user);

            $query = Branch::query();

            // Áp dụng filter theo quyền truy cập của user (đa chi nhánh cho branch-admin)
            // Nếu user có quyền xem tất cả branches, không cần filter
            if (!$isAdmin) {
                $accessibleBranchIds = BranchAccessService::getAccessibleBranchIds($user);
                
                if (!empty($accessibleBranchIds)) {
                    // User có quyền với một số branches cụ thể
                    $query->whereIn('id', $accessibleBranchIds);
                    
                    // Lọc theo ID chi nhánh (từ select box) - chỉ áp dụng nếu user có quyền với branch đó
                    if ($request->has('branch_id')) {
                        $branchId = (int) $request->input('branch_id');
                        if (in_array($branchId, $accessibleBranchIds)) {
                            $query->where('id', $branchId);
                        }
                    }
                } else {
                    // User không có quyền với branch nào (không nên xảy ra, nhưng để an toàn)
                    $query->whereRaw('1 = 0');
                }
            } else {
                // User có quyền xem tất cả branches, vẫn có thể filter theo branch_id nếu cần
                if ($request->has('branch_id')) {
                    $branchId = (int) $request->input('branch_id');
                    $query->where('id', $branchId);
                }
            }

            // Lọc theo trạng thái hoạt động
            if ($request->has('active')) {
                $query->where('active', $request->active);
            }

            // Lọc theo từ khóa (tên hoặc mã)
            if (!empty($keyword)) {
                $query->where(function ($q) use ($keyword) {
                    $q->where('name', 'like', "%{$keyword}%")
                        ->orWhere('code', 'like', "%{$keyword}%");
                });
            }

            // Lọc theo ngày tạo
            if (!empty($request->input('start_date'))) {
                $query->whereDate('created_at', '>=', $request->input('start_date'));
            }

            if (!empty($request->input('end_date'))) {
                $query->whereDate('created_at', '<=', $request->input('end_date'));
            }

            $query->orderBy('id', 'desc');
            $query->with('products');

            $total = $query->count();

            $offset = ($currentPage - 1) * $perPage;
            $branches = $query->skip($offset)->take($perPage)->get();

            $lastPage = (int) ceil($total / $perPage);
            $nextPage = $currentPage < $lastPage ? $currentPage + 1 : null;
            $prevPage = $currentPage > 1 ? $currentPage - 1 : null;

            return [
                'data' => $branches,
                'page' => $currentPage,
                'total' => $total,
                'last_page' => $lastPage,
                'next_page' => $nextPage,
                'pre_page' => $prevPage,
                'is_admin' => $isAdmin, // Thêm field để phân biệt admin
            ];
        } catch (Exception $e) {
            Log::error('Lỗi khi lấy danh sách chi nhánh: ' . $e->getMessage());
            throw $e;
        }
    }
    /**
     * Lấy một chi nhánh theo ID.
     */
    public function getBranchById(string $id): Branch
    {
        return Branch::with('products', 'timeSlots')->findOrFail($id);
    }

    /**
     * Tạo mới một chi nhánh.
     */
    public function createBranch(array $data): Branch
    {
        try {
            $timeSlotIds = Arr::pull($data, 'time_slot_ids', []);

            $branch = Branch::create($data);

            if (!empty($timeSlotIds)) {

                $syncData = array_fill_keys($timeSlotIds, ['is_enabled' => true]);
                $branch->timeSlots()->sync($syncData);
            }

            // Load lại quan hệ để trả về
            return $branch->load('timeSlots');
        } catch (Exception $e) {
            Log::error('Lỗi khi tạo chi nhánh: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Cập nhật thông tin chi nhánh.
     */
    public function updateBranch(string $id, array $data): Branch
    {
        try {
            $timeSlotIds = Arr::pull($data, 'time_slot_ids', null);

            // Dùng getBranchById() để đảm bảo đã load 'timeSlots'
            $branch = $this->getBranchById($id);
            $branch->update($data);

            // Chỉ sync nếu 'time_slot_ids' được gửi lên (kể cả là mảng rỗng [])
            if ($timeSlotIds !== null) {
                $syncData = array_fill_keys($timeSlotIds, ['is_enabled' => true]);
                $branch->timeSlots()->sync($syncData);
            }

            // Dùng refresh() để đảm bảo dữ liệu (kể cả 'timeSlots') là mới nhất
            return $branch->refresh()->load(['products', 'timeSlots']);
        } catch (ModelNotFoundException $e) {
            throw $e;
        } catch (Exception $e) {
            Log::error("Lỗi khi cập nhật chi nhánh (ID: {$id}): " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Xóa một chi nhánh.
     */
    public function deleteBranch(string $id): bool
    {
        try {
            return DB::transaction(function () use ($id) {
                // Dùng getBranchById để load luôn timeSlots
                $branch = $this->getBranchById($id);

                // Kiểm tra tồn kho trước khi xóa
                if ($branch->products->isNotEmpty()) {
                    throw new Exception('Không thể xóa chi nhánh có tồn kho sản phẩm.');
                }
                $branch->timeSlots()->detach();

                return $branch->delete();
            });
        } catch (ModelNotFoundException $e) {
            throw $e;
        } catch (Exception $e) {
            Log::error("Lỗi khi xóa chi nhánh (ID: {$id}): " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Xóa nhiều chi nhánh cùng lúc.
     *
     * @param array $ids Danh sách ID chi nhánh
     * @return int Số lượng bản ghi đã xóa
     */
    public function multiDelete(array $ids): int
    {
        try {
            return DB::transaction(function () use ($ids) {
                $branchesToDelete = Branch::whereIn('id', $ids)
                    ->with('products')
                    ->get();

                $branchesWithProducts = $branchesToDelete->filter(function ($branch) {
                    return $branch->products->isNotEmpty();
                });

                if ($branchesWithProducts->isNotEmpty()) {
                    $errors = $branchesWithProducts->map(function ($branch) {
                        $productCount = $branch->products->count();
                        return "Chi nhánh '{$branch->name}' đang có {$productCount} sản phẩm tồn kho, không thể xóa.";
                    })->all();

                    throw new Exception(implode(' ', $errors));
                }

                DB::table('branch_time_slot_pivot')->whereIn('branch_id', $ids)->delete();

                $count = 0;
                foreach ($branchesToDelete as $branch) {
                    $branch->delete();
                    $count++;
                }

                return $count;
            });
        } catch (Exception $e) {
            Log::error('Lỗi khi xóa nhiều chi nhánh: ' . $e->getMessage());
            throw $e;
        }
    }
}
