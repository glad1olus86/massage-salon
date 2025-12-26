<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Auth;

class VehicleTrip extends Model
{
    use HasFactory;

    protected $fillable = [
        'vehicle_id',
        'user_id',
        'started_at',
        'ended_at',
        'total_distance_km',
        'created_by',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
        'total_distance_km' => 'decimal:2',
    ];

    /**
     * Get the vehicle for this trip.
     */
    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    /**
     * Get the driver (user) for this trip.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get all track points for this trip.
     */
    public function trackPoints(): HasMany
    {
        return $this->hasMany(VehicleTrackPoint::class, 'trip_id')->orderBy('recorded_at');
    }

    /**
     * Scope for current user's company (multi-tenancy).
     */
    public function scopeForCurrentUser($query)
    {
        return $query->where('created_by', Auth::user()->creatorId());
    }

    /**
     * Scope for trips on a specific date.
     */
    public function scopeForDate($query, Carbon $date)
    {
        return $query->whereDate('started_at', $date);
    }

    /**
     * Scope for active (not ended) trips.
     */
    public function scopeActive($query)
    {
        return $query->whereNull('ended_at');
    }

    /**
     * Scope for trips of a specific vehicle.
     */
    public function scopeForVehicle($query, int $vehicleId)
    {
        return $query->where('vehicle_id', $vehicleId);
    }

    /**
     * Check if trip is currently active.
     */
    public function isActive(): bool
    {
        return $this->ended_at === null;
    }

    /**
     * Calculate total distance from track points using Haversine formula.
     */
    public function calculateDistance(): float
    {
        $points = $this->trackPoints()->orderBy('recorded_at')->get();
        
        if ($points->count() < 2) {
            return 0;
        }

        $totalDistance = 0;
        $previousPoint = null;

        foreach ($points as $point) {
            if ($previousPoint !== null) {
                $totalDistance += $this->haversineDistance(
                    $previousPoint->latitude,
                    $previousPoint->longitude,
                    $point->latitude,
                    $point->longitude
                );
            }
            $previousPoint = $point;
        }

        return round($totalDistance, 2);
    }

    /**
     * Calculate distance between two points using Haversine formula.
     * Returns distance in kilometers.
     */
    private function haversineDistance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371; // km

        $latDelta = deg2rad($lat2 - $lat1);
        $lonDelta = deg2rad($lon2 - $lon1);

        $a = sin($latDelta / 2) * sin($latDelta / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($lonDelta / 2) * sin($lonDelta / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }

    /**
     * Get formatted duration of the trip.
     */
    public function getDurationAttribute(): ?string
    {
        if (!$this->ended_at) {
            return null;
        }

        $diff = $this->started_at->diff($this->ended_at);
        
        if ($diff->h > 0) {
            return $diff->format('%hч %iмин');
        }
        
        return $diff->format('%iмин');
    }
}
