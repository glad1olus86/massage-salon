<?php

namespace App\Observers;

use App\Models\User;
use App\Services\Infinity\DutyService;

class UserObserver
{
    protected DutyService $dutyService;

    public function __construct(DutyService $dutyService)
    {
        $this->dutyService = $dutyService;
    }

    /**
     * Handle the User "created" event.
     * Инициализируем баллы дежурств при создании сотрудника с филиалом.
     */
    public function created(User $user): void
    {
        if ($user->branch_id && $user->type === 'employee') {
            $this->dutyService->initializePointsForNewEmployee($user->branch_id, $user->id);
        }
    }

    /**
     * Handle the User "updated" event.
     * Инициализируем баллы при назначении сотрудника в филиал.
     */
    public function updated(User $user): void
    {
        // Проверяем изменился ли branch_id
        if ($user->isDirty('branch_id') && $user->branch_id && $user->type === 'employee') {
            $this->dutyService->initializePointsForNewEmployee($user->branch_id, $user->id);
        }
    }
}
