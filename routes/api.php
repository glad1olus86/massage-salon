<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ApiController;
use App\Http\Controllers\Api\GpsAuthController;
use App\Http\Controllers\Api\GpsTripController;
use App\Http\Controllers\Api\GpsTrackController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/
Route::post('login', [ApiController::class, 'login']);

Route::group(['middleware' => ['auth:sanctum']], function () {

    Route::post('logout', [ApiController::class, 'logout']);
    Route::get('get-projects', [ApiController::class, 'getProjects']);
    Route::post('add-tracker', [ApiController::class, 'addTracker']);
    Route::post('stop-tracker', [ApiController::class, 'stopTracker']);
    Route::post('upload-photos', [ApiController::class, 'uploadImage']);
});

/*
|--------------------------------------------------------------------------
| GPS Tracking API Routes
|--------------------------------------------------------------------------
*/
Route::prefix('gps')->group(function () {
    // Public routes
    Route::post('login', [GpsAuthController::class, 'login']);

    // Protected routes
    Route::middleware(['auth:sanctum'])->group(function () {
        Route::post('logout', [GpsAuthController::class, 'logout']);
        Route::get('me', [GpsAuthController::class, 'me']);

        // Trip management
        Route::post('trips/start', [GpsTripController::class, 'start']);
        Route::post('trips/end', [GpsTripController::class, 'end']);
        Route::get('trips/{trip}/track', [GpsTripController::class, 'getTrack']);

        // Track points (with rate limiting)
        Route::post('trips/{trip}/track', [GpsTrackController::class, 'store'])
            ->middleware('throttle:60,1');
    });
});
