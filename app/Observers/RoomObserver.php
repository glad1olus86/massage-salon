<?php

namespace App\Observers;

use App\Models\Room;
use App\Services\AuditService;

class RoomObserver
{
    /**
     * Handle the Room "created" event.
     */
    public function created(Room $room): void
    {
        $room->load('hotel');
        AuditService::logRoomCreated($room);
    }

    /**
     * Handle the Room "updated" event.
     */
    public function updated(Room $room): void
    {
        $room->load('hotel');
        $oldValues = $room->getOriginal();
        AuditService::logRoomUpdated($room, $oldValues);
    }

    /**
     * Handle the Room "deleted" event.
     */
    public function deleted(Room $room): void
    {
        AuditService::logRoomDeleted($room);
    }
}
