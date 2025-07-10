<?php

namespace App\Services;

use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\Models\Role;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Arr;
use Illuminate\Http\UploadedFile;

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
                })->with(['roles', 'image']);
            

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
                })->with(['roles', 'image']);

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
            // Tải kèm cả quan hệ 'image' để trả về đầy đủ thông tin
            $user = User::onlyTrashed()->with(['roles', 'image'])->findOrFail($id);
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
        $user = User::onlyTrashed()->with('image')->findOrFail($id);
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
        return User::whereHas('roles')->with(['roles', 'permissions', 'image'])->findOrFail($id);
    }

    /**
     * Tạo một tài khoản quản trị mới.
     */
    public function createAdminUser(array $data)
    {
        return DB::transaction(function () use ($data) {
            $imageFile = Arr::pull($data, 'image_url');
            $roleIds = Arr::pull($data, 'role_ids', []);

            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'],
                'password' => Hash::make($data['password']),
                'is_active' => $data['is_active'],
                'gender' => $data['gender'] ?? null,
                'date_of_birth' => $data['date_of_birth'] ?? null,
                'address' => $data['address'] ?? null,
            ]);

            if (!empty($roleIds)) {
                $roles = Role::where('guard_name', 'api')->whereIn('id', $roleIds)->get();
                $user->assignRole($roles);
            }

            // Xử lý upload ảnh
            if ($imageFile instanceof UploadedFile) {
                $basePath = $this->imageService->store($imageFile, 'users', $user->name);
                if ($basePath) {
                    $user->image()->create(['image_url' => $basePath, 'is_featured' => 1]);
                }
            }

            Log::info("Tài khoản quản trị [ID: {$user->id}] đã được tạo bởi người dùng [ID: " . auth()->id() . "].");
            return $user->load(['roles', 'image']);
        });
    }

    /**
     * Cập nhật tài khoản quản trị.
     */
    public function updateAdminUser(User $user, array $data)
    {
        return DB::transaction(function () use ($user, $data) {

            $imageFile = Arr::pull($data, 'image_url');
            $roleIds = Arr::pull($data, 'role_ids');
            //$updatePayload = $data;

           // Cập nhật vai trò
            if (!is_null($roleIds)) {
                $roles = Role::where('guard_name', 'api')->whereIn('id', $roleIds)->get();
                $user->syncRoles($roles);
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
            return $user->load(['roles', 'image']);
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
}
