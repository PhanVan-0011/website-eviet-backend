<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\LogoutRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\UpdateProfileRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\QueryException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Models\User;


class AuthController extends Controller
{
    // Register
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
        }
    }
    //Get chi tiết người dùng đăng nhập
    public function me(Request $request)
    {
        return response()->json([
            'success' => true,
            'data' => $request->user(),
        ], 200);
    }
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
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi không xác định',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
