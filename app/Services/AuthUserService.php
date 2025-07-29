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
        public function findOrCreateUserAfterOtp(string $phone): User
    {

       // 1. Tìm người dùng, bao gồm cả những người đã bị xóa mềm.
        $user = User::withTrashed()->where('phone', $phone)->first();

        if ($user) {
            // 2. Nếu tìm thấy người dùng...
            if ($user->trashed()) {
                // 2a. Nếu họ đã bị xóa, đây có thể là số điện thoại tái sử dụng.
                // Để bảo vệ dữ liệu người dùng cũ, chúng ta sẽ "gỡ liên kết" SĐT khỏi tài khoản cũ.
                $user->phone = null;
                $user->save();
                
                // Sau khi gỡ liên kết, coi như không tìm thấy người dùng và để logic tiếp tục tạo tài khoản mới.
                $user = null; 
            }
        }

        // 3. Nếu không có người dùng nào (hoặc đã được gỡ liên kết), tạo một người dùng hoàn toàn mới.
        if (!$user) {
            $user = User::create([
                'phone' => $phone,
                'name' => 'Người dùng ' . substr($phone, -4),
                'email' => null,
                'password' => Hash::make(uniqid()),
                'is_active' => true,
                'is_verified' => true,
                'phone_verified_at' => now(),
            ]);
        }

        // 4. Kiểm tra trạng thái và cập nhật lần đăng nhập cuối.
        if (!$user->is_active) {
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
