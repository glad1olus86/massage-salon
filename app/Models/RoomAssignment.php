<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RoomAssignment extends Model
{
    use HasFactory;

    // Payment types
    const PAYMENT_AGENCY = 'agency';  // Agency pays (default)
    const PAYMENT_WORKER = 'worker';  // Worker pays themselves
    const PAYMENT_USER = 'user';      // User (masseuse) pays

    // Status types
    const STATUS_ACTIVE = 'active';
    const STATUS_COMPLETED = 'completed';
    const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'worker_id',
        'user_id',
        'room_id',
        'branch_id',
        'hotel_id',
        'check_in_date',
        'check_out_date',
        'payment_type',
        'payment_amount',
        'notes',
        'status',
        'created_by',
    ];

    protected $casts = [
        'check_in_date' => 'date',
        'check_out_date' => 'date',
        'payment_amount' => 'decimal:2',
    ];

    /**
     * Get payment type options for forms.
     */
    public static function getPaymentTypes(): array
    {
        return [
            self::PAYMENT_AGENCY => __('Agency pays'),
            self::PAYMENT_WORKER => __('Worker pays'),
            self::PAYMENT_USER => __('User pays'),
        ];
    }

    /**
     * Get status options for forms.
     */
    public static function getStatuses(): array
    {
        return [
            self::STATUS_ACTIVE => __('Active'),
            self::STATUS_COMPLETED => __('Completed'),
            self::STATUS_CANCELLED => __('Cancelled'),
        ];
    }

    /**
     * Check if worker pays for accommodation.
     */
    public function workerPays(): bool
    {
        return $this->payment_type === self::PAYMENT_WORKER;
    }

    /**
     * Get formatted payment info string.
     */
    public function getPaymentInfoAttribute(): string
    {
        if ($this->payment_type === self::PAYMENT_AGENCY) {
            return __('Agency pays');
        }
        
        if ($this->payment_amount) {
            return __('Worker pays') . ': ' . formatCashboxCurrency($this->payment_amount);
        }
        
        return __('Worker pays');
    }

    /**
     * Get payment history for this assignment.
     */
    public function paymentHistory()
    {
        return $this->hasMany(RoomAssignmentPaymentHistory::class)->orderBy('created_at', 'desc');
    }

    /**
     * Get the worker for this assignment (legacy support).
     */
    public function worker()
    {
        return $this->belongsTo(Worker::class);
    }

    /**
     * Get the user (masseuse) for this assignment.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the assignee (user or worker).
     */
    public function getAssigneeAttribute()
    {
        return $this->user ?? $this->worker;
    }

    /**
     * Get the room for this assignment.
     */
    public function room()
    {
        return $this->belongsTo(Room::class);
    }

    /**
     * Get the branch for this assignment.
     */
    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Get the hotel for this assignment (legacy support).
     */
    public function hotel()
    {
        return $this->belongsTo(Hotel::class);
    }

    /**
     * Scope to get only active assignments (not checked out).
     */
    public function scopeActive($query)
    {
        return $query->whereNull('check_out_date')->where('status', self::STATUS_ACTIVE);
    }

    /**
     * Scope to get assignments for a specific worker.
     */
    public function scopeForWorker($query, $workerId)
    {
        return $query->where('worker_id', $workerId);
    }

    /**
     * Scope to get assignments for a specific user.
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Check if this assignment is currently active.
     */
    public function isActive()
    {
        return $this->check_out_date === null && $this->status === self::STATUS_ACTIVE;
    }

    /**
     * Get the user who created this assignment.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
