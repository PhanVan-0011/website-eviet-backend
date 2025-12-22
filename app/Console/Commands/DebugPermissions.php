<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;

class DebugPermissions extends Command
{
    protected $signature = 'debug:permissions {--user-id= : User ID to check}';
    protected $description = 'Debug permissions for roles and users';

    public function handle()
    {
        $this->info('=== DEBUG PERMISSIONS ===');
        $this->newLine();

        // 1. Check roles.* permissions
        $this->info('1. Checking roles.* permissions in database:');
        $rolesPerms = Permission::where('name', 'like', 'roles.%')
            ->where('guard_name', 'api')
            ->get(['name', 'display_name']);
        
        if ($rolesPerms->isEmpty()) {
            $this->error('   ❌ ERROR: No roles.* permissions found in database!');
            $this->warn('   → You need to run: php artisan db:seed --class=RolesAndPermissionsSeeder');
            $this->newLine();
        } else {
            $this->info('   ✅ Found permissions:');
            foreach ($rolesPerms as $p) {
                $this->line("      - {$p->name} ({$p->display_name})");
            }
            $this->newLine();
        }

        // 2. Check branch-admin role
        $this->info('2. Checking branch-admin role:');
        $branchAdmin = Role::where('name', 'branch-admin')
            ->where('guard_name', 'api')
            ->first();
        
        if (!$branchAdmin) {
            $this->error('   ❌ ERROR: branch-admin role not found!');
            $this->newLine();
            return 1;
        }
        
        $this->info("   ✅ branch-admin role found (ID: {$branchAdmin->id})");
        
        // 3. Check branch-admin permissions
        $this->newLine();
        $this->info('3. Checking branch-admin permissions:');
        $branchAdminPerms = $branchAdmin->permissions->pluck('name')->toArray();
        $rolesPermsInBranchAdmin = array_filter($branchAdminPerms, function($p) {
            return strpos($p, 'roles.') === 0;
        });
        
        if (empty($rolesPermsInBranchAdmin)) {
            $this->error('   ❌ ERROR: branch-admin has NO roles.* permissions!');
            $this->warn('   → You need to run seeder to update permissions');
            $this->newLine();
        } else {
            $this->info('   ✅ branch-admin has roles permissions:');
            foreach ($rolesPermsInBranchAdmin as $p) {
                $this->line("      - $p");
            }
            $this->newLine();
            
            if (in_array('roles.view', $branchAdminPerms)) {
                $this->info('   ✅ roles.view permission EXISTS');
            } else {
                $this->error('   ❌ ERROR: roles.view permission MISSING!');
            }
            $this->newLine();
        }

        // 4. Check users
        $userId = $this->option('user-id');
        if ($userId) {
            $user = User::find($userId);
            if (!$user) {
                $this->error("User ID {$userId} not found!");
                return 1;
            }
            $this->checkUserPermissions($user);
        } else {
            $this->info('4. Checking users with branch-admin role:');
            $branchAdminUsers = User::role('branch-admin')->get();
            
            if ($branchAdminUsers->isEmpty()) {
                $this->warn('   ⚠️  WARNING: No users found with branch-admin role');
                $this->newLine();
            } else {
                $this->info("   Found {$branchAdminUsers->count()} user(s):");
                foreach ($branchAdminUsers as $user) {
                    $this->checkUserPermissions($user);
                }
            }
        }

        // 5. Cache suggestion
        $this->info('5. Cache status:');
        $this->line('   → If permissions are correct but user still can\'t access, try:');
        $this->line('      php artisan cache:clear');
        $this->line('      php artisan config:clear');
        $this->line('      Or: app()[\\Spatie\\Permission\\PermissionRegistrar::class]->forgetCachedPermissions();');
        $this->newLine();

        $this->info('=== END DEBUG ===');
        return 0;
    }

    private function checkUserPermissions(User $user)
    {
        $this->line("      - User ID: {$user->id}, Email: {$user->email}");
        
        // Test permission check
        $hasView = $user->can('roles.view');
        $hasCreate = $user->can('roles.create');
        $hasUpdate = $user->can('roles.update');
        $hasDelete = $user->can('roles.delete');
        
        $this->line("         Permissions check:");
        $this->line("           - roles.view: " . ($hasView ? '✅ YES' : '❌ NO'));
        $this->line("           - roles.create: " . ($hasCreate ? '✅ YES' : '❌ NO'));
        $this->line("           - roles.update: " . ($hasUpdate ? '✅ YES' : '❌ NO'));
        $this->line("           - roles.delete: " . ($hasDelete ? '✅ YES' : '❌ NO'));
        
        // Get all permissions
        $allPerms = $user->getAllPermissions()->pluck('name')->toArray();
        $userRolesPerms = array_filter($allPerms, function($p) {
            return strpos($p, 'roles.') === 0;
        });
        $this->line("         All roles.* permissions: " . json_encode(array_values($userRolesPerms)));
        $this->newLine();
    }
}

