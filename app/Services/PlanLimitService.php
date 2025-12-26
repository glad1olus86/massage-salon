<?php

namespace App\Services;

use App\Models\Plan;
use App\Models\User;
use App\Models\DocumentTemplate;
use App\Models\Worker;
use App\Models\Vehicle;
use App\Models\Hotel;
use App\Models\WorkPlace;
use Spatie\Permission\Models\Role;

class PlanLimitService
{
    /**
     * Get current user's plan
     */
    public static function getUserPlan(?User $user = null): ?Plan
    {
        $user = $user ?? auth()->user();
        
        if (!$user) {
            return null;
        }
        
        // Get company owner for non-company users
        if ($user->type !== 'company' && $user->type !== 'super admin') {
            $companyOwner = User::find($user->created_by);
            if ($companyOwner) {
                $user = $companyOwner;
            }
        }
        
        if (!$user->plan) {
            return null;
        }
        
        return Plan::find($user->plan);
    }
    
    /**
     * Check if user can create more document templates
     */
    public static function canCreateDocumentTemplate(?User $user = null): bool
    {
        $user = $user ?? auth()->user();
        
        // Super admin always can
        if ($user && $user->type === 'super admin') {
            return true;
        }
        
        $plan = self::getUserPlan($user);
        
        if (!$plan) {
            return false;
        }
        
        $limit = $plan->max_document_templates ?? -1;
        
        // -1 means unlimited
        if ($limit === -1) {
            return true;
        }
        
        // Count current templates
        $creatorId = $user->type === 'company' ? $user->id : $user->created_by;
        $currentCount = DocumentTemplate::where('created_by', $creatorId)->count();
        
        return $currentCount < $limit;
    }
    
    /**
     * Get remaining document templates count
     */
    public static function getRemainingDocumentTemplates(?User $user = null): int
    {
        $user = $user ?? auth()->user();
        $plan = self::getUserPlan($user);
        
        if (!$plan) {
            return 0;
        }
        
        $limit = $plan->max_document_templates ?? -1;
        
        if ($limit === -1) {
            return -1; // unlimited
        }
        
        $creatorId = $user->type === 'company' ? $user->id : $user->created_by;
        $currentCount = DocumentTemplate::where('created_by', $creatorId)->count();
        
        return max(0, $limit - $currentCount);
    }
    
    /**
     * Check if module is enabled in user's plan
     */
    public static function hasModuleAccess(string $module, ?User $user = null): bool
    {
        $user = $user ?? auth()->user();
        
        // Super admin always has access
        if ($user && $user->type === 'super admin') {
            return true;
        }
        
        $plan = self::getUserPlan($user);
        
        if (!$plan) {
            return false;
        }
        
        $moduleField = 'module_' . $module;
        
        // If field doesn't exist, allow access (migration not run yet)
        if (!isset($plan->{$moduleField})) {
            return true;
        }
        
        return $plan->{$moduleField} == 1;
    }
    
    /**
     * Get creator ID for current user (company owner ID)
     */
    public static function getCreatorId(?User $user = null): int
    {
        $user = $user ?? auth()->user();
        return $user->type === 'company' ? $user->id : $user->created_by;
    }

    /**
     * Check if user can create more workers
     */
    public static function canCreateWorker(?User $user = null): bool
    {
        return self::canCreate('workers', Worker::class, $user);
    }

    /**
     * Check if user can create more vehicles
     */
    public static function canCreateVehicle(?User $user = null): bool
    {
        return self::canCreate('vehicles', Vehicle::class, $user);
    }

    /**
     * Check if user can create more hotels
     */
    public static function canCreateHotel(?User $user = null): bool
    {
        return self::canCreate('hotels', Hotel::class, $user);
    }

    /**
     * Check if user can create more workplaces
     */
    public static function canCreateWorkplace(?User $user = null): bool
    {
        return self::canCreate('workplaces', WorkPlace::class, $user);
    }

