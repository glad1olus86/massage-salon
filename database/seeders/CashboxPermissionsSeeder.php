<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class CashboxPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Cashbox permissions
        $cashboxPermissions = [
            [
                'name' => 'cashbox_access',
                'guard_name' => 'web',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'cashbox_deposit',
                'guard_name' => 'web',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'cashbox_distribute',
                'guard_name' => 'web',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'cashbox_refund',
                'guard_name' => 'web',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'cashbox_self_salary',
                'guard_name' => 'web',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'cashbox_edit_frozen',
                'guard_name' => 'web',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'cashbox_view_audit',
                'guard_name' => 'web',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        // Insert permissions if they don't exist
        foreach ($cashboxPermissions as $permission) {
            Permission::firstOrCreate(
                ['name' => $permission['name'], 'guard_name' => $permission['guard_name']],
                $permission
            );
        }

        // Get all company roles and assign all cashbox permissions (boss level)
        $companyRoles = Role::where('name', 'company')->get();
        $allCashboxPermissions = [
            'cashbox_access',
            'cashbox_deposit',
            'cashbox_distribute',
            'cashbox_refund',
            'cashbox_self_salary',
            'cashbox_edit_frozen',
            'cashbox_view_audit',
        ];

        foreach ($companyRoles as $role) {
            $role->givePermissionTo($allCashboxPermissions);
        }

        // Create cashbox-specific roles for each company
        $companies = User::where('type', 'company')->get();
        
        foreach ($companies as $company) {
            $this->createCashboxRolesForCompany($company->id);
        }
    }

    /**
     * Create cashbox roles (boss, manager, curator) for a specific company
     *
     * @param int $companyId
     * @return void
     */
    public function createCashboxRolesForCompany(int $companyId): void
    {
        // Boss role - all cashbox permissions
        $bossRole = Role::firstOrCreate(
            ['name' => 'boss', 'created_by' => $companyId],
            ['name' => 'boss', 'created_by' => $companyId]
        );
        
        $bossPermissions = [
            'cashbox_access',
            'cashbox_deposit',
            'cashbox_distribute',
            'cashbox_refund',
            'cashbox_self_salary',
            'cashbox_edit_frozen',
            'cashbox_view_audit',
        ];
        
        $bossRole->syncPermissions($bossPermissions);

        // Manager role - can distribute, refund, and take self salary once per period
        $managerRole = Role::firstOrCreate(
            ['name' => 'manager', 'created_by' => $companyId],
            ['name' => 'manager', 'created_by' => $companyId]
        );
        
        $managerPermissions = [
            'cashbox_access',
            'cashbox_distribute',
            'cashbox_refund',
            'cashbox_self_salary',
        ];
        
        $managerRole->syncPermissions($managerPermissions);

        // Curator role - can only distribute to workers and refund
        $curatorRole = Role::firstOrCreate(
            ['name' => 'curator', 'created_by' => $companyId],
            ['name' => 'curator', 'created_by' => $companyId]
        );
        
        $curatorPermissions = [
            'cashbox_access',
            'cashbox_distribute',
            'cashbox_refund',
        ];
        
        $curatorRole->syncPermissions($curatorPermissions);
    }
}
