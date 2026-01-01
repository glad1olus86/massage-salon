<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CleaningDuty extends Model
{
    use HasFactory;

    protected $fillable = [
        'branch_id',
        'user_id',
        'duty_date',
        'assigned_by',
        'is_manual',
        'is_confirmed',
        'status',
        'completed_at',
        'notes',
    ];

    protected $casts = [
        'duty_date' => 'date',
        'is_manual' => 'boolean',
        'is_confirmed' => 'boolean',
        'completed_at' => 'datetime',
    ];

    /**
     * Get the branch for this duty.
     */
    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Get the assigned user (duty person).
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the user who assigned this duty.
     */
    public function assignedBy()
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    /**
     * Get cleaning statuses for this duty.
     */
    public function cleaningStatuses()
    {
        return $this->hasMany(CleaningStatus::class);
    }

    /**
     * Scope for filtering by branch.
     */
    public function scopeForBranch($query, $branchId)
    {
        return $query->where('branch_id', $branchId);
    }

    /**
     * Scope for filtering by date range.
     */
    public function scopeBetweenDates($query, $startDate, $endDate)
    {
        return $query->whereBetween('duty_date', [$startDate, $endDate]);
    }

    /**
     * Scope for pending duties.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Check if duty is completed.
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Check if duty is confirmed (cannot be auto-reassigned).
     */
    public function isConfirmedDuty(): bool
    {
        return $this->is_confirmed || $this->is_manual || $this->duty_date->lte(now()->endOfWeek());
    }

    /**
     * Scope for unconfirmed duties (can be reassigned).
     */
    public function scopeUnconfirmed($query)
    {
        return $query->where('is_confirmed', false)
            ->where('is_manual', false)
            ->where('duty_date', '>', now()->endOfWeek());
    }
}
