<?php

namespace App\Services;

use App\Models\Plan;
use App\Models\User;
use App\Models\UserBillingLog;
use App\Models\UserBillingPeriod;

class UserBillingService
{
    /**
     * Billable roles that require tracking
     */
    const BILLABLE_ROLES = ['manager', 'curator'];

    /**
     * Get or create current billing period for company
     */
    public function getCurrentPeriod(int $companyId): UserBillingPeriod
    {
        $period = UserBillingPeriod::where('company_id', $companyId)
            ->where('status', 'active')
            ->first();

        if (!$period) {
            $company = User::find($companyId);
            $plan = Plan::find($company->plan);
            $period = $this->createNewPeriod($companyId, $plan);
        }

        return $period;
    }

    /**
     * Create new billing period
     */
    public function createNewPeriod(int $companyId, Plan $plan): UserBillingPeriod
    {
        // Count current users by role
        $currentManagers = User::where('created_by', $companyId)
            ->whereHas('roles', fn($q) => $q->where('name', 'manager'))
            ->count();

        $currentCurators = User::where('created_by', $companyId)
            ->whereHas('roles', fn($q) => $q->where('name', 'curator'))
            ->count();

        // Calculate period dates based on plan duration
        $start = now()->startOfDay();
        $end = $plan->duration === 'year'
            ? now()->addYear()->subDay()->endOfDay()
            : now()->addMonth()->subDay()->endOfDay();

        $period = UserBillingPeriod::create([
            'company_id' => $companyId,
            'period_start' => $start,
            'period_end' => $end,
            'current_managers' => $currentManagers,
            'current_curators' => $currentCurators,
            'max_managers_used' => $currentManagers,
            'max_curators_used' => $currentCurators,
            'base_amount' => $plan->price,
            'status' => 'active',
        ]);

        // Calculate initial totals
        $period->calculateTotal($plan);

        return $period;
    }

    /**
     * Check if adding user would exceed limit
     * Limit is TOTAL for managers + curators combined
     */
    public function checkRoleLimit(int $companyId, string $role): array
    {
        if (!in_array($role, self::BILLABLE_ROLES)) {
            return [
                'would_exceed_limit' => false,
                'is_billable_role' => false,
            ];
        }

        $company = User::find($companyId);
        $plan = Plan::find($company->plan);
        $period = $this->getCurrentPeriod($companyId);

        // Total current users (managers + curators combined)
        $totalCurrent = $period->current_managers + $period->current_curators;
        $baseLimit = $plan->getBaseUsersLimit();

        $wouldExceed = ($totalCurrent + 1) > $baseLimit;
        $alreadyOver = $totalCurrent >= $baseLimit;
        $spotsRemaining = max(0, $baseLimit - $totalCurrent);

        return [
            'would_exceed_limit' => $wouldExceed,
            'already_over_limit' => $alreadyOver,
            'is_billable_role' => true,
            'current_count' => $totalCurrent,
            'base_limit' => $baseLimit,
            'spots_remaining' => $spotsRemaining,
            'role_price' => $plan->getRolePrice($role),
            'message' => $wouldExceed
                ? __('Adding this :role will add $:price to your monthly subscription.', [
                    'role' => __($role),
                    'price' => number_format($plan->getRolePrice($role), 2)
                ])
                : null,
        ];
    }

    /**
     * Record user addition
     */
    public function recordUserAdded(int $companyId, int $userId, string $role): void
    {
        if (!in_array($role, self::BILLABLE_ROLES)) {
            return;
        }

        $period = $this->getCurrentPeriod($companyId);
        $period->incrementRole($role);

        UserBillingLog::create([
            'company_id' => $companyId,
            'billing_period_id' => $period->id,
            'user_id' => $userId,
            'action' => UserBillingLog::ACTION_USER_ADDED,
            'role' => $role,
            'details' => "User added as {$role}",
        ]);

        // Recalculate totals
        $plan = Plan::find(User::find($companyId)->plan);
        $period->calculateTotal($plan);
    }

