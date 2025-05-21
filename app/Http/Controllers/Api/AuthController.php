<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\LogoutRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\ResetPasswordRequest;
use App\Http\Requests\UpdateProfileRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\QueryException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Carbon\Carbon;
use App\Models\User;
use App\Services\SmsService;
use App\Models\otpVerification;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    //Register
    public function register(RegisterRequest $request)
    {
        try {
            $validated = $request->validated();
            $validated['password'] = Hash::make($validated['password']);
            $validated['is_active'] = true;
            $validated['is_verified'] = false;
            $user = User::create($validated);
            // Create access token
            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'success' => true,
                'message' => 'Đăng ký thành công',
                'data' => [
                    'user' => $user,
                    'access_token' => $token,
                    'token_type' => 'Bearer',
                ]
            ], 201);
        } catch (\Illuminate\Database\QueryException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Email hoặc số điện thoại đã tồn tại',
            ], 409);
        }
    }
    // public function register(Request $request)
    // {
    //     // Validate dữ liệu đầu vào
    //     $request->validate([
    //         'name' => 'required|string|max:255',
    //         'email' => 'required|string|email|max:255|unique:users,email',
    //         'phone' => 'required|string|regex:/^0[0-9]{9}$/|unique:users,phone',
    //         'password' => 'required|string|min:8|confirmed',
    //         'gender' => 'nullable|in:male,female,other',
    //         'date_of_birth' => 'nullable|date|before:today',
    //     ]);

    //     // Tạo mã OTP
    //     $otpCode = rand(100000, 999999);
    //     $expireAt = Carbon::now()->addMinutes(5);

    //     // Xóa OTP cũ nếu có
    //     otpVerification::where('phone_number', $request->phone)
    //         ->where('purpose', 'register')
    //         ->delete();

    //     // Lưu OTP vào bảng otp_verifications
    //     OtpVerification::create([
    //         'phone_number' => $request->phone,
    //         'otp_code' => $otpCode,
    //         'used' => false,
    //         'expire_at' => $expireAt,
    //         'purpose' => 'register',
    //         'user_id' => null, // Chưa có user
    //     ]);

    //     // Gửi OTP qua SMS
    //     $smsService = new SmsService();
    //     if ($smsService->sendOtp($request->phone, $otpCode)) {
    //         return response()->json([
    //             'message' => 'OTP đã được gửi đến số điện thoại của bạn.',
    //             'phone' => $request->phone,
    //         ], 200);
    //     } else {
    //         return response()->json([
    //             'message' => 'Không thể gửi OTP. Vui lòng thử lại.',
    //         ], 500);
    //     }
    // }
    // public function verifyOtp(Request $request)
    // {
    //     // Validate dữ liệu đầu vào
    //     $request->validate([
    //         'phone' => 'required|string|regex:/^0[0-9]{9}$/',
    //         'otp_code' => 'required|numeric|digits:6',
    //         'name' => 'required|string|max:255',
    //         'email' => 'required|string|email|max:255|unique:users,email',
    //         'password' => 'required|string|min:8',
    //         'gender' => 'nullable|in:male,female,other',
    //         'date_of_birth' => 'nullable|date|before:today',
    //     ]);

    //     // Tìm OTP
    //     $otp = OtpVerification::where('phone_number', $request->phone)
    //         ->where('otp_code', $request->otp_code)
    //         ->where('purpose', 'register')
    //         ->where('used', false)
    //         ->where('expire_at', '>', Carbon::now())
    //         ->first();

    //     if (!$otp) {
    //         return response()->json([
    //             'message' => 'Mã OTP không hợp lệ, đã được sử dụng hoặc đã hết hạn.',
    //         ], 422);
    //     }

    //     // Tạo user
    //     $user = User::create([
    //         'name' => $request->name,
    //         'email' => $request->email,
    //         'phone' => $request->phone,
    //         'gender' => $request->gender,
    //         'date_of_birth' => $request->date_of_birth,
    //         'password' => Hash::make($request->password),
    //         'is_active' => true,
    //         'is_verified' => true,
    //         'phone_verified_at' => Carbon::now(),
    //     ]);

    //     // Đánh dấu OTP đã sử dụng
    //     $otp->update([
    //         'used' => true,
    //         'user_id' => $user->id,
    //     ]);

    //     // Tạo token
    //     $token = $user->createToken('auth_token')->plainTextToken;

    //     return response()->json([
    //         'message' => 'Đăng ký thành công!',
    //         'user' => $user,
    //         'token' => $token,
    //     ], 201);
    // }
    // public function resendOtp(Request $request)
    // {
    //     // Validate dữ liệu đầu vào
    //     $request->validate([
    //         'phone' => 'required|string|regex:/^0[0-9]{9}$/',
    //     ]);

    //     // Xóa OTP cũ
    //     OtpVerification::where('phone_number', $request->phone)
    //         ->where('purpose', 'register')
    //         ->delete();

    //     // Tạo mã OTP mới
    //     $otpCode = rand(100000, 999999);
    //     $expireAt = Carbon::now()->addMinutes(5);

    //     OtpVerification::create([
    //         'phone_number' => $request->phone,
    //         'otp_code' => $otpCode,
    //         'used' => false,
    //         'expire_at' => $expireAt,
    //         'purpose' => 'register',
    //         'user_id' => null,
    //     ]);

    //     // Gửi OTP
    //     $smsService = new SmsService();
    //     if ($smsService->sendOtp($request->phone, $otpCode)) {
    //         return response()->json([
    //             'message' => 'OTP đã được gửi lại.',
    //             'phone' => $request->phone,
    //         ], 200);
    //     } else {
    //         return response()->json([
    //             'message' => 'Không thể gửi OTP. Vui lòng thử lại.',
    //         ], 500);
    //     }
    // }
    //Login
    public function login(LoginRequest $request)
    {
        $user = User::where('phone', $request->phone)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Số điện thoại hoặc mật khẩu không đúng'
            ], 401);
        }

        // Optional: Kiểm tra tài khoản bị khóa
        if (! $user->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Tài khoản đã bị khóa'
            ], 403);
        }
        // Tạo access token
        $token = $user->createToken('auth_token')->plainTextToken;
        // Cập nhật thời gian đăng nhập cuối cùng
        $user->last_login_at = now();
        $user->save();
        return response()->json([
            'success' => true,
            'message' => 'Đăng nhập thành công',
            'data' => [
                'user' => $user,
                'access_token' => $token,
                'token_type' => 'Bearer',
            ]
        ]);
    }
    //Logout
    public function logout(LogoutRequest $request)
    {
        try {
            $token = $request->user()?->currentAccessToken();

            // Kiểm tra nếu có token thì xóa
            if ($token) {
                $token->delete();
                return response()->json([
                    'success' => true,
                    'message' => 'Đăng xuất thành công'
                ]);
            }
            return response()->json([
                'success' => true,
                'message' => 'Đã đăng xuất hoặc không có token hợp lệ'
            ]);
        } catch (QueryException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi cơ sở dữ liệu'
            ], 500);
        } catch (\Throwable $e) {
            Log::error('Lỗi khi đăng xuất: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Đã xảy ra lỗi khi đăng xuất.',
            ], 500);
        }
    }
    //Get chi tiết người dùng đăng nhập
    public function me(Request $request)
    {
        try {
            $user = $request->user();
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không tìm thấy người dùng.',
                    'data' => null
                ], 404);
            }
            return response()->json([
                'success' => true,
                "message" => "Lấy thông tin người dùng thành công",
                'data' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'gender' => $user->gender,
                    'date_of_birth' => $user->date_of_birth
                        ? Carbon::parse($user->date_of_birth)->format('d/m/Y')
                        : null,
                ],

            ], 200);
        } catch (\Illuminate\Database\QueryException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi cơ sở dữ liệu. Vui lòng kiểm tra dữ liệu gửi lên.',
            ], 500);
        } catch (\Throwable $e) {
            Log::error('Lỗi khi lấy thông tin chi tiết người dùng: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Đã xảy ra lỗi khi lấy chi tiết thông tin người dùng.',
            ], 500);
        }
    }
    //Update profile user
    public function update_profile(UpdateProfileRequest $request)
    {
        try {
            $user = $request->user();

            $user->update($request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Cập nhật thông tin thành công',
                'data' => $user,
            ]);
        } catch (\Illuminate\Database\QueryException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi cơ sở dữ liệu. Vui lòng kiểm tra dữ liệu gửi lên.',
            ], 500);
        } catch (\Throwable $e) {
            Log::error('Lỗi khi cập nhật thông tin người dùng: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Đã xảy ra lỗi khi cập nhật thông tin người dùng.',
            ], 500);
        }
    }
    //Get list User
    public function getUsers(Request $request)
    {
        try {
            $users = User::all();

            return response()->json([
                'success' => true,
                'message' => 'Lấy danh sách người dùng thành công.',
                'data' => $users
            ], 200);
        } catch (\Throwable $e) {
            Log::error('Lỗi khi lấy danh sách user: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Đã xảy ra lỗi khi lấy danh sách người dùng.',
            ], 500);
        }
    }
    public function deleteUser($id)
    {
        try {
            $user = User::find($id);

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Người dùng không tồn tại.'
                ], 404);
            }

            $user->delete();

            return response()->json([
                'success' => true,
                'message' => 'Xoá người dùng thành công.'
            ], 200);
        } catch (\Throwable $e) {
            Log::error('Lỗi khi xoá user: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Đã xảy ra lỗi khi xoá người dùng.',
            ], 500);
        }
    }
    public function resetPasswordByPhone(ResetPasswordRequest $request)
    {
        try {
            $user = User::where('phone', $request->phone)->first();
            if (!$user) {
                return response()->json([ 
                    'success' => false,
                    'message' => 'Không tìm thấy người dùng với số điện thoại này.',
                ], 404);
            }
            $user->password = Hash::make($request->new_password);
            $user->save();

            return response()->json([
                'success' => true,
                'message' => 'Mật khẩu đã được cập nhật thành công.',
            ], 200);
        } catch (\Illuminate\Database\QueryException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi cơ sở dữ liệu. Vui lòng thử lại sau.',
            ], 500);
        } catch (\Throwable $e) {
            Log::error('Lỗi khi đặt lại mật khẩu bằng số điện thoại: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Đã xảy ra lỗi không mong muốn.',
            ], 500);
        }
    }
}
