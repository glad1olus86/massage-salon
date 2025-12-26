<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class AddCashboxViewPermissions extends Command
{
    protected $signature = 'permissions:add-all-custom';
    protected $description = 'Add all custom permissions to company role';

    public function handle()
    {
        $permissions = [
            'cashbox_view_manager', 
            'cashbox_view_curator',
            'cashbox_view_boss',
            'document_template_read',
            'document_template_create',
            'document_template_edit',
            'document_template_delete',
            'document_generate',
            'vehicle_read',
            'vehicle_create',
            'vehicle_edit',
            'vehicle_delete',
        ];
        
        // Add to company role
        $companyRole = Role::where('name', 'company')->first();
        if ($companyRole) {
            foreach ($permissions as $permName) {
                $perm = Permission::where('name', $permName)->first();
                if ($perm && !$companyRole->hasPermissionTo($permName)) {
                    $companyRole->givePermissionTo($perm);
                    $this->info("Added {$permName} to company role");
                }
            }
        }
        
        // Also add to super admin if exists
        $superAdminRole = Role::where('name', 'super admin')->first();
        if ($superAdminRole) {
            foreach ($permissions as $permName) {
                $perm = Permission::where('name', $permName)->first();
                if ($perm && !$superAdminRole->hasPermissionTo($permName)) {
                    $superAdminRole->givePermissionTo($perm);
                    $this->info("Added {$permName} to super admin role");
                }
            }
        }
        
        $this->info('Done!');
    }
}
