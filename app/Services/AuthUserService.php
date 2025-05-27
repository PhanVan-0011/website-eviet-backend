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
     *
     * @param array $validatedData Dữ liệu đã được validate
     * @return \App\Models\User
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
     *
     * @param string $login Email hoặc số điện thoại
     * @param string $password Mật khẩu
     * @return \App\Models\User|null
     */
    public function login(string $login, string $password): ?User
    {
        $isEmail = filter_var($login, FILTER_VALIDATE_EMAIL);
        $user = $isEmail
            ? User::where('email', $login)->first()
            : User::where('phone', $login)->first();

        if (!$user || !Hash::check($password, $user->password) || !$user->is_active) {
            return null;
        }

        $user->last_login_at = now();
        $user->save();

        return $user;
    }
    /**
     * Đăng xuất người dùng.
     *
     * @param \Illuminate\Http\Request $request
     * @return bool
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
     * Lấy thông tin người dùng hiện tại.
     *
     * @param \App\Models\User $user
     * @return \App\Models\User
     */
    public function getUserProfile(User $user): User
    {
        return $user;
    }

    /**
     * Cập nhật thông tin hồ sơ người dùng.
     *
     * @param \App\Models\User $user
     * @param array $validatedData Dữ liệu đã được validate
     * @return \App\Models\User
     */
    public function updateProfile(User $user, array $validatedData): User
    {
        $user->update($validatedData);
        return $user;
    }
    /**
     * Lấy danh sách người dùng với phân trang và tìm kiếm.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Pagination\LengthAwarePaginator
     */
    public function getUsers(Request $request)
    {
        $query = User::query();

        if ($request->has('keyword') && !empty($request->keyword)) {
            $keyword = $request->keyword;
            $query->where(function ($q) use ($keyword) {
                $q->where('name', 'like', "%{$keyword}%")
                  ->orWhere('email', 'like', "%{$keyword}%")
                  ->orWhere('phone', 'like', "%{$keyword}%")
                  ->orWhere('address', 'like', "%{$keyword}%");
            });
        }

        $perPage = $request->input('per_page', 10);
        $page = $request->input('page', 1);

        return $query->paginate($perPage, ['*'], 'page', $page);
    }

    /**
     * Xóa một người dùng theo ID.
     *
     * @param int $id
     * @return bool
     */
    public function deleteUser(int $id): bool
    {
        $user = User::find($id);
        if ($user) {
            $user->delete();
            return true;
        }
        return false;
    }

    /**
     * Yêu cầu đặt lại mật khẩu bằng số điện thoại (gửi OTP).
     *
     * @param string $phone
     * @return array
     */
    public function requestResetPassword(string $phone): array
    {
        $user = User::where('phone', $phone)->first();
        if (!$user) {
            return ['success' => false, 'message' => 'Không tìm thấy người dùng với số điện thoại này.'];
        }

        $otpCode = rand(100000, 999999);
        $expireAt = Carbon::now()->addMinutes(5);

        OtpVerification::where('phone_number', $phone)
            ->where('purpose', 'reset_password')
            ->delete();

        OtpVerification::create([
            'phone_number' => $phone,
            'otp_code' => $otpCode,
            'used' => false,
            'expire_at' => $expireAt,
            'purpose' => 'reset_password',
            'user_id' => $user->id,
        ]);

        Log::info('OTP generated for reset password: ' . $otpCode . ' for phone: ' . $phone);

        return ['success' => true, 'message' => 'OTP đã được gửi đến số điện thoại của bạn.', 'phone' => $phone];
    }

    /**
     * Đặt lại mật khẩu sau khi xác minh OTP.
     *
     * @param string $phone
     * @param string $otpCode
     * @param string $newPassword
     * @return array
     */
    public function resetPassword(string $phone, string $otpCode, string $newPassword): array
    {
        $otp = OtpVerification::where('phone_number', $phone)
            ->where('otp_code', $otpCode)
            ->where('purpose', 'reset_password')
            ->where('used', false)
            ->where('expire_at', '>', Carbon::now())
            ->first();

        if (!$otp) {
            return ['success' => false, 'message' => 'Mã OTP không hợp lệ, đã được sử dụng hoặc đã hết hạn.'];
        }

        $user = User::where('phone', $phone)->first();
        if (!$user) {
            return ['success' => false, 'message' => 'Không tìm thấy người dùng với số điện thoại này.'];
        }

        $user->password = Hash::make($newPassword);
        $user->save();

        $otp->update(['used' => true]);

        return ['success' => true, 'message' => 'Mật khẩu đã được cập nhật thành công.'];
    }
}