<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class CustomModulesPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Adds permissions for custom modules: worker, hotel, room, work place, cashbox, audit, notifications
     */
    public function run()
    {
        $permissions = [
            // Worker permissions
            ['name' => 'manage worker', 'guard_name' => 'web'],
            ['name' => 'create worker', 'guard_name' => 'web'],
            ['name' => 'edit worker', 'guard_name' => 'web'],
            ['name' => 'delete worker', 'guard_name' => 'web'],
            ['name' => 'show worker', 'guard_name' => 'web'],
            ['name' => 'export worker', 'guard_name' => 'web'],
            
            // Hotel permissions
            ['name' => 'manage hotel', 'guard_name' => 'web'],
            ['name' => 'create hotel', 'guard_name' => 'web'],
            ['name' => 'edit hotel', 'guard_name' => 'web'],
            ['name' => 'delete hotel', 'guard_name' => 'web'],
            ['name' => 'show hotel', 'guard_name' => 'web'],
            ['name' => 'export hotel', 'guard_name' => 'web'],
            
            // Room permissions
            ['name' => 'manage room', 'guard_name' => 'web'],
            ['name' => 'create room', 'guard_name' => 'web'],
            ['name' => 'edit room', 'guard_name' => 'web'],
            ['name' => 'delete room', 'guard_name' => 'web'],
            ['name' => 'show room', 'guard_name' => 'web'],
            
            // Work Place permissions
            ['name' => 'manage work place', 'guard_name' => 'web'],
            ['name' => 'create work place', 'guard_name' => 'web'],
            ['name' => 'edit work place', 'guard_name' => 'web'],
            ['name' => 'delete work place', 'guard_name' => 'web'],
            ['name' => 'show work place', 'guard_name' => 'web'],
            ['name' => 'export work place', 'guard_name' => 'web'],
            
            // Room Assignment permissions
            ['name' => 'manage room assignment', 'guard_name' => 'web'],
            ['name' => 'create room assignment', 'guard_name' => 'web'],
            ['name' => 'delete room assignment', 'guard_name' => 'web'],
            
            // Work Assignment permissions
            ['name' => 'manage work assignment', 'guard_name' => 'web'],
            ['name' => 'create work assignment', 'guard_name' => 'web'],
            ['name' => 'delete work assignment', 'guard_name' => 'web'],
            
            // Notification Rules permissions
            ['name' => 'manage notification rule', 'guard_name' => 'web'],
            ['name' => 'create notification rule', 'guard_name' => 'web'],
            ['name' => 'edit notification rule', 'guard_name' => 'web'],
            ['name' => 'delete notification rule', 'guard_name' => 'web'],
            
            // Audit Log permissions
            ['name' => 'manage audit log', 'guard_name' => 'web'],
            ['name' => 'view audit log', 'guard_name' => 'web'],
            
            // Cashbox permissions (if not already added)
            ['name' => 'cashbox_access', 'guard_name' => 'web'],
            ['name' => 'cashbox_deposit', 'guard_name' => 'web'],
            ['name' => 'cashbox_distribute', 'guard_name' => 'web'],
            ['name' => 'cashbox_refund', 'guard_name' => 'web'],
            ['name' => 'cashbox_self_salary', 'guard_name' => 'web'],
            ['name' => 'cashbox_edit_frozen', 'guard_name' => 'web'],
            ['name' => 'cashbox_view_audit', 'guard_name' => 'web'],
            
            // Attendance permissions
            ['name' => 'attendance_access', 'guard_name' => 'web'],
            ['name' => 'attendance_manage_shifts', 'guard_name' => 'web'],
            ['name' => 'attendance_manage_schedule', 'guard_name' => 'web'],
            ['name' => 'attendance_mark', 'guard_name' => 'web'],
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(
                ['name' => $permission['name'], 'guard_name' => $permission['guard_name']]
            );
        }

        // Assign all new permissions to company role
        $companyRoles = Role::where('name', 'company')->get();
        
        $companyPermissions = [
            'manage worker', 'create worker', 'edit worker', 'delete worker', 'show worker', 'export worker',
            'manage hotel', 'create hotel', 'edit hotel', 'delete hotel', 'show hotel', 'export hotel',
            'manage room', 'create room', 'edit room', 'delete room', 'show room',
            'manage work place', 'create work place', 'edit work place', 'delete work place', 'show work place', 'export work place',
            'manage room assignment', 'create room assignment', 'delete room assignment',
            'manage work assignment', 'create work assignment', 'delete work assignment',
            'manage notification rule', 'create notification rule', 'edit notification rule', 'delete notification rule',
            'manage audit log', 'view audit log',
            'cashbox_access', 'cashbox_deposit', 'cashbox_distribute', 'cashbox_refund', 
            'cashbox_self_salary', 'cashbox_edit_frozen', 'cashbox_view_audit',
            'attendance_access', 'attendance_manage_shifts', 'attendance_manage_schedule', 'attendance_mark',
        ];

        foreach ($companyRoles as $role) {
            $role->givePermissionTo($companyPermissions);
        }

        $this->command->info('Custom modules permissions added successfully!');
    }
}
