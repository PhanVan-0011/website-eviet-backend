<?php

namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    // Register
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|unique:users,email',
            'phone' => 'required|string|unique:users,phone',
            'password' => 'required|string|min:6|confirmed',
            'address' => 'nullable|string|max:255',
            'gender' => 'nullable|in:male,female,other',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'password' => Hash::make($request->password),
            'address' => $request->address,
            'gender' => $request->gender,
            'is_active' => true,
            'is_verified' => false,
        ]);

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
    }
    public function login(Request $request)
{
    $request->validate([
        'phone' => 'required|string',
        'password' => 'required|string',
    ]);

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

    // Cập nhật thời gian đăng nhập cuối cùng
    $user->last_login_at = now();
    $user->save();

    // Tạo access token
    $token = $user->createToken('auth_token')->plainTextToken;

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
}
