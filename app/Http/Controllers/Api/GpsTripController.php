<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Vehicle;
use App\Models\VehicleTrip;
use App\Services\GpsTrackingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GpsTripController extends Controller
{
    public function __construct(
        private GpsTrackingService $trackingService
    ) {}

    /**
     * Start a new trip for a vehicle.
     * POST /api/gps/trips/start
     */
    public function start(Request $request): JsonResponse
    {
        $request->validate([
            'vehicle_id' => 'required|integer|exists:vehicles,id',
        ]);

        $user = $request->user();
        $vehicle = Vehicle::find($request->vehicle_id);

        // Check if vehicle belongs to user's company
        if ($vehicle->created_by !== $user->creatorId()) {
            return response()->json([
                'error' => __('You do not have access to this vehicle'),
                'code' => 'ACCESS_DENIED',
            ], 403);
        }

        // Check if vehicle already has an active trip
        if ($this->trackingService->hasActiveTrip($vehicle)) {
            $activeTrip = $this->trackingService->getActiveTrip($vehicle);
            return response()->json([
                'error' => __('Vehicle already has an active trip'),
                'code' => 'TRIP_ACTIVE',
                'active_trip_id' => $activeTrip->id,
            ], 409);
        }

        // Start new trip
        $trip = $this->trackingService->startTrip($vehicle, $user);

        return response()->json([
            'trip_id' => $trip->id,
            'started_at' => $trip->started_at->toIso8601String(),
        ], 201);
    }

    /**
     * End an active trip.
     * POST /api/gps/trips/end
     */
    public function end(Request $request): JsonResponse
    {
        $request->validate([
            'trip_id' => 'required|integer|exists:vehicle_trips,id',
        ]);

        $user = $request->user();
        $trip = VehicleTrip::find($request->trip_id);

        // Check if trip belongs to current user
        if ($trip->user_id !== $user->id) {
            return response()->json([
                'error' => __('You do not have access to this trip'),
                'code' => 'ACCESS_DENIED',
            ], 403);
        }

        // Check if trip is already ended
        if (!$trip->isActive()) {
            return response()->json([
                'error' => __('Trip is already ended'),
                'code' => 'TRIP_ENDED',
            ], 409);
        }

        // End trip and calculate distance
        $trip = $this->trackingService->endTrip($trip);

        return response()->json([
            'trip_id' => $trip->id,
            'started_at' => $trip->started_at->toIso8601String(),
            'ended_at' => $trip->ended_at->toIso8601String(),
            'total_distance_km' => $trip->total_distance_km,
        ]);
    }

    /**
     * Get track points for a trip.
     * GET /api/gps/trips/{trip}/track
     */
    public function getTrack(Request $request, VehicleTrip $trip): JsonResponse
    {
        $user = $request->user();

        // Check if trip belongs to user's company
        if ($trip->created_by !== $user->creatorId()) {
            return response()->json([
                'error' => __('You do not have access to this trip'),
                'code' => 'ACCESS_DENIED',
            ], 403);
        }

        $points = $this->trackingService->getTrackWithGaps($trip);

        return response()->json([
            'trip' => [
                'id' => $trip->id,
                'vehicle_id' => $trip->vehicle_id,
                'started_at' => $trip->started_at->toIso8601String(),
                'ended_at' => $trip->ended_at?->toIso8601String(),
                'total_distance_km' => $trip->total_distance_km,
            ],
            'points' => $points,
        ]);
    }
}
