<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Room extends Model
{
    use HasFactory;

    protected $fillable = ['branch_id', 'hotel_id', 'room_number', 'photo', 'capacity', 'monthly_price', 'created_by'];

    /**
     * Get the branch this room belongs to.
     */
    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Get the hotel this room belongs to (legacy support).
     */
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
}
