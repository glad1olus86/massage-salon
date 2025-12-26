<?php

namespace App\Observers;

use App\Models\WorkAssignment;
use App\Services\AuditService;

class WorkAssignmentObserver
{
    /**
     * Handle the WorkAssignment "created" event.
     */
    public function created(WorkAssignment $assignment): void
    {
        // Загружаем relationships
        $assignment->load(['worker', 'workPlace']);

        AuditService::logWorkerHired($assignment);
    }

    /**
     * Handle the WorkAssignment "updated" event.
     */
    public function updated(WorkAssignment $assignment): void
    {
        // Проверяем, было ли установлено ended_at (увольнение)
        if ($assignment->isDirty('ended_at') && $assignment->ended_at !== null) {
            $assignment->load(['worker', 'workPlace']);
            AuditService::logWorkerDismissed($assignment);
        }
    }
}
