<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Xóa cache của Spatie để đảm bảo các thay đổi được áp dụng
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $shouldReset = false; // Đặt thành true nếu bạn muốn xóa toàn bộ và tạo lại từ đầu
        if ($shouldReset) {
            Schema::disableForeignKeyConstraints();
            Permission::truncate();
            Role::truncate();
            DB::table('model_has_permissions')->truncate();
            DB::table('model_has_roles')->truncate();
            DB::table('role_has_permissions')->truncate();
            Schema::enableForeignKeyConstraints();
        }

        $guardName = 'api';

        // --- TẠO CÁC QUYỀN HẠN (PERMISSIONS) VỚI TÊN HIỂN THỊ THÂN THIỆN ---//
        $permissions = [
            // Dashboard
            ['name' => 'dashboard.view', 'display_name' => 'Xem Dashboard tổng quan'],
            
            // Orders
            ['name' => 'orders.view', 'display_name' => 'Xem Đơn hàng'],
            ['name' => 'orders.create', 'display_name' => 'Tạo Đơn hàng'],
            ['name' => 'orders.update', 'display_name' => 'Sửa Đơn hàng'],
            ['name' => 'orders.update_status', 'display_name' => 'Duyệt/Cập nhật trạng thái Đơn hàng'],
            ['name' => 'orders.cancel', 'display_name' => 'Hủy Đơn hàng'],
            ['name' => 'orders.update_payment', 'display_name' => 'Cập nhật trạng thái Thanh toán'],
            
            // Products
            ['name' => 'products.view', 'display_name' => 'Xem Sản phẩm'],
            ['name' => 'products.create', 'display_name' => 'Tạo Sản phẩm'],
            ['name' => 'products.update', 'display_name' => 'Sửa Sản phẩm'],
            ['name' => 'products.delete', 'display_name' => 'Xóa Sản phẩm'],
            ['name' => 'product-attributes.manage', 'display_name' => 'Quản lý Thuộc tính Sản phẩm'],
            
            // Categories
            ['name' => 'categories.view', 'display_name' => 'Xem Danh mục'],
            ['name' => 'categories.create', 'display_name' => 'Tạo Danh mục'],
            ['name' => 'categories.update', 'display_name' => 'Sửa Danh mục'],
            ['name' => 'categories.delete', 'display_name' => 'Xóa Danh mục'],
            
            // Combos
            ['name' => 'combos.view', 'display_name' => 'Xem Combo'],
            ['name' => 'combos.create', 'display_name' => 'Tạo Combo'],
            ['name' => 'combos.update', 'display_name' => 'Sửa Combo'],
            ['name' => 'combos.delete', 'display_name' => 'Xóa Combo'],
            
            // Promotions
            ['name' => 'promotions.view', 'display_name' => 'Xem Khuyến mãi'],
            ['name' => 'promotions.create', 'display_name' => 'Tạo Khuyến mãi'],
            ['name' => 'promotions.update', 'display_name' => 'Sửa Khuyến mãi'],
            ['name' => 'promotions.delete', 'display_name' => 'Xóa Khuyến mãi'],
            
            // Sliders
            ['name' => 'sliders.view', 'display_name' => 'Xem Slider'],
            ['name' => 'sliders.create', 'display_name' => 'Tạo Slider'],
            ['name' => 'sliders.update', 'display_name' => 'Sửa Slider'],
            ['name' => 'sliders.delete', 'display_name' => 'Xóa Slider'],
            
            // Posts
            ['name' => 'posts.view', 'display_name' => 'Xem Bài viết'],
            ['name' => 'posts.create', 'display_name' => 'Tạo Bài viết'],
            ['name' => 'posts.update', 'display_name' => 'Sửa Bài viết'],
            ['name' => 'posts.delete', 'display_name' => 'Xóa Bài viết'],
            
            // Payment Methods
            ['name' => 'payment_methods.view', 'display_name' => 'Xem Phương thức thanh toán'],
            ['name' => 'payment_methods.create', 'display_name' => 'Tạo Phương thức thanh toán'],
            ['name' => 'payment_methods.update', 'display_name' => 'Sửa Phương thức thanh toán'],
            ['name' => 'payment_methods.delete', 'display_name' => 'Xóa Phương thức thanh toán'],
            
            // Supplier Groups
            ['name' => 'supplier-groups.view', 'display_name' => 'Xem Nhóm nhà cung cấp'],
            ['name' => 'supplier-groups.create', 'display_name' => 'Tạo Nhóm nhà cung cấp'],
            ['name' => 'supplier-groups.update', 'display_name' => 'Sửa Nhóm nhà cung cấp'],
            ['name' => 'supplier-groups.delete', 'display_name' => 'Xóa Nhóm nhà cung cấp'],
            
            // Suppliers
            ['name' => 'suppliers.view', 'display_name' => 'Xem Nhà cung cấp'],
            ['name' => 'suppliers.create', 'display_name' => 'Tạo Nhà cung cấp'],
            ['name' => 'suppliers.update', 'display_name' => 'Sửa Nhà cung cấp'],
            ['name' => 'suppliers.delete', 'display_name' => 'Xóa Nhà cung cấp'],
            
            // Branches
            ['name' => 'branches.view', 'display_name' => 'Xem Chi nhánh'],
            ['name' => 'branches.create', 'display_name' => 'Tạo Chi nhánh'],
            ['name' => 'branches.update', 'display_name' => 'Sửa Chi nhánh'],
            ['name' => 'branches.delete', 'display_name' => 'Xóa Chi nhánh'],
            
            // Pickup Locations (Địa điểm nhận hàng)
            ['name' => 'pickup-locations.view', 'display_name' => 'Xem Địa điểm nhận hàng'],
            ['name' => 'pickup-locations.create', 'display_name' => 'Tạo Địa điểm nhận hàng'],
            ['name' => 'pickup-locations.update', 'display_name' => 'Sửa Địa điểm nhận hàng'],
            ['name' => 'pickup-locations.delete', 'display_name' => 'Xóa Địa điểm nhận hàng'],
            
            // Purchase Invoices
            ['name' => 'purchase-invoices.view', 'display_name' => 'Xem Nhập hàng'],
            ['name' => 'purchase-invoices.create', 'display_name' => 'Tạo Nhập hàng'],
            ['name' => 'purchase-invoices.update', 'display_name' => 'Sửa Nhập hàng'],
            ['name' => 'purchase-invoices.delete', 'display_name' => 'Xóa Nhập hàng'],
            
            // Notifications
            ['name' => 'notifications.view', 'display_name' => 'Xem Thông báo'],
            ['name' => 'notifications.create', 'display_name' => 'Tạo Thông báo'],
            ['name' => 'notifications.update', 'display_name' => 'Sửa Thông báo'],
            ['name' => 'notifications.delete', 'display_name' => 'Xóa Thông báo'],
            
            // Users (Khách hàng)
            ['name' => 'users.view', 'display_name' => 'Xem Khách hàng'],
            ['name' => 'users.create', 'display_name' => 'Tạo Khách hàng'],
            ['name' => 'users.update', 'display_name' => 'Sửa Khách hàng'],
            ['name' => 'users.delete', 'display_name' => 'Xóa Khách hàng'],
            
            // Admin Users (Nhân viên/Admin)
            ['name' => 'admin-users.view', 'display_name' => 'Xem Nhân viên'],
            ['name' => 'admin-users.create', 'display_name' => 'Tạo Nhân viên'],
            ['name' => 'admin-users.update', 'display_name' => 'Sửa Nhân viên'],
            ['name' => 'admin-users.delete', 'display_name' => 'Xóa Nhân viên'],
            
            // Roles
            ['name' => 'roles.view', 'display_name' => 'Xem Phân quyền'],
            ['name' => 'roles.create', 'display_name' => 'Tạo Phân quyền'],
            ['name' => 'roles.update', 'display_name' => 'Sửa Phân quyền'],
            ['name' => 'roles.delete', 'display_name' => 'Xóa Phân quyền'],
        ];

        // Tạo hoặc cập nhật các permission trong CSDL
        foreach ($permissions as $permission) {
            Permission::updateOrCreate(
                ['name' => $permission['name'], 'guard_name' => $guardName],
                ['display_name' => $permission['display_name']]
            );
        }

        // --- TẠO CÁC VAI TRÒ (ROLES) VÀ GÁN QUYỀN ---//

        // 1. QUẢN TRỊ HỆ THỐNG (Super Admin) - TOÀN QUYỀN
        $superAdminRole = Role::updateOrCreate(
            ['name' => 'super-admin', 'guard_name' => $guardName],
            ['display_name' => 'Quản trị hệ thống']
        );
        $superAdminRole->syncPermissions(Permission::where('guard_name', $guardName)->get());

        // 2. QUẢN LÝ CHI NHÁNH (Branch Admin) - ĐA CHI NHÁNH
        $branchAdminRole = Role::updateOrCreate(
            ['name' => 'branch-admin', 'guard_name' => $guardName],
            ['display_name' => 'Quản lý Chi nhánh']
        );
        $branchAdminRole->syncPermissions([
            // Dashboard: Xem (chỉ tại chi nhánh được phân công)
            'dashboard.view',
            
            // Orders: Xem, Thêm, Sửa, Duyệt (KHÔNG có Cancel và Update Payment)
            'orders.view',
            'orders.create',
            'orders.update',
            'orders.update_status',
            
            // Products, Combos, Promotions, Notifications: Toàn quyền
            'products.view', 'products.create', 'products.update', 'products.delete',
            'product-attributes.manage',
            'combos.view', 'combos.create', 'combos.update', 'combos.delete',
            'promotions.view', 'promotions.create', 'promotions.update', 'promotions.delete',
            'notifications.view', 'notifications.create', 'notifications.update', 'notifications.delete',
            
            // Categories, Posts, Sliders: Toàn quyền
            'categories.view', 'categories.create', 'categories.update', 'categories.delete',
            'posts.view', 'posts.create', 'posts.update', 'posts.delete',
            'sliders.view', 'sliders.create', 'sliders.update', 'sliders.delete',
            
            // Users (Khách hàng): Xem, Thêm, Sửa, Xóa
            'users.view', 'users.create', 'users.update', 'users.delete',
            
            // Admin Users (Nhân viên): Xem, Thêm, Sửa (KHÔNG có Delete) - chỉ tại chi nhánh được phân công
            'admin-users.view', 'admin-users.create', 'admin-users.update',
            // Roles: Xem, Tạo, Sửa (KHÔNG có Delete) - quản lý phân quyền nhưng không được xóa
            'roles.view', 'roles.create', 'roles.update',
            
            // Supplier Groups: Xem, Thêm, Sửa (KHÔNG có Delete) - tương tự như Suppliers
            'supplier-groups.view', 'supplier-groups.create', 'supplier-groups.update',
            
            // Suppliers: Xem, Thêm, Sửa (KHÔNG có Delete)
            'suppliers.view', 'suppliers.create', 'suppliers.update',
            
            // Purchase Invoices: Xem, Thêm, Sửa (KHÔNG có Delete)
            'purchase-invoices.view', 'purchase-invoices.create', 'purchase-invoices.update',
            
            // Branches: Chỉ được Xem (không được tạo/sửa/xóa)
            'branches.view',
            
            // Pickup Locations: Toàn quyền (quản lý địa điểm nhận hàng)
            'pickup-locations.view', 'pickup-locations.create', 'pickup-locations.update', 'pickup-locations.delete',
            
            // Payment Methods: Xem
            'payment_methods.view',
        ]);

        // 3. KẾ TOÁN (Accountant) - TOÀN HỆ THỐNG, CHỦ YẾU XEM
        $accountantRole = Role::updateOrCreate(
            ['name' => 'accountant', 'guard_name' => $guardName],
            ['display_name' => 'Kế toán']
        );
        $accountantRole->syncPermissions([
            // Dashboard: Xem (toàn hệ thống)
            'dashboard.view',
            
            // Orders: Chỉ được Xem
            'orders.view',
            
            // Products, Combos, Promotions, Notifications: Chỉ được Xem
            'products.view',
            'combos.view',
            'promotions.view',
            'notifications.view',
            
            // Categories, Posts, Sliders: Chỉ được Xem
            'categories.view',
            'posts.view',
            'sliders.view',
            
            // Users (Khách hàng): Chỉ được Xem
            'users.view',
            
            // Supplier Groups: Xem và Thêm (tương tự như Suppliers)
            'supplier-groups.view', 'supplier-groups.create',
            
            // Suppliers: Xem và Thêm
            'suppliers.view', 'suppliers.create',
            
            // Purchase Invoices: Chỉ được Xem
            'purchase-invoices.view',
            
            // Branches: Chỉ được Xem
            'branches.view',
            
            // Pickup Locations: Chỉ được Xem
            'pickup-locations.view',
            
            // Payment Methods: Xem
            'payment_methods.view',
        ]);

        // 4. NHÂN VIÊN BÁN HÀNG/THU NGÂN (Sales Staff) - 1 CHI NHÁNH
        $salesStaffRole = Role::updateOrCreate(
            ['name' => 'sales-staff', 'guard_name' => $guardName],
            ['display_name' => 'Nhân viên Bán hàng/Thu ngân']
        );
        $salesStaffRole->syncPermissions([
            // Dashboard: Xem
            'dashboard.view',
            
            // Orders: Xem, Thêm, Duyệt (KHÔNG có Update, Cancel, Update Payment)
            'orders.view',
            'orders.create',
            'orders.update_status', // Chỉ duyệt, không sửa thông tin đơn
            
            // Products, Combos, Promotions, Notifications: Chỉ được Xem
            'products.view',
            'combos.view',
            'promotions.view',
            'notifications.view',
            
            // Categories, Posts, Sliders: Chỉ được Xem
            'categories.view',
            'posts.view',
            'sliders.view',
            
            // Users (Khách hàng): Chỉ được Xem
            'users.view',
            
            // Supplier Groups: Chỉ được Xem
            'supplier-groups.view',
            
            // Suppliers: Chỉ được Xem
            'suppliers.view',
            
            // Purchase Invoices: Chỉ được Xem
            'purchase-invoices.view',
            
            // Branches: Chỉ được Xem
            'branches.view',
            
            // Pickup Locations: Chỉ được Xem (để chọn khi tạo đơn)
            'pickup-locations.view',
            
            // Payment Methods: Xem (để chọn khi tạo đơn)
            'payment_methods.view',
        ]);

        // --- TẠO TÀI KHOẢN MẪU VÀ GÁN VAI TRÒ ---
        // 1. Quản trị hệ thống
        $thuyUser = $this->createUser('Ms Thúy', 'thuy@example.com', '0912345001', $superAdminRole);
        $locUser = $this->createUser('Mr Lộc', 'loc@example.com', '0912345002', $superAdminRole);

        // 2. Quản lý chi nhánh (sẽ gán branches sau qua bảng branch_user)
        $taiUser = $this->createUser('Mr Tài', 'tai@example.com', '0912345003', $branchAdminRole);
        $doanhUser = $this->createUser('Mr Doanh', 'doanh@example.com', '0912345004', $branchAdminRole);

        // 3. Kế toán
        $qaUser = $this->createUser('Ms QA', 'qa@example.com', '0912345005', $accountantRole);
        $hongAnhUser = $this->createUser('Ms Hồng Anh', 'honganh@example.com', '0912345006', $accountantRole);

        // 4. Nhân viên bán hàng (cần set branch_id sau)
        $banHangUser = $this->createUser('Ms Bán hàng', 'banhang@example.com', '0912345007', $salesStaffRole);
        // Lưu ý: Sau khi tạo, cần gán branch_id cho nhân viên bán hàng

        // Clear cache sau khi hoàn tất để đảm bảo tất cả thay đổi được áp dụng
        $this->clearPermissionsCache();
    }

    private function createUser(string $name, string $email, string $phone, Role $role): void
    {
        $user = User::where('email', $email)
            ->orWhere('phone', $phone)
            ->withTrashed()
            ->first();

        if ($user) {
            if ($user->trashed()) {
                $user->restore();
                echo "Khôi phục thành công người dùng: $email\n";
            } else {
                echo "Người dùng đã tồn tại: $email, bỏ qua việc tạo mới.\n";
            }
        } else {
            $user = User::create([
                'name'      => $name,
                'email'     => $email,
                'phone'     => $phone,
                'password'  => bcrypt('password123')
            ]);
            echo "Tạo mới thành công người dùng: $email\n";
        }
        $user->syncRoles($role);
    }

    /**
     * Clear cache sau khi hoàn tất seeder để đảm bảo permissions được cập nhật
     */
    private function clearPermissionsCache(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }
}
