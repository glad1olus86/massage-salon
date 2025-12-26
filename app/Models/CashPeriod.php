<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CashPeriod extends Model
{
    use HasFactory;

    protected $fillable = [
        'created_by',
        'year',
        'month',
        'total_deposited',
        'is_frozen',
    ];

    protected $casts = [
        'total_deposited' => 'decimal:2',
        'is_frozen' => 'boolean',
    ];

    /**
     * Get the company that owns this period.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get all transactions for this period.
     */
    public function transactions()
    {
        return $this->hasMany(CashTransaction::class);
    }

    /**
     * Scope to filter by company.
     */
    public function scopeForCompany($query, $companyId)
    {
        return $query->where('created_by', $companyId);
    }

    /**
     * Check if period is frozen.
     */
    public function isFrozen(): bool
    {
        return $this->is_frozen;
    }

    /**
     * Get formatted period name (e.g., "December 2025").
     */
    public function getNameAttribute(): string
    {
        $months = [
            1 => __('January'), 2 => __('February'), 3 => __('March'),
            4 => __('April'), 5 => __('May'), 6 => __('June'),
            7 => __('July'), 8 => __('August'), 9 => __('September'),
            10 => __('October'), 11 => __('November'), 12 => __('December'),
        ];

        return ($months[$this->month] ?? $this->month) . ' ' . $this->year;
    }
}
