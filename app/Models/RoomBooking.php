<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RoomBooking extends Model
{
    use HasFactory;

    protected $fillable = [
        'room_id',
        'branch_id',
        'user_id',
        'client_id',
        'booking_date',
        'start_time',
        'end_time',
        'status',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'booking_date' => 'date',
        'start_time' => 'datetime:H:i',
        'end_time' => 'datetime:H:i',
    ];

    /**
     * Get the room for this booking.
     */
    public function room()
    {
        return $this->belongsTo(Room::class);
    }

    /**
     * Get the branch for this booking.
     */
    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Get the user (masseuse) for this booking.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the client for this booking.
     */
    public function client()
    {
        return $this->belongsTo(MassageClient::class, 'client_id');
    }

    /**
     * Get the user who created this booking.
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
     * Scope for filtering by date range.
     */
    public function scopeBetweenDates($query, $startDate, $endDate)
    {
        return $query->whereBetween('booking_date', [$startDate, $endDate]);
    }

    /**
     * Scope for active bookings (not cancelled).
     */
    public function scopeActive($query)
    {
        return $query->where('status', '!=', 'cancelled');
    }
}
