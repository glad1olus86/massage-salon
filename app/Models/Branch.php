<?php

namespace App\Models;

use App\Models\Traits\HasResponsible;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Branch extends Model
{
    use HasFactory;

    // Используем trait только если он существует
    // use HasResponsible;

    protected $fillable = [
        'name',
        'address',
        'phone',
        'email',
        'latitude',
        'longitude',
        'photos',
        'working_hours',
        'created_by',
        'responsible_id'
    ];

    protected $casts = [
        'photos' => 'array',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
    ];

    /**
     * Get first photo or placeholder
     */
    public function getMainPhotoAttribute()
    {
        if ($this->photos && count($this->photos) > 0) {
            return $this->photos[0];
        }
        return null;
    }

    /**
     * Get all rooms for this branch.
     */
    public function rooms()
    {
        return $this->hasMany(Room::class);
    }

    /**
     * Get all users (masseuses) assigned to this branch.
     */
    public function users()
    {
        return $this->hasMany(User::class);
    }

    /**
     * Get all room assignments for this branch.
     */
    public function roomAssignments()
    {
        return $this->hasMany(RoomAssignment::class);
    }

    /**
     * Get active room assignments for this branch.
     */
    public function activeAssignments()
    {
        return $this->hasMany(RoomAssignment::class)->where('status', 'active');
    }

    /**
     * Get responsible user for this branch.
     */
    public function responsible()
    {
        return $this->belongsTo(User::class, 'responsible_id');
    }

    /**
     * Get total capacity of all rooms in this branch.
     */
    public function getTotalCapacityAttribute()
    {
        return $this->rooms->sum('capacity');
    }

    /**
     * Get count of occupied spots in this branch.
     */
    public function getOccupiedSpotsAttribute()
    {
        return $this->rooms->sum(function ($room) {
            return $room->currentAssignments()->count();
        });
    }

    /**
     * Get available spots in this branch.
     */
    public function getAvailableSpotsAttribute()
    {
        return $this->total_capacity - $this->occupied_spots;
    }
}
