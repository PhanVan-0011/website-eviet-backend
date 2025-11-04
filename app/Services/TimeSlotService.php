<?php

namespace App\Services;
use App\Models\OrderTimeSlot;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Exception;

class TimeSlotService
{
    /**
     * Lấy danh sách tất cả khung giờ (có phân trang thủ công).
     *
     * @param Request $request
     * @return array
     */
    public function getAllTimeSlots(Request $request): array
    {
        try {
            // 1. Lấy các tham số phân trang, giống hệt ProductService
            $perPage = max(1, min(100, (int) $request->input('limit', 10)));
            $currentPage = max(1, (int) $request->input('page', 1));

            $query = OrderTimeSlot::query();

            // 2. Xử lý Lọc / Tìm kiếm
            if ($request->filled('keyword')) {
                $keyword = $request->input('keyword');
                $query->where('name', 'like', "%{$keyword}%");
            }

            if ($request->filled('is_active')) {
                $query->where('is_active', $request->boolean('is_active'));
            }

            // Sắp xếp theo giờ bắt đầu cho hợp lý
            $query->orderBy('start_time', 'asc');

            // 3. Tính toán phân trang thủ công
            $total = $query->count();
            $offset = ($currentPage - 1) * $perPage;
            $timeSlots = $query->skip($offset)->take($perPage)->get();

            $lastPage = (int) ceil($total / $perPage);
            $nextPage = $currentPage < $lastPage ? $currentPage + 1 : null;
            $prevPage = $currentPage > 1 ? $currentPage - 1 : null;

            // 4. Trả về mảng dữ liệu với format pagination của bạn
            return [
                'data' => $timeSlots,
                'page' => $currentPage,
                'total' => $total,
                'last_page' => $lastPage,
                'next_page' => $nextPage,
                'pre_page' => $prevPage, // Giữ đúng key 'pre_page'
            ];
        } catch (Exception $e) {
            Log::error('Lỗi khi lấy danh sách khung giờ: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Tạo mới một khung giờ.
     * (Logic đơn giản vì chỉ tạo 'cha', chưa gán)
     */
    public function createTimeSlot(array $data): OrderTimeSlot
    {
        try {
            // Đảm bảo is_active có giá trị default nếu không được gửi
            if (!isset($data['is_active'])) {
                $data['is_active'] = true;
            }
            
            $timeSlot = OrderTimeSlot::create($data);
            Log::info("Đã tạo khung giờ mới [ID: {$timeSlot->id}]");
            return $timeSlot;
        } catch (Exception $e) {
            Log::error('Lỗi khi tạo khung giờ: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Lấy thông tin chi tiết một khung giờ.
     * (Load quan hệ để xem chi tiết)
     */
    public function getTimeSlotById(int $id): OrderTimeSlot
    {
        try {
            // Load các quan hệ để biết nó đang được gán ở đâu
            return OrderTimeSlot::with(['branches', 'products', 'combos'])->findOrFail($id);
        } catch (ModelNotFoundException $e) {
            Log::error("Khung giờ với ID {$id} không tồn tại.");
            throw $e; // Controller sẽ bắt 404
        } catch (Exception $e) {
            Log::error("Lỗi khi lấy chi tiết khung giờ ID {$id}: " . $e->getMessage());
            throw $e; // Controller sẽ bắt 500
        }
    }

    /**
     * Cập nhật thông tin một khung giờ.
     */
    public function updateTimeSlot(int $id, array $data): OrderTimeSlot
    {
        try {
            $timeSlot = OrderTimeSlot::findOrFail($id);
            $timeSlot->update($data);
            Log::info("Đã cập nhật khung giờ [ID: {$id}]");
            
            // Tải lại dữ liệu và các quan hệ
            return $timeSlot->refresh()->load(['branches', 'products', 'combos']);
        } catch (ModelNotFoundException $e) {
            throw $e;
        } catch (Exception $e) {
            Log::error("Lỗi khi cập nhật khung giờ ID {$id}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Xóa một khung giờ.
     */
    public function deleteTimeSlot(int $id): bool
    {
        return DB::transaction(function () use ($id) {
            try {
                $timeSlot = OrderTimeSlot::findOrFail($id); 

                // **Logic nghiệp vụ quan trọng**: Kiểm tra xem khung giờ có đang được sử dụng không
                if (DB::table('branch_time_slot_pivot')->where('time_slot_id', $id)->exists()) {
                     throw new Exception("Không thể xóa khung giờ '{$timeSlot->name}' vì đang được gán cho chi nhánh.");
                }
                if (DB::table('item_time_slots')->where('time_slot_id', $id)->exists()) {
                   throw new Exception("Không thể xóa khung giờ '{$timeSlot->name}' vì đang được gán cho sản phẩm/combo.");
                }

                $isDeleted = $timeSlot->delete();
                Log::warning("Đã xóa khung giờ [ID: {$id}]");
                return $isDeleted;

            } catch (ModelNotFoundException $e) {
                throw $e; // Lỗi 404
            } catch (Exception $e) {
                Log::error("Lỗi khi xóa khung giờ ID {$id}: " . $e->getMessage());
                throw $e; // Ném lỗi (ví dụ: "Không thể xóa...")
            }
        });
    }

    /**
     * Xóa nhiều khung giờ cùng lúc. (Tối ưu hiệu suất)
     */
    public function multiDeleteTimeSlots(array $ids): int
    {
        // 1. Kiểm tra ràng buộc hàng loạt (Batch Check)
        $inUseBranch = DB::table('branch_time_slot_pivot')->whereIn('time_slot_id', $ids)->exists();
        if ($inUseBranch) {
             throw new Exception("Một hoặc nhiều khung giờ đang được gán cho chi nhánh và không thể xóa.");
        }
        
        $inUseItem = DB::table('item_time_slots')->whereIn('time_slot_id', $ids)->exists();
        if ($inUseItem) {
            throw new Exception("Một hoặc nhiều khung giờ đang được gán cho sản phẩm/combo và không thể xóa.");
        }

        // 2. Xóa hàng loạt (Batch Delete)
        try {
            $deletedCount = OrderTimeSlot::whereIn('id', $ids)->delete();
            Log::warning("Đã xóa {$deletedCount} khung giờ.");
            return $deletedCount;
        } catch (Exception $e) {
            Log::error('Lỗi khi xóa nhiều khung giờ: ' . $e->getMessage());
            throw $e;
        }
    }
}