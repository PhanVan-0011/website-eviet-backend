<?php

namespace App\Services;
use App\Models\SupplierGroup;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

class SupplierGroupService
{
        /**
     * Lấy danh sách tất cả các nhóm nhà cung cấp với các bộ lọc và phân trang.
     */
    public function getAllSupplierGroups($request)
    {
        try {
            $perPage = max(1, min(100, (int) $request->input('limit', 10)));
            $currentPage = max(1, (int) $request->input('page', 1));
            $query = SupplierGroup::query();

            if ($request->has('keyword')) {
                $keyword = $request->keyword;
                $query->where('name', 'like', "%{$keyword}%");
            }

            $query->withCount('suppliers');
            $total = $query->count();
            $offset = ($currentPage - 1) * $perPage;
            $supplierGroups = $query->skip($offset)->take($perPage)->get();

            $lastPage = (int) ceil($total / $perPage);
            $nextPage = $currentPage < $lastPage ? $currentPage + 1 : null;
            $prevPage = $currentPage > 1 ? $currentPage - 1 : null;

            return [
                'data' => $supplierGroups,
                'page' => $currentPage,
                'total' => $total,
                'last_page' => $lastPage,
                'next_page' => $nextPage,
                'pre_page' => $prevPage,
            ];
        } catch (Exception $e) {
            Log::error('Lỗi khi lấy danh sách nhóm nhà cung cấp: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Lấy một nhóm nhà cung cấp theo ID.
     */
    public function getSupplierGroupById(string $id): SupplierGroup
    {
        return SupplierGroup::withCount('suppliers')->findOrFail($id);
    }

    /**
     * Tạo mới một nhóm nhà cung cấp.
     */
    public function createSupplierGroup(array $data): SupplierGroup
    {
        try {
            return SupplierGroup::create($data);
        } catch (Exception $e) {
            Log::error('Lỗi khi tạo nhóm nhà cung cấp: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Cập nhật thông tin nhóm nhà cung cấp.
     */
    public function updateSupplierGroup(string $id, array $data): SupplierGroup
    {
        try {
            $supplierGroup = $this->getSupplierGroupById($id);
            $supplierGroup->update($data);
            return $supplierGroup;
        } catch (ModelNotFoundException $e) {
            throw $e;
        } catch (Exception $e) {
            Log::error("Lỗi khi cập nhật nhóm nhà cung cấp (ID: {$id}): " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Xóa một nhóm nhà cung cấp.
     */
    public function deleteSupplierGroup(string $id): bool
    {
        try {
            return DB::transaction(function () use ($id) {
                $supplierGroup = SupplierGroup::with('suppliers')->findOrFail($id);
                if ($supplierGroup->suppliers->isNotEmpty()) {
                    throw new Exception("Không thể xóa nhóm nhà cung cấp '{$supplierGroup->name}' vì đang có nhà cung cấp liên kết.");
                }
                return $supplierGroup->delete();
            });
        } catch (ModelNotFoundException $e) {
            throw $e;
        } catch (Exception $e) {
            Log::error("Lỗi khi xóa nhóm nhà cung cấp (ID: {$id}): " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Xóa nhiều nhóm nhà cung cấp cùng lúc.
     *
     * @param array $ids Danh sách ID nhóm nhà cung cấp
     * @return int Số lượng bản ghi đã xóa
     */
    public function multiDelete(array $ids): int
    {
        try {
            return DB::transaction(function () use ($ids) {
                $groupsToDelete = SupplierGroup::whereIn('id', $ids)
                    ->with('suppliers')
                    ->get();
                
                $errors = [];
                foreach ($groupsToDelete as $group) {
                    if ($group->suppliers->isNotEmpty()) {
                        $errors[] = "Không thể xóa nhóm nhà cung cấp '{$group->name}' vì đang có nhà cung cấp liên kết.";
                    }
                }
                if (!empty($errors)) {
                    throw new Exception(implode(' ', $errors));
                }

                $count = 0;
                foreach ($groupsToDelete as $group) {
                    $group->delete();
                    $count++;
                }
                return $count;
            });
        } catch (Exception $e) {
            Log::error('Lỗi khi xóa nhiều nhóm nhà cung cấp: ' . $e->getMessage());
            throw $e;
        }
    }
}