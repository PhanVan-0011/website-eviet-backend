<?php

namespace App\Services;
use App\Models\Supplier;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

class SupplierService
{ 
    /**
     * Lấy danh sách tất cả nhà cung cấp với các bộ lọc và phân trang.
     */
    public function getAllSuppliers($request)
    {
        try {
            $perPage = max(1, min(100, (int) $request->input('limit', 10)));
            $currentPage = max(1, (int) $request->input('page', 1));
            $query = Supplier::query();

            if ($request->has('active')) {
                $query->where('is_active', $request->active);
            }

            if ($request->has('keyword')) {
                $keyword = $request->keyword;
                $query->where(function ($q) use ($keyword) {
                    $q->where('name', 'like', "%{$keyword}%")
                      ->orWhere('code', 'like', "%{$keyword}%");
                });
            }

            $query->with('group', 'user');
            $total = $query->count();
            $offset = ($currentPage - 1) * $perPage;
            $suppliers = $query->skip($offset)->take($perPage)->get();

            $lastPage = (int) ceil($total / $perPage);
            $nextPage = $currentPage < $lastPage ? $currentPage + 1 : null;
            $prevPage = $currentPage > 1 ? $currentPage - 1 : null;

            return [
                'data' => $suppliers,
                'page' => $currentPage,
                'total' => $total,
                'last_page' => $lastPage,
                'next_page' => $nextPage,
                'pre_page' => $prevPage,
            ];
        } catch (Exception $e) {
            Log::error('Lỗi khi lấy danh sách nhà cung cấp: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Lấy một nhà cung cấp theo ID.
     */
    public function getSupplierById(string $id): Supplier
    {
        return Supplier::with('group', 'user')->findOrFail($id);
    }

    /**
     * Tạo mới một nhà cung cấp.
     */
    public function createSupplier(array $data): Supplier
    {
        try {
            return Supplier::create($data);
        } catch (Exception $e) {
            Log::error('Lỗi khi tạo nhà cung cấp: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Cập nhật thông tin nhà cung cấp.
     */
    public function updateSupplier(string $id, array $data): Supplier
    {
        try {
            $supplier = $this->getSupplierById($id);
            $supplier->update($data);
            return $supplier;
        } catch (ModelNotFoundException $e) {
            throw $e;
        } catch (Exception $e) {
            Log::error("Lỗi khi cập nhật nhà cung cấp (ID: {$id}): " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Xóa một nhà cung cấp.
     */
    public function deleteSupplier(string $id): bool
    {
        try {
            return DB::transaction(function () use ($id) {
                $supplier = Supplier::with('purchaseInvoices')->findOrFail($id);
                if ($supplier->purchaseInvoices->isNotEmpty()) {
                    throw new Exception("Không thể xóa nhà cung cấp '{$supplier->name}' vì đang có hóa đơn nhập hàng.");
                }
                return $supplier->delete();
            });
        } catch (ModelNotFoundException $e) {
            throw $e;
        } catch (Exception $e) {
            Log::error("Lỗi khi xóa nhà cung cấp (ID: {$id}): " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Xóa nhiều nhà cung cấp cùng lúc.
     *
     * @param array $ids Danh sách ID nhà cung cấp
     * @return int Số lượng bản ghi đã xóa
     */
    public function multiDelete(array $ids): int
    {
        try {
            return DB::transaction(function () use ($ids) {
                $suppliersToDelete = Supplier::whereIn('id', $ids)
                    ->with('purchaseInvoices')
                    ->get();
                
                $errors = [];
                foreach ($suppliersToDelete as $supplier) {
                    if ($supplier->purchaseInvoices->isNotEmpty()) {
                        $errors[] = "Không thể xóa nhà cung cấp '{$supplier->name}' vì đang có hóa đơn nhập hàng.";
                    }
                }
                if (!empty($errors)) {
                    throw new Exception(implode(' ', $errors));
                }

                $count = 0;
                foreach ($suppliersToDelete as $supplier) {
                    $supplier->delete();
                    $count++;
                }

                return $count;
            });
        } catch (Exception $e) {
            Log::error('Lỗi khi xóa nhiều nhà cung cấp: ' . $e->getMessage());
            throw $e;
        }
    }
}