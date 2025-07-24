<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\DashboardService;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Exception;

class DashBoardController extends Controller
{
    protected DashboardService $dashboardService;

    public function __construct(DashboardService $dashboardService)
    {
        $this->dashboardService = $dashboardService;
    }
    public function getStatistics(Request $request)
    {
        try {
            $stats = $this->dashboardService->getDashboardStatistics();

            return response()->json([
                'success' => true,
                'data' => $stats
            ]);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => 'Đã có lỗi hệ thống xảy ra.'], 500);
        }
    }
}
