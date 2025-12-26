<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Room extends Model
{
    use HasFactory;

    protected $fillable = ['hotel_id', 'room_number', 'capacity', 'monthly_price', 'created_by'];

    public function hotel()
    {
        return $this->belongsTo(Hotel::class);
    }

    /**
     * Get all assignments for this room.
     */
    public function assignments()
    {
        return $this->hasMany(RoomAssignment::class);
    }

    /**
     * Get only current (active) assignments for this room.
     */
    public function currentAssignments()
    {
        return $this->hasMany(RoomAssignment::class)
            ->whereNull('check_out_date');
    }

    /**
     * Get the number of available spots in this room.
     */
    public function availableSpots()
    {
        $occupied = $this->currentAssignments()->count();
        return $this->capacity - $occupied;
    }

    /**
     * Check if this room is full.
     */
    public function isFull()
    {
        return $this->availableSpots() <= 0;
    }

    /**
     * Get occupancy info as a string (e.g., "2/3").
     */
    public function occupancyStatus()
    {
        $occupied = $this->currentAssignments()->count();
        return $occupied . '/' . $this->capacity;
    }
}
