<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\MenuService;
use App\Http\Requests\Api\Menu\GetMenuRequest;
use Illuminate\Support\Facades\Log; 

class MenuController extends Controller
{
     protected $menuService;

    public function __construct(MenuService $menuService)
    {
        $this->menuService = $menuService;
    }
    /**
     * Lấy thực đơn (menu) cho POS (React App) hoặc App Công nhân.
     */
    public function getMenu(GetMenuRequest $request) 
    {

        try {
            $validatedData = $request->validated();
            
            $branchId = (int) $validatedData['branch_id'];
            
            // Lấy 'price_type' từ request, nếu không có thì mặc định là 'store_price'
            $priceType = $validatedData['price_type'] ?? MenuService::PRICE_TYPE_STORE;
            $menuData = $this->menuService->getMenu($branchId, $priceType);

            return response()->json([
                'success' => true,
                'data' => $menuData
            ], 200);

        } catch (\Exception $e) {
            Log::error('Lỗi khi lấy MenuService: ' . $e->getMessage(), [ 
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Lỗi máy chủ nội bộ khi lấy thực đơn.',
            ], 500);
        }
    }
}
