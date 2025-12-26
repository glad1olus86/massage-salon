<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VehicleTrackPoint extends Model
{
    use HasFactory;

    const UPDATED_AT = null; // Only created_at, no updated_at

    protected $fillable = [
        'trip_id',
        'latitude',
        'longitude',
        'speed',
        'accuracy',
        'recorded_at',
        'synced_at',
    ];

    protected $casts = [
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
        'speed' => 'decimal:1',
        'accuracy' => 'decimal:1',
        'recorded_at' => 'datetime',
        'synced_at' => 'datetime',
    ];

    /**
     * Gap threshold in seconds (2 minutes).
     */
    const GAP_THRESHOLD_SECONDS = 120;

    /**
     * Get the trip this point belongs to.
     */
    public function trip(): BelongsTo
    {
        return $this->belongsTo(VehicleTrip::class, 'trip_id');
    }

    /**
     * Check if this point represents a gap in tracking.
     * A gap is when there's more than 2 minutes between this point and the previous one.
     * This is calculated dynamically based on context.
     */
    public function isGapFrom(?VehicleTrackPoint $previousPoint): bool
    {
        if ($previousPoint === null) {
            return false;
        }

        $diffSeconds = $this->recorded_at->diffInSeconds($previousPoint->recorded_at);
        
        return $diffSeconds > self::GAP_THRESHOLD_SECONDS;
    }

    /**
     * Get formatted time for display.
     */
    public function getFormattedTimeAttribute(): string
    {
        return $this->recorded_at->format('H:i:s');
    }

    /**
     * Get formatted speed for display.
     */
    public function getFormattedSpeedAttribute(): ?string
    {
        if ($this->speed === null) {
            return null;
        }
        
        return round($this->speed) . ' км/ч';
    }

    /**
     * Convert to array for map display.
     */
    public function toMapPoint(bool $isGap = false): array
    {
        return [
            'lat' => (float) $this->latitude,
            'lng' => (float) $this->longitude,
            'time' => $this->formatted_time,
            'speed' => $this->speed ? round($this->speed) : null,
            'is_gap' => $isGap,
        ];
    }
}
