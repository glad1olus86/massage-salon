<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CleaningStatus extends Model
{
    use HasFactory;

    protected $fillable = [
        'cleaning_duty_id',
        'room_id',
        'area_type',
        'status',
        'cleaned_by',
        'cleaned_at',
    ];

    protected $casts = [
        'cleaned_at' => 'datetime',
    ];

    /**
     * Get the cleaning duty.
     */
    public function cleaningDuty()
    {
        return $this->belongsTo(CleaningDuty::class);
    }

    /**
     * Get the room (if area_type is 'room').
     */
    public function room()
    {
        return $this->belongsTo(Room::class);
    }

    /**
     * Get the user who cleaned.
     */
    public function cleanedBy()
    {
        return $this->belongsTo(User::class, 'cleaned_by');
    }

    /**
     * Scope for room statuses.
     */
    public function scopeRooms($query)
    {
        return $query->where('area_type', 'room');
    }

    /**
     * Scope for common area statuses.
     */
    public function scopeCommonAreas($query)
    {
        return $query->where('area_type', 'common_area');
    }

    /**
     * Mark as clean.
     */
    public function markAsClean(int $userId): void
    {
        $this->update([
            'status' => 'clean',
            'cleaned_by' => $userId,
            'cleaned_at' => now(),
        ]);
    }

    /**
     * Check if clean.
     */
    public function getIsCleanAttribute(): bool
    {
        return $this->status === 'clean';
    }
}
