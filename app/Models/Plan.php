<?php

namespace App\Models;

use DB;
use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    protected $fillable = [
        'name',
        'price',
        'duration',
        'max_users',
        'max_customers',
        'max_venders',
        'max_clients',
        'trial',
        'trial_days',
        'description',
        'image',
        'crm',
        'hrm',
        'account',
        'project',
        'pos',
        'chatgpt',
        'storage_limit',
        // JOBSI Modules
        'module_workers',
        'module_workplaces',
        'module_hotels',
        'module_vehicles',
        'module_documents',
        'module_cashbox',
        'module_calendar',
        'module_notifications',
        'module_attendance',
        // JOBSI Limits
        'max_workers',
        'max_roles',
        'max_vehicles',
        'max_hotels',
        'max_workplaces',
        'max_document_templates',
        // User Pricing
        'base_users_limit',
        'manager_price',
        'curator_price',
    ];

    private static $getplans = NULL;

    public static $arrDuration = [
        'lifetime' => 'Lifetime',
        'month' => 'Per Month',
        'year' => 'Per Year',
    ];

    public function status()
    {
        return [
            __('lifetime'),
            __('Per Month'),
            __('Per Year'),
        ];
    }

    public static function total_plan()
    {
        return Plan::count();
    }

    public static function most_purchese_plan()
    {
        $free_plan = Plan::where('price', '<=', 0)->first()->id;
        $plan =  User::select(DB::raw('count(*) as total') , 'plan')->where('type', '=', 'company')->where('plan', '!=', $free_plan)->groupBy('plan')->first();

        return $plan;
    }

    public static function getPlan($id)
    {
        if(self::$getplans == null)
        {
            $plan = Plan::find($id);
            self::$getplans = $plan;
        }

        return self::$getplans;
    }

    /**
     * Check if module is enabled in plan
     */
    public function hasModule(string $module): bool
    {
        $field = 'module_' . $module;
        return $this->{$field} ?? true;
    }

    /**
     * Get limit value for a resource (-1 = unlimited)
     */
    public function getLimit(string $resource): int
    {
        $field = 'max_' . $resource;
        return $this->{$field} ?? -1;
    }

    /**
     * Check if user can create more of a resource
     */
    public function canCreate(string $resource, int $currentCount): bool
    {
        $limit = $this->getLimit($resource);
        if ($limit === -1) {
            return true;
        }
        return $currentCount < $limit;
    }

    /**
     * JOBSI modules list for forms
     */
    public static function getJobsiModules(): array
    {
        return [
            'workers' => __('Workers'),
            'workplaces' => __('Workplaces'),
            'hotels' => __('Hotels & Rooms'),
            'vehicles' => __('Vehicles'),
            'documents' => __('Documents'),
            'cashbox' => __('Cashbox'),
            'calendar' => __('Calendar'),
            'notifications' => __('Notifications'),
            'attendance' => __('Attendance'),
        ];
    }

    /**
     * Get manager price per month (from admin settings)
     */
    public function getManagerPrice(): float
    {
        $billingSettings = Utility::getAdminBillingSettings();
        return (float) $billingSettings['manager_price'];
    }

    /**
     * Get curator price per month (from admin settings)
     */
    public function getCuratorPrice(): float
    {
        $billingSettings = Utility::getAdminBillingSettings();
        return (float) $billingSettings['curator_price'];
    }

    /**
     * Get role price converted to company currency
     */
    public function getRolePriceForCompany(string $role, int $companyId): array
    {
        $priceInfo = Utility::getBillingPriceForCompany($role, $companyId);
        return $priceInfo;
    }

    /**
     * Get base users limit for billable roles
     */
    public function getBaseUsersLimit(): int
    {
        return (int) ($this->base_users_limit ?? $this->max_users ?? 3);
    }

    /**
     * Get price for a specific role
     */
    public function getRolePrice(string $role): float
    {
        return match($role) {
            'manager' => $this->getManagerPrice(),
            'curator' => $this->getCuratorPrice(),
            default => 0.00
        };
    }
}
