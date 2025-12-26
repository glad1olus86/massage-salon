<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\VehicleTrip;
use App\Services\GpsTrackingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GpsTrackController extends Controller
{
    public function __construct(
        private GpsTrackingService $trackingService
    ) {}

    /**
     * Store batch of track points for a trip.
     * POST /api/gps/trips/{trip}/track
     */
    public function store(Request $request, VehicleTrip $trip): JsonResponse
    {
        $user = $request->user();

        // Check if trip belongs to current user (authorization)
        if ($trip->user_id !== $user->id) {
            return response()->json([
                'error' => __('You do not have access to this trip'),
                'code' => 'ACCESS_DENIED',
            ], 403);
        }

        // Check if trip is still active
        if (!$trip->isActive()) {
            return response()->json([
                'error' => __('Cannot add points to ended trip'),
                'code' => 'TRIP_ENDED',
            ], 409);
        }

        // Validate request
        $request->validate([
            'points' => 'required|array|max:100',
            'points.*.latitude' => 'required|numeric|between:-90,90',
            'points.*.longitude' => 'required|numeric|between:-180,180',
            'points.*.recorded_at' => 'required|date',
            'points.*.speed' => 'nullable|numeric|min:0',
            'points.*.accuracy' => 'nullable|numeric|min:0',
        ]);

        $points = $request->input('points', []);
        $totalPoints = count($points);

        // Save points using service (handles filtering of invalid points)
        $savedCount = $this->trackingService->saveTrackPoints($trip, $points);
        $rejectedCount = $totalPoints - $savedCount;

        return response()->json([
            'saved' => $savedCount,
            'rejected' => $rejectedCount,
        ]);
    }
}