    /**
     * Check if user can create more roles
     */
    public static function canCreateRole(?User $user = null): bool
    {
        $user = $user ?? auth()->user();
        
        if ($user && $user->type === 'super admin') {
            return true;
        }
        
        $plan = self::getUserPlan($user);
        
        if (!$plan) {
            return false;
        }
        
        $limit = $plan->max_roles ?? -1;
        
        if ($limit === -1) {
            return true;
        }
        
        $creatorId = self::getCreatorId($user);
        $currentCount = Role::where('created_by', $creatorId)->count();
        
        return $currentCount < $limit;
    }

    /**
     * Generic check if user can create more of a resource
     */
    protected static function canCreate(string $resource, string $modelClass, ?User $user = null): bool
    {
        $user = $user ?? auth()->user();
        
        if ($user && $user->type === 'super admin') {
            return true;
        }
        
        $plan = self::getUserPlan($user);
        
        if (!$plan) {
            return false;
        }
        
        $limitField = 'max_' . $resource;
        $limit = $plan->{$limitField} ?? -1;
        
        if ($limit === -1) {
            return true;
        }
        
        $creatorId = self::getCreatorId($user);
        $currentCount = $modelClass::where('created_by', $creatorId)->count();
        
        return $currentCount < $limit;
    }

    /**
     * Get remaining count for a resource
     */
    public static function getRemaining(string $resource, string $modelClass, ?User $user = null): int
    {
        $user = $user ?? auth()->user();
        $plan = self::getUserPlan($user);
        
        if (!$plan) {
            return 0;
        }
        
        $limitField = 'max_' . $resource;
        $limit = $plan->{$limitField} ?? -1;
        
        if ($limit === -1) {
            return -1;
        }
        
        $creatorId = self::getCreatorId($user);
        $currentCount = $modelClass::where('created_by', $creatorId)->count();
        
        return max(0, $limit - $currentCount);
    }

    /**
     * Get plan limit info for display
     */
    public static function getLimitInfo(?User $user = null): array
    {
        $plan = self::getUserPlan($user);
        
        if (!$plan) {
            return [
                'document_templates' => ['limit' => 0, 'used' => 0, 'remaining' => 0],
                'workers' => ['limit' => 0, 'used' => 0, 'remaining' => 0],
                'vehicles' => ['limit' => 0, 'used' => 0, 'remaining' => 0],
                'hotels' => ['limit' => 0, 'used' => 0, 'remaining' => 0],
                'workplaces' => ['limit' => 0, 'used' => 0, 'remaining' => 0],
                'roles' => ['limit' => 0, 'used' => 0, 'remaining' => 0],
            ];
        }
        
        $user = $user ?? auth()->user();
        $creatorId = self::getCreatorId($user);
        
        return [
            'document_templates' => self::buildLimitArray($plan->max_document_templates ?? -1, DocumentTemplate::where('created_by', $creatorId)->count()),
            'workers' => self::buildLimitArray($plan->max_workers ?? -1, Worker::where('created_by', $creatorId)->count()),
            'vehicles' => self::buildLimitArray($plan->max_vehicles ?? -1, Vehicle::where('created_by', $creatorId)->count()),
            'hotels' => self::buildLimitArray($plan->max_hotels ?? -1, Hotel::where('created_by', $creatorId)->count()),
            'workplaces' => self::buildLimitArray($plan->max_workplaces ?? -1, WorkPlace::where('created_by', $creatorId)->count()),
            'roles' => self::buildLimitArray($plan->max_roles ?? -1, Role::where('created_by', $creatorId)->count()),
        ];
    }

    /**
     * Build limit array for display
     */
    protected static function buildLimitArray(int $limit, int $used): array
    {
        return [
            'limit' => $limit,
            'used' => $used,
            'remaining' => $limit === -1 ? -1 : max(0, $limit - $used),
        ];
    }
}
