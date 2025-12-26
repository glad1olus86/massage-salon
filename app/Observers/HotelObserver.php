<?php

namespace App\Observers;

use App\Models\Hotel;
use App\Services\AuditService;

class HotelObserver
{
    /**
     * Handle the Hotel "created" event.
     */
    public function created(Hotel $hotel): void
    {
        AuditService::logHotelCreated($hotel);
    }

    /**
     * Handle the Hotel "updated" event.
     */
    public function updated(Hotel $hotel): void
    {
        $oldValues = $hotel->getOriginal();
        AuditService::logHotelUpdated($hotel, $oldValues);
    }

    /**
     * Handle the Hotel "deleted" event.
     */
    public function deleted(Hotel $hotel): void
    {
        AuditService::logHotelDeleted($hotel);
    }
}
