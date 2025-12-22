<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;

echo "=== DEBUG PERMISSIONS ===\n\n";

// 1. Kiểm tra permissions roles.* có tồn tại không
echo "1. Checking roles.* permissions in database:\n";
$rolesPerms = Permission::where('name', 'like', 'roles.%')->get(['name', 'display_name']);
if ($rolesPerms->isEmpty()) {
    echo "   ❌ ERROR: No roles.* permissions found in database!\n";
    echo "   → You need to run seeder: php artisan db:seed --class=RolesAndPermissionsSeeder\n\n";
} else {
    echo "   ✅ Found permissions:\n";
    foreach ($rolesPerms as $p) {
        echo "      - {$p->name} ({$p->display_name})\n";
    }
    echo "\n";
}

// 2. Kiểm tra branch-admin role
echo "2. Checking branch-admin role:\n";
$branchAdmin = Role::where('name', 'branch-admin')->first();
if (!$branchAdmin) {
    echo "   ❌ ERROR: branch-admin role not found!\n\n";
} else {
    echo "   ✅ branch-admin role found (ID: {$branchAdmin->id})\n";
    
    // 3. Kiểm tra permissions của branch-admin
    echo "\n3. Checking branch-admin permissions:\n";
    $branchAdminPerms = $branchAdmin->permissions->pluck('name')->toArray();
    $rolesPermsInBranchAdmin = array_filter($branchAdminPerms, function($p) {
        return strpos($p, 'roles.') === 0;
    });
    
    if (empty($rolesPermsInBranchAdmin)) {
        echo "   ❌ ERROR: branch-admin has NO roles.* permissions!\n";
        echo "   → You need to run seeder to update permissions\n\n";
    } else {
        echo "   ✅ branch-admin has roles permissions:\n";
        foreach ($rolesPermsInBranchAdmin as $p) {
            echo "      - $p\n";
        }
        echo "\n";
        
        // Kiểm tra cụ thể roles.view
        if (in_array('roles.view', $branchAdminPerms)) {
            echo "   ✅ roles.view permission EXISTS\n\n";
        } else {
            echo "   ❌ ERROR: roles.view permission MISSING!\n\n";
        }
    }
}

// 4. Kiểm tra user branch-admin
echo "4. Checking users with branch-admin role:\n";
$branchAdminUsers = User::role('branch-admin')->get();
if ($branchAdminUsers->isEmpty()) {
    echo "   ⚠️  WARNING: No users found with branch-admin role\n\n";
} else {
    echo "   Found {$branchAdminUsers->count()} user(s):\n";
    foreach ($branchAdminUsers as $user) {
        echo "      - User ID: {$user->id}, Email: {$user->email}\n";
        
        // Test permission check
        $hasView = $user->can('roles.view');
        $hasCreate = $user->can('roles.create');
        $hasUpdate = $user->can('roles.update');
        $hasDelete = $user->can('roles.delete');
        
        echo "         Permissions check:\n";
        echo "           - roles.view: " . ($hasView ? '✅ YES' : '❌ NO') . "\n";
        echo "           - roles.create: " . ($hasCreate ? '✅ YES' : '❌ NO') . "\n";
        echo "           - roles.update: " . ($hasUpdate ? '✅ YES' : '❌ NO') . "\n";
        echo "           - roles.delete: " . ($hasDelete ? '✅ YES' : '❌ NO') . "\n";
        
        // Get all permissions
        $allPerms = $user->getAllPermissions()->pluck('name')->toArray();
        $userRolesPerms = array_filter($allPerms, function($p) {
            return strpos($p, 'roles.') === 0;
        });
        echo "         All roles.* permissions: " . json_encode(array_values($userRolesPerms)) . "\n\n";
    }
}

// 5. Clear cache suggestion
echo "5. Cache status:\n";
echo "   → If permissions are correct but user still can't access, try:\n";
echo "      php artisan cache:clear\n";
echo "      php artisan config:clear\n";
echo "      Or in code: app()[\\Spatie\\Permission\\PermissionRegistrar::class]->forgetCachedPermissions();\n\n";

echo "=== END DEBUG ===\n";

