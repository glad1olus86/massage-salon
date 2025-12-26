<?php

namespace App\Observers;

use App\Models\WorkPlace;
use App\Services\AuditService;

class WorkPlaceObserver
{
    /**
     * Handle the WorkPlace "created" event.
     */
    public function created(WorkPlace $workPlace): void
    {
        AuditService::logWorkPlaceCreated($workPlace);
    }

    /**
     * Handle the WorkPlace "updated" event.
     */
    public function updated(WorkPlace $workPlace): void
    {
        $oldValues = $workPlace->getOriginal();
        AuditService::logWorkPlaceUpdated($workPlace, $oldValues);
    }

    /**
     * Handle the WorkPlace "deleted" event.
     */
    public function deleted(WorkPlace $workPlace): void
    {
        AuditService::logWorkPlaceDeleted($workPlace);
    }
}
