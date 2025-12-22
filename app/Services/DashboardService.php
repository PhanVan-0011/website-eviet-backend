<?php

namespace App\Services;

use App\Models\Order;
use App\Models\User;
use App\Models\Product;
use App\Models\Post;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Log;
use App\Services\BranchAccessService;

class DashboardService
{
    /**
     * Lấy tất cả các số liệu thống kê cần thiết cho Dashboard.
     *
     * @return array
     */
    public function getDashboardStatistics(): array
    {
        try {
            //Các thẻ chỉ số KPI tổng quan
            $kpis = $this->getKpis();

            //Dữ liệu cho biểu đồ doanh thu 6 tháng gần nhất
            $revenueChart = $this->getRevenueChartData(6, 'month');

            //Dữ liệu cho biểu đồ tròn trạng thái đơn hàng
            $orderStatusChart = $this->getOrderStatusDistribution();

            //Top 5 sản phẩm bán chạy nhất trong 30 ngày qua
            $topProducts = $this->getTopSellingProducts(30, 5);

            //đơn hàng gần nhất cần xử lý
            $pendingOrders = $this->getRecentPendingOrders(5);

            return [
                'kpis' => $kpis,
                'revenue_chart' => $revenueChart,
                'order_status_chart' => $orderStatusChart,
                'top_selling_products' => $topProducts,
                'pending_orders' => $pendingOrders,
            ];
        } catch (Exception $e) {
            Log::error('Lỗi Service khi lấy dữ liệu dashboard:', ['message' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Lấy các chỉ số KPI chính.
     */
    private function getKpis(): array
    {
        $now = Carbon::now();
        $dayOfMonth = $now->day;

        // Kỳ hiện tại: Từ đầu tháng này đến hôm nay
        $currentPeriodStart = $now->copy()->startOfMonth();
        $currentPeriodEnd = $now->copy();

        // Kỳ so sánh: Từ đầu tháng trước đến ngày tương ứng của tháng trước
        $previousPeriodStart = $now->copy()->subMonthNoOverflow()->startOfMonth();
        $previousPeriodEnd = $previousPeriodStart->copy()->addDays($dayOfMonth - 1)->endOfDay();


        //Doanh thu 
        $baseRevenueQuery = Payment::where('status', 'success')
            ->whereHas('order', function ($query) {
                $query->where('status', 'delivered');
                // Apply branch filter cho orders
                BranchAccessService::applyBranchFilter($query);
            });

        $revenueThisPeriod = (float) (clone $baseRevenueQuery)->whereBetween('paid_at', [$currentPeriodStart, $currentPeriodEnd])->sum('amount');
        $revenuePreviousPeriod = (float) (clone $baseRevenueQuery)->whereBetween('paid_at', [$previousPeriodStart, $previousPeriodEnd])->sum('amount');
        $totalRevenue = (float) (clone $baseRevenueQuery)->sum('amount');

        $ordersQuery = Order::where('status', 'delivered');
        BranchAccessService::applyBranchFilter($ordersQuery);
        
        $ordersThisPeriod = (clone $ordersQuery)->whereBetween('created_at', [$currentPeriodStart, $currentPeriodEnd])->count();
        // Đơn hàng 
        $ordersPreviousPeriod = (clone $ordersQuery)->whereBetween('created_at', [$previousPeriodStart, $previousPeriodEnd])->count();
        $totalOrders = (clone $ordersQuery)->count();

        //Người dùng
        $baseUserQuery = User::whereDoesntHave('roles');
        $usersThisPeriod = (clone $baseUserQuery)->whereBetween('created_at', [$currentPeriodStart, $currentPeriodEnd])->count();
        $usersPreviousPeriod = (clone $baseUserQuery)->whereBetween('created_at', [$previousPeriodStart, $previousPeriodEnd])->count();
        $totalUsers = (clone $baseUserQuery)->count();

        //Sản phẩm
        $totalProducts = Product::count();
        $lowStockProductsCount = Product::where('stock_quantity', '<', 10)->where('stock_quantity', '>', 0)->count();

        return [
            'total_revenue' => [
                'value' => $totalRevenue,
                'change' => $this->calculatePercentageChange($revenueThisPeriod, $revenuePreviousPeriod)
            ],
            'total_orders' => [
                'value' => $totalOrders,
                'change' => $this->calculatePercentageChange($ordersThisPeriod, $ordersPreviousPeriod)
            ],
            'total_users' => [
                'value' => $totalUsers,
                'change' => $this->calculatePercentageChange($usersThisPeriod, $usersPreviousPeriod)
            ],
            'total_products' => [
                'value' => $totalProducts,
                'secondary_info' => [
                    'type' => 'warning',
                    'text' => "{$lowStockProductsCount} sản phẩm sắp hết hàng"
                ]
            ],
        ];
    }
    /**
     * Hàm trợ giúp để tính toán phần trăm thay đổi.
     */
    private function calculatePercentageChange($current, $previous): float
    {
        if ($previous == 0) {
            return $current > 0 ? 100.0 : 0.0;
        }

        $change = (($current - $previous) / abs($previous)) * 100;
        $change = round($change, 1);

        // Nếu vượt quá ±100 thì giới hạn lại
        if ($change > 100) {
            return 100.0;
        } elseif ($change < -100) {
            return -100.0;
        }

        return $change;
    }



    //     $totalRevenue = Payment::where('status', 'success')
    //         ->whereHas('order', function ($query) {
    //             $query->where('status', 'delivered');
    //         })
    //         ->whereNotNull('paid_at')
    //         ->sum('amount');

    //     return [
    //         'total_revenue' => (float) $totalRevenue,
    //         'total_orders' => Order::count(),
    //         'total_users' => User::whereDoesntHave('roles')->count(),
    //         'total_products' => Product::count(),
    //     ];
    // }

    /**
     * Lấy dữ liệu doanh thu cho biểu đồ.
     */
    private function getRevenueChartData(int $count, string $unit): array
    {
        $endDate = Carbon::now();
        $startDate = ($unit === 'month')
            ? Carbon::now()->subMonths($count - 1)->startOfMonth()
            : Carbon::now()->subDays($count - 1)->startOfDay();

        $sqlFormat = ($unit === 'month') ? '%Y-%m' : '%Y-%m-%d';
        $phpFormat = ($unit === 'month') ? 'Y-m' : 'Y-m-d';

        $revenueData = Payment::where('status', 'success')
            ->whereNotNull('paid_at')
            ->whereHas('order', function ($query) {
                $query->where('status', 'delivered');
                // Apply branch filter cho orders
                BranchAccessService::applyBranchFilter($query);
            })
            ->whereBetween('paid_at', [$startDate, $endDate])
            ->whereRaw('DATE_FORMAT(paid_at, "%Y-%m") = DATE_FORMAT(created_at, "%Y-%m")')
            ->select(
                DB::raw("DATE_FORMAT(paid_at, '{$sqlFormat}') as date"),
                DB::raw('SUM(amount) as total')
            )
            ->groupBy('date')
            ->orderBy('date', 'asc')
            ->get()
            ->keyBy('date');

        $labels = [];
        $data = [];
        $date = $startDate->copy();

        while ($date <= $endDate) {
            $dateString = $date->format($phpFormat);

            if ($unit === 'month') {
                $labels[] = 'Tháng ' . $date->format('n/Y');
                $date->addMonth();
            } else {

                $labels[] = $date->format('d-m-Y');
                $date->addDay();
            }

            $data[] = (float) ($revenueData->get($dateString)->total ?? 0);
        }

        return ['labels' => $labels, 'data' => $data];
    }

    /**
     * Lấy dữ liệu cho biểu đồ tròn trạng thái đơn hàng.
     */
    private function getOrderStatusDistribution(): array
    {
        $ordersQuery = Order::query();
        BranchAccessService::applyBranchFilter($ordersQuery);
        
        $statusCounts = (clone $ordersQuery)
            ->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->get()
            ->pluck('count', 'status');

        $statusMap = [
            'delivered' => 'Đã giao',
            'pending' => 'Chờ xử lý',
            'draft' => 'Nháp',
            'cancelled' => 'Đã hủy',
            'processing' => 'Đang xử lý',
        ];

        $labels = [];
        $data = [];
        foreach ($statusMap as $status => $label) {
            $labels[] = $label;
            $data[] = $statusCounts->get($status, 0);
        }

        return ['labels' => $labels, 'data' => $data];
    }

    /**
     * Lấy top sản phẩm bán chạy.
     */
    private function getTopSellingProducts(int $days, int $limit): \Illuminate\Support\Collection
    {
        $branchIds = BranchAccessService::getAccessibleBranchIds();
        
        $query = DB::table('order_details')
            ->join('products', 'order_details.product_id', '=', 'products.id')
            ->join('orders', 'order_details.order_id', '=', 'orders.id')
            ->join('payments', 'payments.order_id', '=', 'orders.id')
            ->select(
                'products.name as product_name',
                DB::raw('SUM(order_details.quantity) as total_sold'),
                DB::raw('SUM(order_details.quantity * order_details.unit_price) as total_revenue')
            )
            ->where('orders.status', 'delivered')
            ->where('payments.status', 'success')
            ->whereNotNull('payments.paid_at');
            
        // Apply branch filter nếu không phải super admin/accountant
        if (!empty($branchIds)) {
            $query->whereIn('orders.branch_id', $branchIds);
        }
        
        return $query
            ->groupBy('products.id', 'products.name')
            ->orderByDesc('total_sold')
            ->limit($limit)
            ->get();
    }

    /**
     * Lấy các đơn hàng gần nhất cần xử lý.
     */
    private function getRecentPendingOrders(int $limit): \Illuminate\Support\Collection
    {
        $query = Order::where('status', 'pending');
        BranchAccessService::applyBranchFilter($query);
        
        return $query
            ->select('id as order_id', 'order_code', 'client_name', 'status', 'grand_total', 'created_at')
            ->latest('created_at')
            ->limit($limit)
            ->get();
    }
}
