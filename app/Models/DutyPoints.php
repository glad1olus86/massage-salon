<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DutyPoints extends Model
{
    use HasFactory;

    protected $fillable = [
        'branch_id',
        'user_id',
        'points',
        'last_duty_date',
        'created_by',
    ];

    protected $casts = [
        'last_duty_date' => 'date',
        'points' => 'integer',
    ];

    /**
     * Get the branch.
     */
    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Get the user.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the creator.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Scope for filtering by branch.
     */
    public function scopeForBranch($query, $branchId)
    {
        return $query->where('branch_id', $branchId);
    }

    /**
     * Scope for active users only.
     */
    public function scopeActiveUsers($query)
    {
        return $query->whereHas('user', function ($q) {
            $q->where('is_active', true);
        });
    }

    /**
     * Add points after duty completion.
     */
    public function addPoints(int $amount = 100): void
    {
        $this->increment('points', $amount);
        $this->update(['last_duty_date' => now()->toDateString()]);
    }
}
