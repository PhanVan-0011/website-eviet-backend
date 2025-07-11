<?php

namespace App\Services;

use App\Models\User;
use App\Models\Image;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

use App\Services\ImageService;

class UserService
{
    protected $imageService;

    public function __construct(ImageService $imageService)
    {
        $this->imageService = $imageService;
    }

    public function getAllUsers($request)
{
    try {
        $limit = intval($request->input('limit', 10));
        $page = intval($request->input('page', 1));
        $search = $request->input('keyword', '');

        $query = User::whereDoesntHave('roles');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }
        
        $countQuery = clone $query;
        $total = $countQuery->count();

        $users = $query->with('image') 
                       ->orderBy('id', 'desc')
                       ->skip(($page - 1) * $limit)
                       ->take($limit)
                       ->get();

        $last_page = ceil($total / $limit);
        
        return [
            'data' => $users,
            'page' => $page,
            'total' => $total,
            'last_page' => (int) $last_page,
            'next_page' => $page < $last_page ? $page + 1 : null,
            'pre_page' => $page > 1 ? $page - 1 : null,
        ];
    } catch (\Exception $e) {
        Log::error("Lỗi khi lấy danh sách người dùng: " . $e->getMessage());
        throw $e;
    }
}

    public function getUserById($id): User
    {
        try {
            return User::with('image')->findOrFail($id);
        } catch (ModelNotFoundException $e) {
            Log::warning("Không tìm thấy người dùng với ID: {$id}");
            throw $e;
        }
    }

    public function createUser(array $data){
        try {
            return DB::transaction(function () use ($data) {
                $data['password'] = Hash::make($data['password']);
                $data['is_active'] = $data['is_active'] ?? true;
                $data['is_verified'] = false;

                $imageFile = $data['image_url'] ?? null;
                unset($data['image_url']);

                $user = User::create($data);
                if ($imageFile) {
                    $basePath = $this->imageService->store($imageFile, 'users', $user->name);
                    if ($basePath) {
                        $user->image()->create(['image_url' => $basePath, 'is_featured' => 1]);
                    }
                }
                return $user->load('image');
            });
        } catch (\Exception $e) {
            Log::error("Lỗi khi tạo người dùng mới: " . $e->getMessage(), ['data' => $data]);
            throw $e;
        }
    }

   public function updateUser(array $data, int $id): User
    {
        try {
            $user = $this->getUserById($id);
            return DB::transaction(function () use ($user, $data) {
                $imageFile = $data['image_url'] ?? null;
                unset($data['image_url']);

                if (!empty($data['password'])) {
                    $data['password'] = Hash::make($data['password']);
                } else {
                    unset($data['password']);
                }

                if ($imageFile) {
                    if ($oldImage = $user->image) {
                        $this->imageService->delete($oldImage->image_url, 'users');
                    }
                    $basePath = $this->imageService->store($imageFile, 'users', $user->name);
                    if ($basePath) {
                        $user->image()->updateOrCreate(['imageable_id' => $user->id], ['image_url' => $basePath, 'is_featured' => 1]);
                    }
                }

                $user->update($data);
                return $user->load('image');
            });
        } catch (ModelNotFoundException $e) {
            Log::warning("Không tìm thấy người dùng để cập nhật với ID: {$id}");
            throw $e;
        } catch (\Exception $e) {
            Log::error("Lỗi khi cập nhật người dùng ID {$id}: " . $e->getMessage(), ['data' => $data]);
            throw $e;
        }
    }

     public function deleteUser($id): bool
    {
        try {
            $user = $this->getUserById($id);
            return DB::transaction(function () use ($user) {
                if ($image = $user->image) {
                    $this->imageService->delete($image->image_url, 'users');
                    $image->delete();
                }
                return $user->delete();
            });
        } catch (ModelNotFoundException $e) {
            Log::warning("Không tìm thấy người dùng để xóa với ID: {$id}");
            throw $e;
        } catch (\Exception $e) {
            Log::error("Lỗi khi xóa người dùng ID {$id}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Xóa nhiều user cùng lúc
     */
    public function multiDelete(array $ids): int
    {
        try {
            if (empty($ids)) return 0;
            
            return DB::transaction(function () use ($ids) {
                $users = User::with('image')->whereIn('id', $ids)->get();
                if ($users->count() !== count($ids)) {
                    throw new ModelNotFoundException('Một hoặc nhiều ID người dùng không tồn tại.');
                }
                
                foreach ($users as $user) {
                    if ($image = $user->image) {
                        $this->imageService->delete($image->image_url, 'users');
                    }
                }

                Image::where('imageable_type', User::class)->whereIn('imageable_id', $ids)->delete(); 
                
                return User::whereIn('id', $ids)->delete();
            });
        } catch (ModelNotFoundException $e) {
            Log::warning($e->getMessage());
            throw $e;
        } catch (\Exception $e) {
            Log::error("Lỗi khi xóa nhiều người dùng: " . $e->getMessage(), ['ids' => $ids]);
            throw $e;
        }
    }
}
