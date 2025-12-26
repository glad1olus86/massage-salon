<?php

namespace App\Services;

use App\Models\Plan;
use App\Models\User;

class PlanModuleService
{
    /**
     * Список всех JOBSI модулей
     */
    public const MODULES = [
        'workers' => [
            'name' => 'Workers',
            'permissions' => ['manage worker', 'create worker', 'edit worker', 'delete worker', 'show worker'],
        ],
        'workplaces' => [
            'name' => 'Workplaces', 
            'permissions' => ['manage work place', 'create work place', 'edit work place', 'delete work place', 'show work place'],
        ],
        'hotels' => [
            'name' => 'Hotels & Rooms',
            'permissions' => ['manage hotel', 'create hotel', 'edit hotel', 'delete hotel', 'show hotel', 'manage room', 'create room', 'edit room', 'delete room'],
        ],
        'vehicles' => [
            'name' => 'Vehicles',
            'permissions' => ['manage vehicle', 'create vehicle', 'edit vehicle', 'delete vehicle', 'show vehicle'],
        ],
        'documents' => [
            'name' => 'Documents',
            'permissions' => ['manage document template', 'create document template', 'edit document template', 'delete document template'],
        ],
        'cashbox' => [
            'name' => 'Cashbox',
            'permissions' => ['manage cashbox', 'create cashbox', 'edit cashbox', 'delete cashbox', 'view cashbox', 'cashbox deposit', 'cashbox withdraw', 'cashbox transfer', 'cashbox audit'],
        ],
        'calendar' => [
            'name' => 'Calendar',
            'permissions' => ['manage calendar', 'manage event'],
        ],
        'notifications' => [
            'name' => 'Notifications',
            'permissions' => ['manage notification rule', 'create notification rule', 'edit notification rule', 'delete notification rule'],
        ],
        'attendance' => [
            'name' => 'Attendance',
            'permissions' => ['attendance_access', 'attendance_manage_shifts', 'attendance_manage_schedule', 'attendance_mark'],
        ],
    ];

    /**
     * Проверяет есть ли у пользователя/компании доступ к модулю
     */
    public static function hasModule(string $module, $user = null): bool
    {
        $user = $user ?? auth()->user();
        
        if (!$user) {
            return false;
        }

        // Super admin имеет доступ ко всему
        if ($user->type === 'super admin') {
            return true;
        }

        // Получаем план компании
        $planId = $user->plan ?? null;
        
        // Если пользователь не company, получаем план его создателя
        if ($user->type !== 'company') {
            $creator = User::find($user->created_by);
            $planId = $creator ? $creator->plan : null;
        }

        if (!$planId) {
            // Если нет плана - разрешаем доступ (для обратной совместимости)
            return true;
        }

        $plan = Plan::find($planId);
        
        if (!$plan) {
            return true;
        }

        $field = 'module_' . $module;
        
        // Проверяем существует ли поле в плане
        // Если поле не существует (миграция не выполнена) - разрешаем доступ
        if (!isset($plan->$field) && !array_key_exists($field, $plan->getAttributes())) {
            return true;
        }
        
        // Явно проверяем значение поля
        return (bool) $plan->$field;
    }

    /**
     * Получить список доступных модулей для пользователя
     */
    public static function getAvailableModules($user = null): array
    {
        $available = [];
        
        foreach (array_keys(self::MODULES) as $module) {
            if (self::hasModule($module, $user)) {
                $available[] = $module;
            }
        }
        
        return $available;
    }

    /**
     * Получить список недоступных модулей
     */
    public static function getDisabledModules($user = null): array
    {
        $disabled = [];
        
        foreach (array_keys(self::MODULES) as $module) {
            if (!self::hasModule($module, $user)) {
                $disabled[] = $module;
            }
        }
        
        return $disabled;
    }

    /**
     * Получить разрешения которые нужно скрыть (для недоступных модулей)
     */
    public static function getHiddenPermissions($user = null): array
    {
        $hidden = [];
        
        foreach (self::MODULES as $module => $config) {
            if (!self::hasModule($module, $user)) {
                $hidden = array_merge($hidden, $config['permissions']);
            }
        }
        
        return $hidden;
    }

    /**
     * Проверить доступно ли разрешение (не скрыто ли из-за плана)
     */
    public static function isPermissionAvailable(string $permission, $user = null): bool
    {
        $hidden = self::getHiddenPermissions($user);
        return !in_array($permission, $hidden);
    }

    /**
     * Получить план пользователя
     */
    public static function getUserPlan($user = null): ?Plan
    {
        $user = $user ?? auth()->user();
        
        if (!$user) {
            return null;
        }

        $planId = $user->plan ?? null;
        
        if ($user->type !== 'company' && $user->type !== 'super admin') {
            $creator = User::find($user->created_by);
            $planId = $creator ? $creator->plan : null;
        }

        return $planId ? Plan::find($planId) : null;
    }

    /**
     * Получить информацию о модуле
     */
    public static function getModuleInfo(string $module): ?array
    {
        return self::MODULES[$module] ?? null;
    }
}
