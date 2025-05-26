<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;

class UserService
{
    public function getAllUsers($request)
    {
        $limit = intval($request->input('limit', 10));
        $page = intval($request->input('page', 1));
        $search = $request->input('keyword', '');

        $query = User::query();

        // Tìm kiếm theo name, email, phone
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Sắp xếp theo created_at mới nhất
        $query->orderBy('created_at', 'desc');

        // Chỉ lấy các trường cần thiết
        $fields = [
            'id',
            'name',
            'email',
            'phone',
            'gender',
            'date_of_birth',
            'is_active',
            'is_verified',
            'created_at',
            'updated_at',
        ];
        $query->select($fields);

        // Phân trang thủ công để lấy total
        $total = $query->count();
        $users = $query->skip(($page - 1) * $limit)->take($limit)->get();

        $last_page = ceil($total / $limit);
        $next_page = $page < $last_page ? $page + 1 : null;
        $pre_page = $page > 1 ? $page - 1 : null;

        return response()->json([
            'data' => $users,
            'page' => $page,
            'total' => $total,
            'last_page' => $last_page,
            'next_page' => $next_page,
            'pre_page' => $pre_page,
        ]);
    }

    public function getUserById($id)
    {
        $user = User::findOrFail($id);
        return $user;
    }

    public function createUser($data)
    {
        $data['password'] = Hash::make($data['password']);
        $data['is_active'] = true;
        $data['is_verified'] = false;

        return User::create($data);
    }

    public function updateUser($id, $data)
    {
        $user = User::findOrFail($id);

        if (isset($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }

        $user->update($data);
        return $user;
    }

    public function deleteUser($id)
    {
        $user = User::findOrFail($id);
        return $user->delete();
    }
    // 

    /**
     * Xóa nhiều user cùng lúc
     */
    public function multiDelete($ids)
    {
        // Chuyển đổi chuỗi thành mảng
        $ids = array_map('intval', explode(',', $ids));

        // // // Kiểm tra các ID có tồn tại không
        $existingIds = User::whereIn('id', $ids)->pluck('id')->toArray();
        $nonExistingIds = array_diff($ids, $existingIds);

        if (!empty($nonExistingIds)) {
            throw new ModelNotFoundException('Tồn tại ID cần xóa không tồn tại trong hệ thống');
        }

        // Xóa users
        return User::whereIn('id', $ids)->delete();
    }
}
