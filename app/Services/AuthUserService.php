<?php

namespace App\Services;
use App\Models\User;
use App\Models\OtpVerification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

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
        try{
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

   

  

   
}