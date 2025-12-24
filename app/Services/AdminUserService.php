<?php

namespace App\Services;

use App\Models\User;
use App\Models\Role;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Arr;
use Illuminate\Http\UploadedFile;
use App\Services\BranchAccessService;

class AdminUserService
{
    protected ImageService $imageService;

    public function __construct(ImageService $imageService)
    {
        $this->imageService = $imageService;
    }
    /**
     * Lấy danh sách tài khoản quản trị với logic phân trang tùy chỉnh.
     */
    public function getAdminUsers(Request $request): array
    {
        try {
           
            $perPage = max(1, min(100, (int) $request->input('limit', 10)));
            $currentPage = max(1, (int) $request->input('page', 1));

            $query = User::query()
                ->whereHas('roles') 
                ->whereDoesntHave('roles', function (Builder $q) {
                    $q->where('name', 'super-admin');
                })->with(['roles', 'image', 'branch', 'branches']);
            
            // Apply branch filter (tự động theo role)
            // Branch Admin chỉ xem users trong branches được phân công
            BranchAccessService::applyBranchFilter($query, 'branch_id');

            // Lọc
            if ($request->filled('keyword')) { 
                $keyword = $request->input('keyword');
                $query->where(function (Builder $q) use ($keyword) {
                    $q->where('name', 'like', "%{$keyword}%")
                        ->orWhere('email', 'like', "%{$keyword}%")
                        ->orWhere('phone', 'like', "%{$keyword}%");
                });
            }

            if ($request->filled('role_name')) {
                $query->whereHas('roles', function (Builder $q) use ($request) {
                    $q->where('name', $request->input('role_name'));
                });
            }

            if ($request->filled('is_active')) {
                $query->where('is_active', $request->input('is_active') === 'true');
            }

            // Filter theo branch_id: Check cả trong branches relationship và branch_id field
            // Vì user có thể có nhiều branches qua pivot table hoặc 1 branch qua branch_id
            if ($request->filled('branch_id')) {
                $branchId = $request->input('branch_id');
                $query->where(function (Builder $q) use ($branchId) {
                    // Check trong branches relationship (many-to-many)
                    $q->whereHas('branches', function (Builder $subQ) use ($branchId) {
                        $subQ->where('branches.id', $branchId);
                    })
                    // Hoặc check trong branch_id field (cho sales-staff)
                    ->orWhere('branch_id', $branchId);
                });
            }

            // Sắp xếp
            $query->orderBy('created_at', 'desc');

            // Phân trang thủ công
            $total = $query->count();
            $offset = ($currentPage - 1) * $perPage;
            $users = $query->skip($offset)->take($perPage)->get();

            $lastPage = (int) ceil($total / $perPage);
            $nextPage = $currentPage < $lastPage ? $currentPage + 1 : null;
            $prevPage = $currentPage > 1 ? $currentPage - 1 : null;

            // Trả về kết quả với cấu trúc phẳng
            return [
                'data' => $users,
                'page' => $currentPage,
                'total' => $total,
                'last_page' => $lastPage,
                'next_page' => $nextPage,
                'prev_page' => $prevPage,
            ];
        } catch (\Exception $e) {
            Log::error('Lỗi lấy danh sách nhân viên: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Lấy danh sách tài khoản quản trị trong thùng rác với logic phân trang tùy chỉnh.
     */
    public function getTrashedAdminUsers(Request $request): array
    {
        try {
            $perPage = max(1, min(100, (int) $request->input('limit', 10)));
            $currentPage = max(1, (int) $request->input('page', 1));

             $query = User::onlyTrashed()
                ->whereHas('roles')
                ->whereDoesntHave('roles', function (Builder $q) {
                    $q->where('name', 'super-admin');
                })->with(['roles', 'image', 'branch', 'branches']);

            // Apply branch filter
            BranchAccessService::applyBranchFilter($query, 'branch_id');

            if ($request->filled('keyword')) {
                $keyword = $request->input('keyword');
                $query->where(function (Builder $q) use ($keyword) {
                    $q->where('name', 'like', "%{$keyword}%")
                        ->orWhere('email', 'like', "%{$keyword}%")
                        ->orWhere('phone', 'like', "%{$keyword}%");
                });
            }
            if ($request->filled('role_name')) {
                $query->whereHas('roles', function (Builder $q) use ($request) {
                    $q->where('name', $request->input('role_name'));
                });
            }
            if ($request->filled('is_active')) {
                $query->where('is_active', $request->input('is_active') === 'true');
            }

            // Filter theo branch_id: Check cả trong branches relationship và branch_id field
            // Vì user có thể có nhiều branches qua pivot table hoặc 1 branch qua branch_id
            if ($request->filled('branch_id')) {
                $branchId = $request->input('branch_id');
                $query->where(function (Builder $q) use ($branchId) {
                    // Check trong branches relationship (many-to-many)
                    $q->whereHas('branches', function (Builder $subQ) use ($branchId) {
                        $subQ->where('branches.id', $branchId);
                    })
                    // Hoặc check trong branch_id field (cho sales-staff)
                    ->orWhere('branch_id', $branchId);
                });
            }

            $query->orderBy('deleted_at', 'desc');

            $total = $query->count();
            $offset = ($currentPage - 1) * $perPage;
            $users = $query->skip($offset)->take($perPage)->get();

            $lastPage = (int) ceil($total / $perPage);
            $nextPage = $currentPage < $lastPage ? $currentPage + 1 : null;
            $prevPage = $currentPage > 1 ? $currentPage - 1 : null;

            return [
                'data' => $users,
                'page' => $currentPage,
                'total' => $total,
                'last_page' => $lastPage,
                'next_page' => $nextPage,
                'prev_page' => $prevPage,
            ];
        } catch (\Exception $e) {
            Log::error('Lỗi lấy danh sách nhân viên trong thùng rác: ' . $e->getMessage());
            throw $e;
        }
    }
     /**
     * Phục hồi một tài khoản quản trị từ thùng rác.
     */
    public function restoreAdminUser(int $id): User
    {
        try {
            // Tải kèm cả quan hệ 'image' và 'branch', 'branches' để trả về đầy đủ thông tin
            $user = User::onlyTrashed()->with(['roles', 'image', 'branch', 'branches'])->findOrFail($id);
            $user->restore();

            Log::info(
                "Tài khoản quản trị [ID: {$user->id}, Email: {$user->email}] đã được PHỤC HỒI bởi người dùng [ID: " . auth()->id() . "]."
            );

            return $user;
        } catch (ModelNotFoundException $e) {
            Log::warning("Không tìm thấy tài khoản trong thùng rác để phục hồi với ID: {$id}");
            throw $e;
        } catch (\Exception $e) {
            Log::error("Lỗi khi phục hồi tài khoản ID {$id}: " . $e->getMessage());
            throw $e;
        }
    }
    /**
     * Xóa vĩnh viễn một tài khoản quản trị khỏi hệ thống.
     */
    public function forceDeleteAdminUser(int $id): void
    {
        $user = User::onlyTrashed()->with(['image', 'branch', 'branches'])->findOrFail($id);
        if ($user->hasRole('super-admin')) {
            throw new Exception('Không thể xóa vĩnh viễn tài khoản Super Admin.');
        }

        DB::transaction(function() use ($user) {
            // 5. Xóa ảnh và các quan hệ trước khi xóa vĩnh viễn
            if ($image = $user->image) {
                $this->imageService->delete($image->image_url, 'users');
                $image->delete();
            }
            $user->roles()->detach();
            $user->permissions()->detach();
            
            $userId = $user->id;
            $userEmail = $user->email;
            $user->forceDelete();
            
            Log::alert("Tài khoản quản trị [ID: {$userId}, Email: {$userEmail}] đã bị XÓA VĨNH VIỄN bởi người dùng [ID: " . auth()->id() . "].");
        });
    }
    /**
     * Tìm một tài khoản quản trị theo ID.
     * @throws ModelNotFoundException
     */
    public function findAdminUserById(int $id)
    {
        return User::whereHas('roles')->with(['roles', 'permissions', 'image', 'branch', 'branches'])->findOrFail($id);
    }

    /**
     * Tạo một tài khoản quản trị mới.
     */
    public function createAdminUser(array $data)
    {
        return DB::transaction(function () use ($data) {
            $imageFile = Arr::pull($data, 'image_url');
            $roleId = Arr::pull($data, 'role_id');
            // Kiểm tra branch_ids có được gửi lên không (khác với default empty array)
            $branchIds = Arr::has($data, 'branch_ids') ? $data['branch_ids'] : null;
            Arr::forget($data, 'branch_ids'); // Xóa khỏi $data để không bị update vào user

            // Xử lý branch_id cho single_branch role
            // Nếu role có scope là single_branch và có branchIds, lấy phần tử đầu tiên
            if ($roleId && is_null($data['branch_id'] ?? null) && !is_null($branchIds) && is_array($branchIds) && count($branchIds) > 0) {
                $role = Role::where('guard_name', 'api')->where('id', $roleId)->first();
                if ($role) {
                    $scope = $this->getRoleScope($role);
                    if ($scope === Role::SCOPE_SINGLE_BRANCH) {
                        $data['branch_id'] = (int) $branchIds[0];
                    }
                }
            }

            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'],
                'password' => Hash::make($data['password']),
                'is_active' => $data['is_active'],
                'gender' => $data['gender'] ?? null,
                'date_of_birth' => $data['date_of_birth'] ?? null,
                'address' => $data['address'] ?? null,
                'branch_id' => $data['branch_id'] ?? null, // Cho sales-staff
            ]);

            if ($roleId) {
                if (!isset($role)) {
                    $role = Role::where('guard_name', 'api')->where('id', $roleId)->firstOrFail();
                }
                $user->assignRole($role);
                
                // Log để debug
                Log::info("Creating admin user - Role: {$role->name}, branchIds: " . json_encode($branchIds) . ", branch_id: " . ($data['branch_id'] ?? 'null'));
                
                // Xử lý branch theo scope của role
                $this->applyBranchScope($user, $role, $branchIds, $data['branch_id'] ?? null);
            }

            // Xử lý upload ảnh
            if ($imageFile instanceof UploadedFile) {
                $basePath = $this->imageService->store($imageFile, 'users', $user->name);
                if ($basePath) {
                    $user->image()->create(['image_url' => $basePath, 'is_featured' => 1]);
                }
            }

            Log::info("Tài khoản quản trị [ID: {$user->id}] đã được tạo bởi người dùng [ID: " . auth()->id() . "].");
            return $user->load(['roles', 'image', 'branch', 'branches']);
        });
    }

    /**
     * Cập nhật tài khoản quản trị.
     */
    public function updateAdminUser(User $user, array $data)
    {
        return DB::transaction(function () use ($user, $data) {

            $imageFile = Arr::pull($data, 'image_url');
            $roleId = Arr::pull($data, 'role_id');
            // Kiểm tra branch_ids có được gửi lên không (khác với default null)
            $branchIds = Arr::has($data, 'branch_ids') ? $data['branch_ids'] : null;
            Arr::forget($data, 'branch_ids'); // Xóa khỏi $data để không bị update vào user
            
            // Xử lý password nếu có
            if (isset($data['password']) && !empty($data['password'])) {
                $data['password'] = Hash::make($data['password']);
            } else {
                unset($data['password']);
            }

           // Cập nhật vai trò - chỉ cho phép 1 role
            $role = null;
            if (!is_null($roleId)) {
                $role = Role::where('guard_name', 'api')->where('id', $roleId)->firstOrFail();
                $user->syncRoles([$role]);
            } else {
                // Nếu không cập nhật role, lấy role hiện tại (chỉ lấy role đầu tiên)
                $role = $user->roles->first();
            }
            
            // Xử lý branch_id cho single_branch role
            // Nếu role có scope là single_branch và có branchIds, lấy phần tử đầu tiên
            if ($role && is_null($data['branch_id'] ?? null) && !is_null($branchIds) && is_array($branchIds) && count($branchIds) > 0) {
                $scope = $this->getRoleScope($role);
                if ($scope === Role::SCOPE_SINGLE_BRANCH) {
                    $data['branch_id'] = (int) $branchIds[0];
                }
            }
            
            // Xử lý branch theo scope của role
            if ($role) {
                $this->applyBranchScope($user, $role, $branchIds, $data['branch_id'] ?? null);
            }
            
            // Xử lý cập nhật ảnh
            if ($imageFile instanceof UploadedFile) {
                if ($oldImage = $user->image) {
                    $this->imageService->delete($oldImage->image_url, 'users');
                }
                $basePath = $this->imageService->store($imageFile, 'users', $user->name);
                if ($basePath) {
                    $user->image()->updateOrCreate(['imageable_id' => $user->id], ['image_url' => $basePath, 'is_featured' => 1]);
                }
            }

            $user->update($data);

            Log::info("Tài khoản quản trị [ID: {$user->id}] đã được cập nhật bởi người dùng [ID: " . auth()->id() . "].");
            return $user->load(['roles', 'image', 'branch', 'branches']);
        });
    }

    /**
     * Xóa một tài khoản quản trị.
     */
    public function deleteAdminUser(User $user)
    {
        if ($user->hasRole('super-admin')) {
            throw new Exception('Không thể xóa tài khoản Super Admin.');
        }

        $userId = $user->id;
        $userEmail = $user->email;
        $user->delete();

        Log::warning(
            "Tài khoản quản trị [ID: {$userId}, Email: {$userEmail}] đã bị xóa bởi người dùng [ID: " . auth()->id() . "]."
        );
    }

    /**
     * Xóa mềm nhiều tài khoản quản trị cùng lúc.
     */

    public function multiDeleteAdminUsers(array $ids): array
    {
        $usersToDelete = User::whereIn('id', $ids)->get();
        $safeToDeleteIds = [];
        $errors = [];
        foreach ($usersToDelete as $user) {
            if ($user->hasRole('super-admin')) {
                $errors[] = "Không thể xóa tài khoản Super Admin: {$user->email}.";
            } else {
                $safeToDeleteIds[] = $user->id;
            }
        }
        if (!empty($errors)) {
            throw new Exception(implode(' ', $errors));
        }
        $deletedCount = 0;
        if (!empty($safeToDeleteIds)) {
            DB::transaction(function () use ($safeToDeleteIds, &$deletedCount) {
                $deletedCount = User::destroy($safeToDeleteIds);
            });
            Log::warning("{$deletedCount} tài khoản quản trị với các ID: [" . implode(', ', $safeToDeleteIds) . "] đã bị chuyển vào thùng rác bởi người dùng [ID: " . (auth()->id() ?? 'N/A') . "].");
        }
        return ['deleted_count' => $deletedCount];
    }

    /**
     * Áp dụng branch scope dựa trên role
     * 
     * @param User $user
     * @param Role|null $role
     * @param array|null $branchIds
     * @param int|null $branchId
     * @return void
     * @throws Exception
     */
    private function applyBranchScope(User $user, ?Role $role, ?array $branchIds, ?int $branchId): void
    {
        if (!$role) {
            // Nếu không có role, xóa tất cả branches
            $user->branches()->sync([]);
            return;
        }

        // Tính branch scope từ role name
        $scope = $this->getRoleScope($role);

        $currentUser = auth()->user();
        $accessibleBranchIds = BranchAccessService::getAccessibleBranchIds($currentUser);
        
        // Log để debug
        Log::info("applyBranchScope - Role: {$role->name}, Scope: {$scope}, branchIds: " . json_encode($branchIds) . ", branch_id: " . ($branchId ?? 'null'));

        switch ($scope) {
            case Role::SCOPE_ALL_BRANCHES:
                // Super-admin và Accountant: tự động chọn tất cả branches
                // Backend xử lý - không cần branch_ids, nhưng có thể gán tất cả branches
                if (!is_null($branchIds)) {
                    // Nếu có gửi branch_ids, validate quyền
                    if (!$currentUser->hasRole('super-admin')) {
                        $branchIds = array_intersect($branchIds, $accessibleBranchIds);
                    }
                    $user->branches()->sync($branchIds);
                } else {
                    // Nếu không có branch_ids, giữ nguyên hoặc xóa (vì all_branches không cần lưu)
                    $user->branches()->sync([]);
                }
                break;

            case Role::SCOPE_SINGLE_BRANCH:
                // Sales-staff: chỉ được chọn 1 branch (branch_id)
                // Nếu frontend gửi branch_ids (array), lấy phần tử đầu tiên
                if (!is_null($branchIds) && is_array($branchIds) && count($branchIds) > 0) {
                    $branchId = (int) $branchIds[0];
                }
                
                $user->branches()->sync([]); // Xóa branches trong pivot table
                
                if (!is_null($branchId)) {
                    // Validate quyền
                    if (!$currentUser->hasRole('super-admin') && !in_array($branchId, $accessibleBranchIds)) {
                        throw new Exception('Bạn không có quyền gán chi nhánh này.');
                    }
                    // branch_id đã được set trong $data khi tạo/cập nhật user
                }
                break;

            case Role::SCOPE_MULTIPLE_BRANCHES:
                // Branch-admin: chọn nhiều branches (ít nhất 1)
                // Chỉ xử lý nếu branchIds được gửi lên (không phải null)
                // Nếu là null, giữ nguyên branches cũ (cho update)
                if (is_null($branchIds)) {
                    // Nếu không có branch_ids trong request, giữ nguyên branches cũ (chỉ áp dụng khi update)
                    break;
                }

                // Validate ít nhất 1 branch (khi có gửi branchIds)
                if (empty($branchIds)) {
                    throw new Exception('Vui lòng chọn ít nhất một chi nhánh cho vai trò Quản lý chi nhánh.');
                }

                // Kiểm tra quyền
                if (!$currentUser->hasRole('super-admin')) {
                    $branchIds = array_intersect($branchIds, $accessibleBranchIds);
                    if (empty($branchIds)) {
                        throw new Exception('Bạn không có quyền gán các chi nhánh này.');
                    }
                }

                $user->branches()->sync($branchIds);
                Log::info("Đã sync branches cho user [ID: {$user->id}, Role: {$role->name}]: " . json_encode($branchIds));
                break;

            default:
                // Default: xóa branches
                $user->branches()->sync([]);
                break;
        }
    }

    /**
     * Lấy branch scope từ role name
     * Sử dụng Role::getBranchScope() để hỗ trợ các role mới được tạo trên giao diện
     * 
     * @param Role $role
     * @return string
     */
    private function getRoleScope(Role $role): string
    {
        // Sử dụng method getBranchScope() của Role model để lấy scope từ config
        // Điều này cho phép hỗ trợ các role mới được tạo trên giao diện
        return $role->getBranchScope();
    }

    /**
     * Xử lý branch assignment khi gán role cho user (dùng khi gán role qua giao diện)
     * Method này được gọi từ PermissionService khi assign roles
     * 
     * Hỗ trợ các role mới được tạo trên giao diện thông qua config trong config/roles.php
     * Nếu role không có trong config, sẽ được xử lý như SCOPE_SINGLE_BRANCH (default)
     * 
     * @param User $user
     * @param Role|null $role Role đầu tiên nếu user có nhiều roles
     * @return void
     */
    public function handleBranchAssignmentForRole(User $user, ?Role $role = null): void
    {
        // Nếu user không có role nào, xóa tất cả branches
        if (!$role) {
            $user->branches()->sync([]);
            return;
        }

        // Tính branch scope từ role (sử dụng config, hỗ trợ role mới)
        $scope = $this->getRoleScope($role);
        
        // Log để debug khi có role mới
        if (!in_array($role->name, ['super-admin', 'accountant', 'sales-staff', 'branch-admin'])) {
            Log::info("Xử lý branch assignment cho role mới: {$role->name}, scope: {$scope}");
        }

        switch ($scope) {
            case Role::SCOPE_ALL_BRANCHES:
                // Super-admin và Accountant: không cần lưu branches, xóa để tránh confusion
                $user->branches()->sync([]);
                break;

            case Role::SCOPE_SINGLE_BRANCH:
                // Sales-staff: sử dụng branch_id field, xóa branches trong pivot table
                $user->branches()->sync([]);
                // branch_id đã được set khi tạo/cập nhật user, không cần xử lý thêm
                break;

            case Role::SCOPE_MULTIPLE_BRANCHES:
                // Branch-admin: cần có branches trong pivot table
                // Nếu user chưa có branches, giữ nguyên (không xóa) để admin có thể gán sau
                // Nếu user đã có branches, validate và giữ lại nếu hợp lệ
                // Reload user để đảm bảo có relationship branches
                $user = $user->fresh('branches');
                $currentBranches = $user->branches->pluck('id')->toArray();
                if (!empty($currentBranches)) {
                    // Validate quyền của current user
                    $currentUser = auth()->user();
                    if ($currentUser && !$currentUser->hasRole('super-admin')) {
                        $accessibleBranchIds = BranchAccessService::getAccessibleBranchIds($currentUser);
                        $validBranches = array_intersect($currentBranches, $accessibleBranchIds);
                        if (!empty($validBranches)) {
                            // Giữ lại các branches hợp lệ
                            $user->branches()->sync($validBranches);
                        } else {
                            // Nếu không có branches hợp lệ, xóa hết
                            $user->branches()->sync([]);
                        }
                    }
                    // Nếu là super-admin, giữ nguyên branches hiện tại
                }
                // Nếu user chưa có branches, không làm gì (để admin gán sau)
                break;

            default:
                // Default: xóa branches
                $user->branches()->sync([]);
                break;
        }
    }
}
