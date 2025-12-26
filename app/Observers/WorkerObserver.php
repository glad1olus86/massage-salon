<?php

namespace App\Observers;

use App\Models\Worker;
use App\Services\AuditService;

class WorkerObserver
{
    /**
     * Handle the Worker "created" event.
     */
    public function created(Worker $worker): void
    {
        AuditService::logWorkerCreated($worker);
    }

    /**
     * Handle the Worker "updated" event.
     */
    public function updated(Worker $worker): void
    {
        // Получаем старые значения из dirty attributes
        $oldValues = $worker->getOriginal();

        AuditService::logWorkerUpdated($worker, $oldValues);
    }

    /**
     * Handle the Worker "deleted" event.
     */
    public function deleted(Worker $worker): void
    {
        AuditService::logWorkerDeleted($worker);
    }
}
