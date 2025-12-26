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

    protected $fillable = [
        'worker_id',
        'room_id',
        'hotel_id',
        'check_in_date',
        'check_out_date',
        'payment_type',
        'payment_amount',
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
     * Get the worker for this assignment.
     */
    public function worker()
    {
        return $this->belongsTo(Worker::class);
    }

    /**
     * Get the room for this assignment.
     */
    public function room()
    {
        return $this->belongsTo(Room::class);
    }

    /**
     * Get the hotel for this assignment.
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
        return $query->whereNull('check_out_date');
    }

    /**
     * Scope to get assignments for a specific worker.
     */
    public function scopeForWorker($query, $workerId)
    {
        return $query->where('worker_id', $workerId);
    }

    /**
     * Check if this assignment is currently active.
     */
    public function isActive()
    {
        return $this->check_out_date === null;
    }

    /**
     * Get the user who created this assignment.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
