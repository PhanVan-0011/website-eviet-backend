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
            $query = User::with(['roles', 'permissions']);
            $user = $isEmail
                ? $query->where('email', $login)->first()
                : $query->where('phone', $login)->first();
            if (!$user->is_active) {
                return 'locked'; // Tài khoản bị khóa
            }

            if (!$user || !Hash::check($password, $user->password) || !$user->is_active) {
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
     * Tìm hoặc tạo người dùng bằng SĐT sau khi xác thực OTP thành công.
     */
    public function findOrCreateUserAfterOtp(string $phone){
        $user = \App\Models\User::firstOrCreate(
            ['phone' => $phone],
            [
                'name' => 'Người dùng tạm' . substr($phone, -4),
                'email' => null, 
                'password' => \Illuminate\Support\Facades\Hash::make(uniqid()),
                'is_active' => true,
                'is_verified' => true,
                'phone_verified_at' => now(),
            ]
        );

        if (!$user->wasRecentlyCreated && !$user->is_active) {
            throw new \Exception('Tài khoản của bạn đã bị khóa.');
        }

        $user->last_login_at = now();
        $user->save();

        return $user;
    }
    public function updateProfile(User $user, array $data): User
    {
        $imageFile = Arr::pull($data, 'image_url');
        if (isset($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }
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
        //Log::info('Dữ liệu request:', $data);
        $user->update($data);

        return $user->fresh('image');
    }

    public function changePassword(User $user, array $data): bool
    {
        $user->password = Hash::make($data['password']);
        return $user->save();
    }
}
