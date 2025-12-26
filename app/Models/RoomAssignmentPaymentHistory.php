<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RoomAssignmentPaymentHistory extends Model
{
    use HasFactory;

    protected $table = 'room_assignment_payment_history';

    protected $fillable = [
        'room_assignment_id',
        'payment_type',
        'payment_amount',
        'changed_by_name',
        'changed_by',
        'comment',
    ];

    protected $casts = [
        'payment_amount' => 'decimal:2',
    ];

    /**
     * Get the assignment this history belongs to.
     */
    public function assignment()
    {
        return $this->belongsTo(RoomAssignment::class, 'room_assignment_id');
    }

    /**
     * Get the user who made the change.
     */
    public function changedByUser()
    {
        return $this->belongsTo(User::class, 'changed_by');
    }

    /**
     * Get formatted payment info.
     */
    public function getPaymentInfoAttribute(): string
    {
        if ($this->payment_type === 'agency') {
            return __('Agency pays');
        }
        
        if ($this->payment_amount) {
            return __('Worker pays') . ': ' . formatCashboxCurrency($this->payment_amount);
        }
        
        return __('Worker pays');
    }
}
