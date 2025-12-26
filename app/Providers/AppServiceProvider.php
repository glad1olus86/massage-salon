<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;

// Models
use App\Models\Worker;
use App\Models\RoomAssignment;
use App\Models\WorkAssignment;
use App\Models\Hotel;
use App\Models\Room;
use App\Models\WorkPlace;

// Observers
use App\Observers\WorkerObserver;
use App\Observers\RoomAssignmentObserver;
use App\Observers\WorkAssignmentObserver;
use App\Observers\HotelObserver;
use App\Observers\RoomObserver;
use App\Observers\WorkPlaceObserver;


class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Schema::defaultStringLength(191);

        // Set Carbon locale to match app locale
        Carbon::setLocale(config('app.locale'));

        // Register Audit Observers
        Worker::observe(WorkerObserver::class);
        RoomAssignment::observe(RoomAssignmentObserver::class);
        WorkAssignment::observe(WorkAssignmentObserver::class);
        Hotel::observe(HotelObserver::class);
        Room::observe(RoomObserver::class);
        WorkPlace::observe(WorkPlaceObserver::class);
    }
}