    /**
     * Record user removal (max_used stays the same for anti-abuse!)
     */
    public function recordUserRemoved(int $companyId, int $userId, string $role): void
    {
        if (!in_array($role, self::BILLABLE_ROLES)) {
            return;
        }

        $period = $this->getCurrentPeriod($companyId);
        $period->decrementRole($role);

        UserBillingLog::create([
            'company_id' => $companyId,
            'billing_period_id' => $period->id,
            'user_id' => $userId,
            'action' => UserBillingLog::ACTION_USER_REMOVED,
            'role' => $role,
            'details' => __('User removed. Charge for this period remains fixed.'),
        ]);
    }

    /**
     * Record role change
     */
    public function recordRoleChanged(int $companyId, int $userId, string $oldRole, string $newRole): void
    {
        $period = $this->getCurrentPeriod($companyId);

        // Decrement old role if billable
        if (in_array($oldRole, self::BILLABLE_ROLES)) {
            $period->decrementRole($oldRole);
        }

        // Increment new role if billable
        if (in_array($newRole, self::BILLABLE_ROLES)) {
            $period->incrementRole($newRole);
        }

        UserBillingLog::create([
            'company_id' => $companyId,
            'billing_period_id' => $period->id,
            'user_id' => $userId,
            'action' => UserBillingLog::ACTION_ROLE_CHANGED,
            'role' => $newRole,
            'previous_role' => $oldRole,
            'details' => "Role changed from {$oldRole} to {$newRole}",
        ]);

        // Recalculate totals
        $plan = Plan::find(User::find($companyId)->plan);
        $period->calculateTotal($plan);
    }

    /**
     * Get billing breakdown for dashboard
     * Limit is TOTAL for managers + curators combined
     */
    public function getBillingBreakdown(int $companyId): array
    {
        $company = User::find($companyId);
        $plan = Plan::find($company->plan);
        $period = $this->getCurrentPeriod($companyId);

        $baseLimit = $plan->getBaseUsersLimit();
        
        // Get company currency info
        $managerPriceInfo = \App\Models\Utility::getBillingPriceForCompany('manager', $companyId);
        $curatorPriceInfo = \App\Models\Utility::getBillingPriceForCompany('curator', $companyId);
        $currencySymbol = $managerPriceInfo['symbol'];
        $currency = $managerPriceInfo['currency'];

        // Total users (managers + curators combined)
        $totalCurrent = $period->current_managers + $period->current_curators;
        $totalMaxUsed = $period->max_managers_used + $period->max_curators_used;
        $totalOverLimit = max(0, $totalMaxUsed - $baseLimit);
        $spotsRemaining = max(0, $baseLimit - $totalCurrent);

        // Calculate additional cost based on role prices for users over limit
        // First fill with cheaper curators, then managers
        $managersOverLimit = 0;
        $curatorsOverLimit = 0;
        
        if ($totalOverLimit > 0) {
            // All users over limit pay their role price
            $managersOverLimit = max(0, $period->max_managers_used - min($period->max_managers_used, $baseLimit - $period->max_curators_used));
            $curatorsOverLimit = max(0, $period->max_curators_used - min($period->max_curators_used, $baseLimit - $period->max_managers_used));
            
            // Simpler calculation: if total > limit, excess users pay
            if ($totalMaxUsed > $baseLimit) {
                // Distribute over-limit proportionally or by creation order
                // For simplicity: managers over their "share" pay manager price, curators pay curator price
                $managersOverLimit = max(0, $period->max_managers_used - ($baseLimit / 2));
                $curatorsOverLimit = max(0, $period->max_curators_used - ($baseLimit / 2));
                
                // Recalculate to ensure total matches
                $managersOverLimit = $period->max_managers_used;
                $curatorsOverLimit = $period->max_curators_used;
                $freeSlots = $baseLimit;
                
                // First allocate free slots to curators (cheaper), then managers
                $freeCurators = min($curatorsOverLimit, $freeSlots);
                $freeSlots -= $freeCurators;
                $freeManagers = min($managersOverLimit, $freeSlots);
                
                $managersOverLimit = $period->max_managers_used - $freeManagers;
                $curatorsOverLimit = $period->max_curators_used - $freeCurators;
            }
        }

        // Use converted prices in company currency
        $managerPrice = $managerPriceInfo['price'];
        $curatorPrice = $curatorPriceInfo['price'];
        
        $additionalCost = ($managersOverLimit * $managerPrice) + ($curatorsOverLimit * $curatorPrice);

        return [
            'plan_name' => $plan->name,
            'base_price' => (float) $plan->price,
            'base_limit' => $baseLimit,
            'period_start' => $period->period_start->format('d.m.Y'),
            'period_end' => $period->period_end->format('d.m.Y'),
            // Combined totals
            'total_current' => $totalCurrent,
            'total_max_used' => $totalMaxUsed,
            'total_over_limit' => $totalOverLimit,
            'spots_remaining' => $spotsRemaining,
            // Individual counts (with converted prices)
            'managers' => [
                'current' => $period->current_managers,
                'max_used' => $period->max_managers_used,
                'over_limit' => $managersOverLimit,
                'price_per_user' => $managerPrice,
                'additional_cost' => $managersOverLimit * $managerPrice,
            ],
            'curators' => [
                'current' => $period->current_curators,
                'max_used' => $period->max_curators_used,
                'over_limit' => $curatorsOverLimit,
                'price_per_user' => $curatorPrice,
                'additional_cost' => $curatorsOverLimit * $curatorPrice,
            ],
            'total_additional' => $additionalCost,
            'total_charge' => (float) $plan->price + $additionalCost,
            'status' => $period->status,
            // Currency info
            'currency' => $currency,
            'currency_symbol' => $currencySymbol,
        ];
    }

