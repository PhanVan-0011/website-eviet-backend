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
            // Quản lý dashboard
            ['name' => 'dashboard.view', 'display_name' => 'Xem Dashboard tổng quan'],

            // Quản lý Đơn hàng
            ['name' => 'orders.view', 'display_name' => 'Xem Đơn hàng'],
            ['name' => 'orders.create', 'display_name' => 'Tạo Đơn hàng'],
            ['name' => 'orders.update', 'display_name' => 'Sửa Đơn hàng'],
            ['name' => 'orders.update_status', 'display_name' => 'Duyệt/Cập nhật trạng thái Đơn hàng'],
            ['name' => 'orders.cancel', 'display_name' => 'Hủy Đơn hàng'],
            ['name' => 'orders.update_payment', 'display_name' => 'Cập nhật trạng thái Thanh toán'],

            // Quản lý Sản phẩm
            ['name' => 'products.view', 'display_name' => 'Xem chi tiết Sản phẩm (Giá, tồn kho...)'],
            ['name' => 'products.create', 'display_name' => 'Tạo mới Sản phẩm'],
            ['name' => 'products.update', 'display_name' => 'Cập nhật Sản phẩm'],
            ['name' => 'products.delete', 'display_name' => 'Xóa Sản phẩm'],

            // Quản lý danh mục
            ['name' => 'categories.view', 'display_name' => 'Xem chi tiết Danh mục'],
            ['name' => 'categories.manage', 'display_name' => 'Quản lý Danh mục (Thêm/Sửa/Xóa)'],

            // Quản lý Combo
            ['name' => 'combos.view', 'display_name' => 'Xem chi tiết Combo'],
            ['name' => 'combos.manage', 'display_name' => 'Quản lý Combo (Thêm/Sửa/Xóa)'],

            // Quản lý Khuyến mãi
            ['name' => 'promotions.view', 'display_name' => 'Xem chi tiết Khuyến mãi'],
            ['name' => 'promotions.create', 'display_name' => 'Tạo Khuyến mãi'],
            ['name' => 'promotions.update', 'display_name' => 'Sửa Khuyến mãi'],
            ['name' => 'promotions.delete', 'display_name' => 'Xóa Khuyến mãi'],

            // Quản lý Slider
            ['name' => 'sliders.view', 'display_name' => 'Xem Slider'],
            ['name' => 'sliders.manage', 'display_name' => 'Quản lý Slider (Thêm/Sửa/Xóa)'],

            // Quản lý bài viết
            ['name' => 'posts.view', 'display_name' => 'Xem Bài viết'],
            ['name' => 'posts.manage', 'display_name' => 'Quản lý Bài viết (Thêm/Sửa/Xóa)'],

            // Quản lý phương thức thanh toán
            ['name' => 'payment_methods.view', 'display_name' => 'Xem phương thức thanh toán'],
            ['name' => 'payment_methods.manage', 'display_name' => 'Quản lý phương thức thanh toán'],

            // Quản lý Người dùng & Vai trò
            ['name' => 'users.manage', 'display_name' => 'Quản lý Người dùng'],
            ['name' => 'roles.manage', 'display_name' => 'Quản lý Phân quyền'],
        ];

        // Tạo hoặc cập nhật các permission trong CSDL
        foreach ($permissions as $permission) {
            Permission::updateOrCreate(
                ['name' => $permission['name'], 'guard_name' => $guardName],
                ['display_name' => $permission['display_name']]
            );
        }

        // --- TẠO CÁC VAI TRÒ (ROLES) VÀ GÁN QUYỀN ---//

        $supportRole = Role::updateOrCreate(
            ['name' => 'support-staff', 'guard_name' => $guardName],
            ['display_name' => 'Nhân viên Hỗ trợ']
        );
        $supportRole->syncPermissions(['orders.view']);

        $editorRole = Role::updateOrCreate(
            ['name' => 'content-editor', 'guard_name' => $guardName],
            ['display_name' => 'Biên tập viên']
        );
        $editorRole->syncPermissions([
            'sliders.manage',
            'posts.manage',
        ]);

        $productManagerRole = Role::updateOrCreate(
            ['name' => 'product-manager', 'guard_name' => $guardName],
            ['display_name' => 'Quản lý Sản phẩm']
        );
        $productManagerRole->syncPermissions(['products.view', 'products.create', 'products.update', 'products.delete', 'categories.manage', 'combos.manage']);

        $salesManagerRole = Role::updateOrCreate(
            ['name' => 'sales-manager', 'guard_name' => $guardName],
            ['display_name' => 'Quản lý Bán hàng']
        );
        $salesManagerRole->syncPermissions([
            'orders.view',
            'orders.create',
            'orders.update',
            'orders.update_status',
            'orders.cancel',
            'orders.update_payment',
            'promotions.view',
            'promotions.create',
            'promotions.update',
            'promotions.delete',
            'dashboard.view',
            'payment_methods.view',
        ]);

        $superAdminRole = Role::updateOrCreate(
            ['name' => 'super-admin', 'guard_name' => $guardName],
            ['display_name' => 'Super Admin']
        );
        $superAdminRole->syncPermissions(Permission::where('guard_name', $guardName)->get());

        // --- TẠO TÀI KHOẢN MẪU VÀ GÁN VAI TRÒ --- 
        $this->createUser('Super Admin', 'superadmin@example.com', '0912345675', $superAdminRole);
        $this->createUser('Sales Manager', 'sales@example.com', '0912345676', $salesManagerRole);
        $this->createUser('Product Manager', 'product@example.com', '0912345677', $productManagerRole);
        $this->createUser('Content Editor', 'editor@example.com', '0912345678', $editorRole);
        $this->createUser('Support Staff', 'support@example.com', '0912345679', $supportRole);
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
}