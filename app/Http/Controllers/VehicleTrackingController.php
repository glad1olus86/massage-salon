<?php

namespace App\Http\Controllers;

use App\Models\Vehicle;
use App\Services\GpsTrackingService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VehicleTrackingController extends Controller
{
    public function __construct(
        private GpsTrackingService $trackingService
    ) {}

    /**
     * Get list of trips for a vehicle on a specific date.
     * GET /vehicles/{vehicle}/trips
     */
    public function trips(Request $request, Vehicle $vehicle): JsonResponse
    {
        // Check permission - anyone who can view vehicles can see tracking
        if (!$request->user()->can('vehicle_read')) {
            return response()->json([
                'error' => __('You do not have permission to view tracking data'),
                'code' => 'ACCESS_DENIED',
            ], 403);
        }

        // Check vehicle belongs to user's company
        if ($vehicle->created_by !== $request->user()->creatorId()) {
            return response()->json([
                'error' => __('You do not have access to this vehicle'),
                'code' => 'ACCESS_DENIED',
            ], 403);
        }

        $request->validate([
            'date' => 'nullable|date',
        ]);

        $date = $request->input('date')
            ? Carbon::parse($request->input('date'))
            : Carbon::today();

        $trips = $this->trackingService->getTripsForDate($vehicle, $date);

        return response()->json([
            'date' => $date->toDateString(),
            'trips' => $trips->map(function ($trip) {
                return [
                    'id' => $trip->id,
                    'started_at' => $trip->started_at->toIso8601String(),
                    'ended_at' => $trip->ended_at?->toIso8601String(),
                    'total_distance_km' => $trip->total_distance_km,
                    'is_active' => $trip->isActive(),
                ];
            }),
        ]);
    }

    /**
     * Get track points for a vehicle on a specific date.
     * GET /vehicles/{vehicle}/track
     */
    public function track(Request $request, Vehicle $vehicle): JsonResponse
    {
        // Check permission - anyone who can view vehicles can see tracking
        if (!$request->user()->can('vehicle_read')) {
            return response()->json([
                'error' => __('You do not have permission to view tracking data'),
                'code' => 'ACCESS_DENIED',
            ], 403);
        }

        // Check vehicle belongs to user's company
        if ($vehicle->created_by !== $request->user()->creatorId()) {
            return response()->json([
                'error' => __('You do not have access to this vehicle'),
                'code' => 'ACCESS_DENIED',
            ], 403);
        }

        $request->validate([
            'date' => 'nullable|date',
            'trip_id' => 'nullable|integer',
        ]);

        $date = $request->input('date')
            ? Carbon::parse($request->input('date'))
            : Carbon::today();

        // Get all trips for the date
        $allTrips = $this->trackingService->getTripsForDate($vehicle, $date);

        // If specific trip requested, use it; otherwise use first trip
        $tripId = $request->input('trip_id');
        if ($tripId) {
            $trip = $allTrips->firstWhere('id', $tripId);
        } else {
            $trip = $allTrips->first();
        }

        if (!$trip) {
            return response()->json([
                'date' => $date->toDateString(),
                'trips' => [],
                'trip' => null,
                'points' => [],
            ]);
        }

        $points = $this->trackingService->getTrackWithGaps($trip);

        // Calculate distance for current trip if not stored
        $tripDistance = $trip->total_distance_km;
        if (!$tripDistance || $tripDistance == 0) {
            $tripDistance = $trip->calculateDistance();
        }

        return response()->json([
            'date' => $date->toDateString(),
            'trips' => $allTrips->map(function ($t, $index) {
                // Calculate distance if not stored
                $distance = $t->total_distance_km;
                if (!$distance || $distance == 0) {
                    $distance = $t->calculateDistance();
                }
                
                return [
                    'id' => $t->id,
                    'label' => __('Trip') . ' ' . ($index + 1),
                    'started_at' => $t->started_at->toIso8601String(),
                    'ended_at' => $t->ended_at?->toIso8601String(),
                    'total_distance_km' => round($distance, 2),
                    'is_active' => $t->isActive(),
                ];
            })->values(),
            'trip' => [
                'id' => $trip->id,
                'started_at' => $trip->started_at->toIso8601String(),
                'ended_at' => $trip->ended_at?->toIso8601String(),
                'total_distance_km' => round($tripDistance, 2),
                'is_active' => $trip->isActive(),
            ],
            'points' => $points,
        ]);
    }
}
