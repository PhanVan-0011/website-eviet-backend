<?php

namespace App\Http\Controllers\Api\Client;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\AuthUserService;
use App\Services\OtpService;
use App\Http\Resources\UserResource;
use Illuminate\Http\JsonResponse;
use App\Http\Requests\Api\Client\ChangePasswordRequest;
use App\Http\Requests\Api\Client\UpdateProfileRequest;
use App\Http\Requests\Api\Client\CompleteProfileRequest;
use Illuminate\Support\Facades\Log;

class ProfileController extends Controller
{
    protected $authUserService;

    public function __construct(AuthUserService $authUserService, OtpService $otpService)
    {
        $this->authUserService = $authUserService;
    }

    /**
     * Lấy thông tin của người dùng đang đăng nhập.
     */
    public function show(Request $request): JsonResponse
    {
        $user = $request->user()->load('image');
        return response()->json(['success' => true, 'data' => new UserResource($user)]);
    }
     public function completeProfile(CompleteProfileRequest $request): JsonResponse
    {
        try {
            // Dùng chung logic updateProfile nhưng validation khác
            $user = $this->authUserService->updateProfile($request->user(), $request->validated());
            return response()->json([
                'success' => true,
                'message' => 'Hoàn tất thông tin thành công.',
                'data' => new UserResource($user)
            ]);
        } catch (\Exception $e) {
            Log::error('Lỗi khi nhập thông tin đăng ký: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Đã có lỗi xảy ra.'], 500);
        }
    }
    /**
     * Cập nhật hồ sơ người dùng / Hoàn tất đăng ký.
     */
    public function update(UpdateProfileRequest $request): JsonResponse
    {
        try {
            $user = $this->authUserService->updateProfile($request->user(), $request->validated());
            return response()->json([
                'success' => true,
                'message' => 'Cập nhật thông tin thành công.',
                'data' => new UserResource($user)
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Đã có lỗi xảy ra khi cập nhật thông tin.'], 500);
        }
    }

    /**
     * Cập nhật Thay đổi mật khẩu sau khi đăn nhập.
     * URL: PUT /api/me/change-password
     */
    public function changePassword(ChangePasswordRequest $request): JsonResponse
    {
         try {
            $this->authUserService->changePassword($request->user(), $request->validated());    
            return response()->json([
                'success' => true,
                'message' => 'Cập nhật mật khẩu thành công.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Đã có lỗi xảy ra khi cập nhật mật khẩu.'
            ], 500);
        }
    }
}
