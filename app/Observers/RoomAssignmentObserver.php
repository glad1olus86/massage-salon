<?php

namespace App\Observers;

use App\Models\RoomAssignment;
use App\Services\AuditService;

class RoomAssignmentObserver
{
    /**
     * Handle the RoomAssignment "created" event.
     */
    public function created(RoomAssignment $assignment): void
    {
        // Загружаем relationships
        $assignment->load(['worker', 'room', 'hotel']);

        AuditService::logWorkerCheckedIn($assignment);
    }

    /**
     * Handle the RoomAssignment "updated" event.
     */
    public function updated(RoomAssignment $assignment): void
    {
        // Проверяем, было ли установлено check_out_date (выселение)
        if ($assignment->isDirty('check_out_date') && $assignment->check_out_date !== null) {
            $assignment->load(['worker', 'room', 'hotel']);
            AuditService::logWorkerCheckedOut($assignment);
        }
    }
}
