<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Admin có tất cả các quyền
        $adminRoleId = DB::table('roles')->where('name', 'admin')->first()->id;
        $permissions = DB::table('permissions')->get();
        
        foreach ($permissions as $permission) {
            DB::table('role_permissions')->insert([
                'role_id' => $adminRoleId,
                'permission_id' => $permission->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Editor có quyền quản lý slider và xem dashboard
        $editorRoleId = DB::table('roles')->where('name', 'editor')->first()->id;
        $editorPermissions = DB::table('permissions')
            ->whereIn('name', ['manage_sliders', 'view_dashboard'])
            ->get();

        foreach ($editorPermissions as $permission) {
            DB::table('role_permissions')->insert([
                'role_id' => $editorRoleId,
                'permission_id' => $permission->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // User thông thường chỉ có quyền xem dashboard
        $userRoleId = DB::table('roles')->where('name', 'user')->first()->id;
        $viewDashboardPermission = DB::table('permissions')
            ->where('name', 'view_dashboard')
            ->first();

        DB::table('role_permissions')->insert([
            'role_id' => $userRoleId,
            'permission_id' => $viewDashboardPermission->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
} 