    /**
     * Get delete warning info for a user
     */
    public function getDeleteWarning(int $companyId, string $role): ?array
    {
        if (!in_array($role, self::BILLABLE_ROLES)) {
            return null;
        }

        $company = User::find($companyId);
        $plan = Plan::find($company->plan);
        $period = $this->getCurrentPeriod($companyId);

        $currentField = "current_{$role}s";
        $maxField = "max_{$role}s_used";
        $baseLimit = $plan->getBaseUsersLimit();

        // Only show warning if this user is over the limit
        if ($period->$maxField <= $baseLimit) {
            return null;
        }

        return [
            'role' => $role,
            'role_price' => $plan->getRolePrice($role),
            'message' => __('This user\'s charge ($:price) is already fixed for the current billing period. Deletion will reduce charges starting from the next period.', [
                'price' => number_format($plan->getRolePrice($role), 2)
            ]),
        ];
    }

    /**
     * Process end of billing period
     */
    public function processEndOfPeriod(int $companyId): array
    {
        $period = $this->getCurrentPeriod($companyId);
        $company = User::find($companyId);
        $plan = Plan::find($company->plan);

        // Calculate final amount
        $period->calculateTotal($plan);
        $period->status = 'pending_payment';
        $period->save();

        return [
            'period_id' => $period->id,
            'total_charge' => (float) $period->total_amount,
            'breakdown' => $this->getBillingBreakdown($companyId),
        ];
    }

    /**
     * Mark period as paid and create new one
     */
    public function markPeriodPaid(int $periodId): UserBillingPeriod
    {
        $period = UserBillingPeriod::find($periodId);
        $period->status = 'paid';
        $period->paid_at = now();
        $period->save();

        // Create new period
        $company = User::find($period->company_id);
        $plan = Plan::find($company->plan);

        return $this->createNewPeriod($period->company_id, $plan);
    }

    /**
     * Get billing history for company
     */
    public function getBillingHistory(int $companyId, int $limit = 12): \Illuminate\Database\Eloquent\Collection
    {
        return UserBillingPeriod::where('company_id', $companyId)
            ->where('status', '!=', 'active')
            ->orderBy('period_end', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Check if role is billable
     */
    public function isBillableRole(string $role): bool
    {
        return in_array($role, self::BILLABLE_ROLES);
    }
}
