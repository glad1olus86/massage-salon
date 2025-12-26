<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Spatie\Permission\Models\Role;
use App\Models\User;

class CleanupUnusedRoles extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'roles:cleanup 
                            {--dry-run : Show what would be deleted without actually deleting}
                            {--role= : Specific role name to remove}
                            {--company= : Specific company ID to clean}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Remove unused roles from companies. By default removes: boss, accountant, client (Employee is kept for HRM)';

    /**
     * Roles that are safe to remove (not used in core functionality)
     */
    protected $safeToRemove = ['boss', 'accountant', 'client'];

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dryRun = $this->option('dry-run');
        $specificRole = $this->option('role');
        $specificCompany = $this->option('company');
        
        if ($dryRun) {
            $this->info('DRY RUN MODE - No changes will be made');
        }

        $rolesToRemove = $specificRole ? [$specificRole] : $this->safeToRemove;
        
        $this->info('Looking for unused roles to remove: ' . implode(', ', $rolesToRemove));
        $this->newLine();

        $totalDeleted = 0;
        $totalSkipped = 0;

        foreach ($rolesToRemove as $roleName) {
            $query = Role::where('name', $roleName);
            
            if ($specificCompany) {
                $query->where('created_by', $specificCompany);
            }
            
            $roles = $query->get();
            
            if ($roles->isEmpty()) {
                $this->line("No roles found with name '{$roleName}'");
                continue;
            }
            
            foreach ($roles as $role) {
                // Check if any users have this role
                $usersWithRole = User::whereHas('roles', function($q) use ($role) {
                    $q->where('roles.id', $role->id);
                })->count();
                
                if ($usersWithRole > 0) {
                    $this->warn("  ⚠ Role '{$roleName}' (ID: {$role->id}, Company: {$role->created_by}) has {$usersWithRole} users - skipping");
                    $totalSkipped++;
                    continue;
                }

                if ($dryRun) {
                    $this->line("  → Would delete role '{$roleName}' (ID: {$role->id}, Company: {$role->created_by})");
                } else {
                    $role->delete();
                    $this->info("  ✓ Deleted role '{$roleName}' (ID: {$role->id}, Company: {$role->created_by})");
                }
                $totalDeleted++;
            }
        }

        $this->newLine();
        if ($dryRun) {
            $this->info("DRY RUN Summary: Would delete {$totalDeleted} roles, skipped {$totalSkipped} roles with users");
        } else {
            $this->info("Summary: Deleted {$totalDeleted} roles, skipped {$totalSkipped} roles with users");
        }

        return Command::SUCCESS;
    }
}
