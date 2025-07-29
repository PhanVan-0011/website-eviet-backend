<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class AuthUserService
{
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
    public function findOrCreateUserAfterOtp(string $phone)
    {
        $user = \App\Models\User::firstOrCreate(
            ['phone' => $phone],
            [
                'name' => 'Người dùng ' . substr($phone, -4),
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
}
