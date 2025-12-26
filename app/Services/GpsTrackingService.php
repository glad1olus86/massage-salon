<?php

namespace App\Services;

use App\Models\Vehicle;
use App\Models\VehicleTrip;
use App\Models\VehicleTrackPoint;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class GpsTrackingService
{
    /**
     * Maximum speed in km/h - points with higher speed are considered errors.
     */
    const MAX_VALID_SPEED = 200;

    /**
     * Maximum accuracy in meters - points with worse accuracy are filtered.
     */
    const MAX_VALID_ACCURACY = 100;

    /**
     * Gap threshold in seconds (2 minutes).
     */
    const GAP_THRESHOLD_SECONDS = 120;

    /**
     * Start a new trip for a vehicle.
     */
    public function startTrip(Vehicle $vehicle, User $driver): VehicleTrip
    {
        return VehicleTrip::create([
            'vehicle_id' => $vehicle->id,
            'user_id' => $driver->id,
            'started_at' => now(),
            'created_by' => $driver->creatorId(),
        ]);
    }

    /**
     * End a trip and calculate total distance.
     */
    public function endTrip(VehicleTrip $trip): VehicleTrip
    {
        $distance = $trip->calculateDistance();

        $trip->update([
            'ended_at' => now(),
            'total_distance_km' => $distance,
        ]);

        return $trip->fresh();
    }

    /**
     * Save a batch of track points.
     * Returns the number of successfully saved points.
     */
    public function saveTrackPoints(VehicleTrip $trip, array $points): int
    {
        $savedCount = 0;
        $now = now();

        $validPoints = [];

        foreach ($points as $point) {
            // Validate required fields
            if (!isset($point['latitude']) || !isset($point['longitude']) || !isset($point['recorded_at'])) {
                continue;
            }

            // Validate coordinates range
            $lat = (float) $point['latitude'];
            $lng = (float) $point['longitude'];

            if ($lat < -90 || $lat > 90 || $lng < -180 || $lng > 180) {
                continue;
            }

            // Filter out points with unrealistic speed
            $speed = isset($point['speed']) ? (float) $point['speed'] : null;
            if ($speed !== null && $speed > self::MAX_VALID_SPEED) {
                continue;
            }

            // Filter out points with poor accuracy
            $accuracy = isset($point['accuracy']) ? (float) $point['accuracy'] : null;
            if ($accuracy !== null && $accuracy > self::MAX_VALID_ACCURACY) {
                continue;
            }

            $validPoints[] = [
                'trip_id' => $trip->id,
                'latitude' => $lat,
                'longitude' => $lng,
                'speed' => $speed,
                'accuracy' => $accuracy,
                'recorded_at' => Carbon::parse($point['recorded_at']),
                'synced_at' => $now,
                'created_at' => $now,
            ];
        }

        if (!empty($validPoints)) {
            // Bulk insert for performance
            DB::table('vehicle_track_points')->insert($validPoints);
            $savedCount = count($validPoints);
        }

        return $savedCount;
    }


    /**
     * Get track points with gap markers for map display.
     */
    public function getTrackWithGaps(VehicleTrip $trip): Collection
    {
        $points = $trip->trackPoints()->orderBy('recorded_at')->get();

        return $this->markGaps($points);
    }

    /**
     * Mark gaps in track points collection.
     * A gap is when there's more than 2 minutes between consecutive points.
     */
    private function markGaps(Collection $points): Collection
    {
        if ($points->isEmpty()) {
            return collect();
        }

        $result = collect();
        $previousPoint = null;

        foreach ($points as $point) {
            $isGap = false;

            if ($previousPoint !== null) {
                $diffSeconds = $point->recorded_at->diffInSeconds($previousPoint->recorded_at);
                $isGap = $diffSeconds > self::GAP_THRESHOLD_SECONDS;
            }

            $result->push($point->toMapPoint($isGap));
            $previousPoint = $point;
        }

        return $result;
    }

    /**
     * Calculate distance between array of points using Haversine formula.
     * Points should have 'latitude' and 'longitude' keys.
     */
    public function calculateDistance(array $points): float
    {
        if (count($points) < 2) {
            return 0;
        }

        $totalDistance = 0;
        $previousPoint = null;

        foreach ($points as $point) {
            if ($previousPoint !== null) {
                $totalDistance += $this->haversineDistance(
                    $previousPoint['latitude'],
                    $previousPoint['longitude'],
                    $point['latitude'],
                    $point['longitude']
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
     * Check if vehicle has an active (not ended) trip.
     */
    public function hasActiveTrip(Vehicle $vehicle): bool
    {
        return VehicleTrip::where('vehicle_id', $vehicle->id)
            ->active()
            ->exists();
    }

    /**
     * Get active trip for a vehicle.
     */
    public function getActiveTrip(Vehicle $vehicle): ?VehicleTrip
    {
        return VehicleTrip::where('vehicle_id', $vehicle->id)
            ->active()
            ->first();
    }

    /**
     * Get trips for a vehicle on a specific date.
     */
    public function getTripsForDate(Vehicle $vehicle, Carbon $date): Collection
    {
        return VehicleTrip::where('vehicle_id', $vehicle->id)
            ->forDate($date)
            ->orderBy('started_at')
            ->get();
    }

    /**
     * Get the latest trip for a vehicle (for map display).
     */
    public function getLatestTripForDate(Vehicle $vehicle, Carbon $date): ?VehicleTrip
    {
        return VehicleTrip::where('vehicle_id', $vehicle->id)
            ->forDate($date)
            ->orderBy('started_at', 'desc')
            ->first();
    }
}
