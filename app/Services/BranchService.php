<?php

namespace App\Services;
use App\Models\Branch;
use App\Models\BranchProductStock;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\ModelNotFoundException;

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

            $query = Branch::query();
            // Lọc theo ID chi nhánh (từ select box)
            if ($request->has('branch_id')) {
                $query->where('id', $request->branch_id);
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
        return Branch::with('products')->findOrFail($id);
    }

    /**
     * Tạo mới một chi nhánh.
     */
    public function createBranch(array $data): Branch
    {
        try {
            return Branch::create($data);
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
            $branch = $this->getBranchById($id);
            $branch->update($data);
            return $branch;
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
                $branch = Branch::with('products')->findOrFail($id);
                
                // Kiểm tra tồn kho trước khi xóa
                if ($branch->products->isNotEmpty()) {
                    throw new Exception('Không thể xóa chi nhánh có tồn kho sản phẩm.');
                }

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