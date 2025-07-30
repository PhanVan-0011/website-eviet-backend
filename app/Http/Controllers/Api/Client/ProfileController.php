<?php

namespace App\Http\Controllers\Api\Client;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\AuthUserService;
use App\Services\OtpService;
use App\Http\Resources\UserResource;
use Illuminate\Http\JsonResponse;
use App\Http\Requests\Api\Auth\Me\UpdateProfileRequest;
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
    /**
     * Cập nhật hồ sơ người dùng.
     * URL: POST /api/me/update
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
            Log::error('Update Profile Error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Đã có lỗi xảy ra khi cập nhật thông tin.'], 500);
        }
    }
}
