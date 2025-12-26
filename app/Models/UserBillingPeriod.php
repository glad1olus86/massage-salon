<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserBillingPeriod extends Model
{
    protected $fillable = [
        'company_id',
        'period_start',
        'period_end',
        'current_managers',
        'current_curators',
        'max_managers_used',
        'max_curators_used',
        'base_amount',
        'additional_amount',
        'total_amount',
        'status',
        'paid_at',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'paid_at' => 'datetime',
        'base_amount' => 'decimal:2',
        'additional_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
    ];

    /**
     * Get the company that owns this billing period
     */
    public function company()
    {
        return $this->belongsTo(User::class, 'company_id');
    }

    /**
     * Get all logs for this billing period
     */
    public function logs()
    {
        return $this->hasMany(UserBillingLog::class, 'billing_period_id');
    }

    /**
     * Increment role count and update max_used if needed
     */
    public function incrementRole(string $role): void
    {
        $currentField = "current_{$role}s";
        $maxField = "max_{$role}s_used";

        $this->$currentField++;
        
        // Update max_used if current exceeds it (anti-abuse protection)
        if ($this->$currentField > $this->$maxField) {
            $this->$maxField = $this->$currentField;
        }
        
        $this->save();
    }

    /**
     * Decrement role count (max_used stays the same for anti-abuse)
     */
    public function decrementRole(string $role): void
    {
        $currentField = "current_{$role}s";
        $this->$currentField = max(0, $this->$currentField - 1);
        // max_used НЕ уменьшается - это защита от абьюза!
        $this->save();
    }

    /**
     * Calculate total billing amount based on plan pricing
     * Limit is TOTAL for managers + curators combined
     */
    public function calculateTotal(Plan $plan): void
    {
        $baseLimit = $plan->getBaseUsersLimit();
        $totalMaxUsed = $this->max_managers_used + $this->max_curators_used;
        
        // Calculate how many users are over the combined limit
        $totalOver = max(0, $totalMaxUsed - $baseLimit);
        
        // Distribute free slots: first to curators (cheaper), then managers
        $freeSlots = $baseLimit;
        $freeCurators = min($this->max_curators_used, $freeSlots);
        $freeSlots -= $freeCurators;
        $freeManagers = min($this->max_managers_used, $freeSlots);
        
        $managersOver = $this->max_managers_used - $freeManagers;
        $curatorsOver = $this->max_curators_used - $freeCurators;

        $this->base_amount = $plan->price;
        $this->additional_amount = ($managersOver * $plan->getManagerPrice())
                                 + ($curatorsOver * $plan->getCuratorPrice());
        $this->total_amount = $this->base_amount + $this->additional_amount;
        
        $this->save();
    }

    /**
     * Check if period is active
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Get managers over limit count
     */
    public function getManagersOverLimit(int $baseLimit): int
    {
        return max(0, $this->max_managers_used - $baseLimit);
    }

    /**
     * Get curators over limit count
     */
    public function getCuratorsOverLimit(int $baseLimit): int
    {
        return max(0, $this->max_curators_used - $baseLimit);
    }
}
