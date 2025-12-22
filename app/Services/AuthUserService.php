<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Arr;

class AuthUserService
{
     protected ImageService $imageService;

    public function __construct(ImageService $imageService)
    {
        $this->imageService = $imageService;
    }
    /**
     * Đăng ký người dùng mới.
     */
    public function register(array $validatedData): User
    {
        $validatedData['password'] = Hash::make($validatedData['password']);
        $validatedData['is_active'] = true;
        $validatedData['is_verified'] = false;

        return User::create($validatedData);
    }
    /**
     * Xác thực và đăng nhập người dùng.
     */
    public function login(string $login, string $password)
    {
        try {
            $isEmail = filter_var($login, FILTER_VALIDATE_EMAIL);
            $query = User::with(['roles', 'permissions', 'image']);
            $user = $isEmail
                ? $query->where('email', $login)->first()
                : $query->where('phone', $login)->first();

            // Kiểm tra user tồn tại trước
            if (!$user) {
                return null;
            }

            // Kiểm tra tài khoản bị khóa
            if (!$user->is_active) {
                return 'locked';
            }

            // Kiểm tra password
            if (!Hash::check($password, $user->password)) {
                return null;
            }


            $user->last_login_at = now();
            $user->save();

            return $user;
        } catch (\Exception $e) {
            Log::error("Lỗi nghiêm trọng khi đăng nhập: " . $e->getMessage());
        }
    }

    /**
     * Đăng xuất người dùng.
     */
    public function logout(Request $request): bool
    {
        $token = $request->user()?->currentAccessToken();
        if ($token) {
            $token->delete();
            return true;
        }
        return false;
    }
     /**
     * Tạo người dùng mới sau khi hoàn tất đăng ký.
     */
    public function createUser(array $data): User
    {
        return User::create([
            'phone' => $data['phone'],
            'name' => $data['name'],
            'gender' => $data['gender'],
            'date_of_birth' => $data['date_of_birth'],
            'password' => Hash::make($data['password']),
            'email' => null, // Mặc định email là null
            'is_active' => true,
            'is_verified' => true,
            'phone_verified_at' => now(),
        ]);
    }

    /**
     * Đăng nhập người dùng bằng SĐT và mật khẩu.
     */
    public function loginApp(string $phone, string $password)
    {
        $user = User::where('phone', $phone)->first();

        if (!$user || !Hash::check($password, $user->password)) {
            return null; // Trả về null nếu sai thông tin
        }

        if (!$user->is_active) {
            return 'locked'; // Trả về 'locked' nếu tài khoản bị khóa
        }

        $user->last_login_at = now();
        $user->save();

        return $user;
    }
    
    /**
     * Đặt lại mật khẩu cho người dùng.
     */
    public function resetPassword(array $data): bool
    {
        $user = User::where('phone', $data['phone'])->firstOrFail();
        $user->password = Hash::make($data['password']);
        return $user->save();
    }
    /**
     * (MỚI) Cập nhật hồ sơ cho người dùng.
     */
    public function updateProfile(User $user, array $data): User
    {
        $imageFile = Arr::pull($data, 'image_url');

        if ($imageFile) {
            if ($oldImage = $user->image) {
                $this->imageService->delete($oldImage->image_url, 'users');
            }
            $basePath = $this->imageService->store($imageFile, 'users', $data['name'] ?? $user->name);
            if ($basePath) {
                $user->image()->updateOrCreate(
                    ['imageable_id' => $user->id, 'imageable_type' => User::class],
                    ['image_url' => $basePath, 'is_featured' => true]
                );
            }
        }

        $user->update($data);
        return $user->fresh('image');
    }
}
