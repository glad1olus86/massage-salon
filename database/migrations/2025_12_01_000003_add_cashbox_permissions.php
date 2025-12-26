<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Cashbox permissions
        $cashboxPermissions = [
            'cashbox_access',
            'cashbox_deposit',
            'cashbox_distribute',
            'cashbox_refund',
            'cashbox_self_salary',
            'cashbox_edit_frozen',
            'cashbox_view_audit',
        ];

        // Insert permissions if they don't exist
        foreach ($cashboxPermissions as $permissionName) {
            Permission::firstOrCreate(
                ['name' => $permissionName, 'guard_name' => 'web'],
                ['name' => $permissionName, 'guard_name' => 'web']
            );
        }

        // Assign all cashbox permissions to existing company roles
        $companyRoles = Role::where('name', 'company')->get();
        foreach ($companyRoles as $role) {
            $role->givePermissionTo($cashboxPermissions);
        }

        // Create cashbox roles for each existing company
        $companies = User::where('type', 'company')->get();
        
        foreach ($companies as $company) {
            // Boss role - all cashbox permissions
            $bossRole = Role::firstOrCreate(
                ['name' => 'boss', 'created_by' => $company->id],
                ['name' => 'boss', 'created_by' => $company->id]
            );
            $bossRole->syncPermissions($cashboxPermissions);

            // Manager role - can distribute, refund, and take self salary
            $managerRole = Role::firstOrCreate(
                ['name' => 'manager', 'created_by' => $company->id],
                ['name' => 'manager', 'created_by' => $company->id]
            );
            $managerRole->syncPermissions([
                'cashbox_access',
                'cashbox_distribute',
                'cashbox_refund',
                'cashbox_self_salary',
            ]);

            // Curator role - can only distribute to workers and refund
            $curatorRole = Role::firstOrCreate(
                ['name' => 'curator', 'created_by' => $company->id],
                ['name' => 'curator', 'created_by' => $company->id]
            );
            $curatorRole->syncPermissions([
                'cashbox_access',
                'cashbox_distribute',
                'cashbox_refund',
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove cashbox permissions
        $cashboxPermissions = [
            'cashbox_access',
            'cashbox_deposit',
            'cashbox_distribute',
            'cashbox_refund',
            'cashbox_self_salary',
            'cashbox_edit_frozen',
            'cashbox_view_audit',
        ];

        // Delete permissions
        Permission::whereIn('name', $cashboxPermissions)->delete();

        // Delete cashbox roles
        Role::whereIn('name', ['boss', 'manager', 'curator'])->delete();
    }
};
