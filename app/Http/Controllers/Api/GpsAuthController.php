<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class GpsAuthController extends Controller
{
    /**
     * Authenticate user and return token with available vehicles.
     * POST /api/gps/login
     */
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'error' => __('Invalid credentials'),
                'code' => 'AUTH_FAILED',
            ], 401);
        }

        // Check if user is active
        if (!$user->is_active) {
            return response()->json([
                'error' => __('Account is deactivated'),
                'code' => 'ACCOUNT_INACTIVE',
            ], 401);
        }

        // Create Sanctum token for mobile app
        $token = $user->createToken('gps-driver-app')->plainTextToken;

        // Get vehicles available to this user (from their company)
        $vehicles = Vehicle::where('created_by', $user->creatorId())
            ->orderBy('brand')
            ->get()
            ->map(function ($vehicle) {
                return [
                    'id' => $vehicle->id,
                    'license_plate' => $vehicle->license_plate,
                    'brand' => $vehicle->brand,
                ];
            });

        return response()->json([
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'company_id' => $user->creatorId(),
            ],
            'vehicles' => $vehicles,
        ]);
    }

    /**
     * Logout and revoke current token.
     * POST /api/gps/logout
     */
    public function logout(Request $request): JsonResponse
    {
        // Revoke the token that was used to authenticate the current request
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => __('Successfully logged out'),
        ]);
    }

    /**
     * Get current user info and refresh vehicles list.
     * GET /api/gps/me
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user();

        $vehicles = Vehicle::where('created_by', $user->creatorId())
            ->orderBy('brand')
            ->get()
            ->map(function ($vehicle) {
                return [
                    'id' => $vehicle->id,
                    'license_plate' => $vehicle->license_plate,
                    'brand' => $vehicle->brand,
                ];
            });

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'company_id' => $user->creatorId(),
            ],
            'vehicles' => $vehicles,
        ]);
    }
}
