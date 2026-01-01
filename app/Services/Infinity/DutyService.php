<?php

namespace App\Services\Infinity;

use App\Models\CleaningDuty;
use App\Models\CleaningStatus;
use App\Models\DutyPoints;
use App\Models\Room;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DutyService
{
    /**
     * Получить следующего дежурного по алгоритму:
     * 
     * ПРИОРИТЕТ:
     * 1. Меньше баллов = выше приоритет (самая "чистая" девочка)
     * 2. При равных баллах: те кто ещё НЕ убирался (last_duty_date = NULL) идут ПЕРВЫМИ
     * 3. При равных баллах и обе убирались: та кто убиралась РАНЬШЕ идёт первой
     * 
     * ПРИМЕР:
     * - Маша: 200 баллов, убиралась 01.01
     * - Катя: 100 баллов, убиралась 25.12
     * - Соня: 100 баллов, НЕ убиралась (новенькая)
     * → Соня будет следующей (100 баллов + не убиралась)
     * 
     * ВАЖНО: Только массажистки (роль masseuse), закреплённые за филиалом
     */
    public function getNextDutyPerson(int $branchId): ?User
    {
        $dutyPoints = DutyPoints::where('branch_id', $branchId)
            ->whereHas('user', function ($query) use ($branchId) {
                $query->where('is_active', 1)
                    ->where('branch_id', $branchId) // Только сотрудники этого филиала
                    ->role('masseuse'); // Только массажистки, не операторы
            })
            ->orderBy('points', 'asc') // 1. Меньше баллов = приоритет
            ->orderByRaw('last_duty_date IS NULL DESC') // 2. Не убиралась = приоритет
            ->orderBy('last_duty_date', 'asc') // 3. Убиралась раньше = приоритет
            ->first();

        return $dutyPoints?->user;
    }

    /**
     * Инициализация баллов для нового сотрудника (при заселении в филиал).
     * 
     * ЛОГИКА:
     * 1. Новенькая получает баллы = MIN(баллы всех активных массажисток филиала)
     *    Пример: Маша 200, Катя 100 → Соня получит 100 баллов
     * 2. last_duty_date = NULL (ещё не убиралась)
     * 3. При выборе следующего дежурного: сначала по баллам (меньше = приоритет),
     *    при равенстве - те кто ещё не убирался (NULL) идут первыми
     *    Пример: Катя 100 (убиралась), Соня 100 (не убиралась) → Соня первая
     * 
     * ВАЖНО: Баллы создаются только для массажисток (роль masseuse).
     */
    public function initializePointsForNewEmployee(int $branchId, int $userId, int $createdBy): ?DutyPoints
    {
        // Проверяем, что пользователь - массажистка
        $user = User::find($userId);
        if (!$user || !$user->hasRole('masseuse')) {
            return null; // Операторы не участвуют в дежурствах
        }
        
        // Проверяем, нет ли уже записи баллов для этого сотрудника в этом филиале
        $existingPoints = DutyPoints::where('branch_id', $branchId)
            ->where('user_id', $userId)
            ->first();
        
        if ($existingPoints) {
            return $existingPoints; // Уже есть запись
        }
        
        // Получаем минимальные баллы среди активных массажисток филиала
        // Это баллы самой "чистой" девочки - новенькая получит столько же
        $minPoints = DutyPoints::where('branch_id', $branchId)
            ->whereHas('user', function ($query) {
                $query->where('is_active', 1)->role('masseuse');
            })
            ->min('points') ?? 0;

        $dutyPoints = DutyPoints::create([
            'branch_id' => $branchId,
            'user_id' => $userId,
            'points' => $minPoints,
            'last_duty_date' => null, // Ещё не убиралась - будет приоритет при равных баллах
            'created_by' => $createdBy,
        ]);
        
        // Пересчитываем предварительные дежурства следующей недели
        // Новенькая может стать следующей дежурной
        $this->recalculateFutureDuties($branchId);
        
        return $dutyPoints;
    }
    
    /**
     * Инициализация баллов для всех сотрудников филиала (если ещё не созданы).
     * ВАЖНО: Только для массажисток (роль masseuse), операторы не дежурят.
     */
    public function initializePointsForBranch(int $branchId, int $createdBy): void
    {
        // Получаем всех активных массажисток филиала (не операторов)
        $employees = User::where('branch_id', $branchId)
            ->where('is_active', 1)
            ->role('masseuse')
            ->get();
        
        foreach ($employees as $employee) {
            // Проверяем, есть ли уже запись баллов
            $exists = DutyPoints::where('branch_id', $branchId)
                ->where('user_id', $employee->id)
                ->exists();
            
            if (!$exists) {
                DutyPoints::create([
                    'branch_id' => $branchId,
                    'user_id' => $employee->id,
                    'points' => 0,
                    'last_duty_date' => null,
                    'created_by' => $createdBy,
                ]);
            }
        }
    }

    /**
     * Завершение дежурства: +100 баллов и обновление даты.
     */
    public function completeDuty(CleaningDuty $duty): void
    {
        DB::transaction(function () use ($duty) {
            // Обновляем баллы дежурного
            $dutyPoints = DutyPoints::where('branch_id', $duty->branch_id)
                ->where('user_id', $duty->user_id)
                ->first();

            if ($dutyPoints) {
                $dutyPoints->update([
                    'points' => $dutyPoints->points + 100,
                    'last_duty_date' => $duty->duty_date,
                ]);
            }

            // Отмечаем дежурство как выполненное
            $duty->update([
                'status' => 'completed',
                'completed_at' => now(),
            ]);

            // Отмечаем все статусы уборки как выполненные
            $duty->cleaningStatuses()->update([
                'status' => 'clean',
                'cleaned_by' => $duty->user_id,
                'cleaned_at' => now(),
            ]);
        });
    }

    /**
     * Автоматическое назначение дежурных на период.
     * Текущая неделя - утверждённая, следующая - предварительная.
     */
    public function assignDutiesForPeriod(int $branchId, Carbon $startDate, Carbon $endDate): Collection
    {
        $duties = collect();
        $currentDate = $startDate->copy();
        $currentWeekEnd = now()->endOfWeek();

        while ($currentDate->lte($endDate)) {
            // Проверяем, есть ли уже дежурство на эту дату
            $existingDuty = CleaningDuty::where('branch_id', $branchId)
                ->where('duty_date', $currentDate->toDateString())
                ->first();

            if (!$existingDuty) {
                // Определяем, утверждённое ли это дежурство (текущая неделя или раньше)
                $isConfirmed = $currentDate->lte($currentWeekEnd);
                $duty = $this->assignDutyForDate($branchId, $currentDate, $isConfirmed);
                if ($duty) {
                    $duties->push($duty);
                }
            }

            $currentDate->addDay();
        }

        return $duties;
    }
    
    /**
     * Пересчитать предварительные дежурства на следующую неделю.
     * Вызывается при изменении баллов или добавлении нового сотрудника.
     */
    public function recalculateFutureDuties(int $branchId): Collection
    {
        $nextWeekStart = now()->addWeek()->startOfWeek();
        $nextWeekEnd = now()->addWeek()->endOfWeek();
        
        // Получаем неутверждённые дежурства на следующую неделю
        $unconfirmedDuties = CleaningDuty::where('branch_id', $branchId)
            ->where('is_confirmed', false)
            ->where('is_manual', false)
            ->whereBetween('duty_date', [$nextWeekStart->toDateString(), $nextWeekEnd->toDateString()])
            ->get();
        
        // Пересчитываем каждое дежурство
        foreach ($unconfirmedDuties as $duty) {
            $nextPerson = $this->getNextDutyPerson($branchId);
            if ($nextPerson && $nextPerson->id !== $duty->user_id) {
                $duty->update(['user_id' => $nextPerson->id]);
            }
        }
        
        return $unconfirmedDuties;
    }
    
    /**
     * Утвердить дежурства текущей недели (вызывается автоматически).
     */
    public function confirmCurrentWeekDuties(int $branchId): void
    {
        $weekStart = now()->startOfWeek();
        $weekEnd = now()->endOfWeek();
        
        CleaningDuty::where('branch_id', $branchId)
            ->where('is_confirmed', false)
            ->whereBetween('duty_date', [$weekStart->toDateString(), $weekEnd->toDateString()])
            ->update(['is_confirmed' => true]);
    }

    /**
     * Назначить дежурного на конкретную дату.
     */
    public function assignDutyForDate(int $branchId, Carbon $date, bool $isConfirmed = false): ?CleaningDuty
    {
        $nextPerson = $this->getNextDutyPerson($branchId);

        if (!$nextPerson) {
            return null;
        }

        return DB::transaction(function () use ($branchId, $date, $nextPerson, $isConfirmed) {
            // Создаём дежурство
            $duty = CleaningDuty::create([
                'branch_id' => $branchId,
                'user_id' => $nextPerson->id,
                'duty_date' => $date->toDateString(),
                'assigned_by' => null,
                'is_manual' => false,
                'is_confirmed' => $isConfirmed,
                'status' => 'pending',
            ]);

            // Создаём статусы уборки для всех комнат филиала
            $this->createCleaningStatusesForDuty($duty);

            return $duty;
        });
    }

    /**
     * Ручная смена дежурного (для операторов).
     */
    public function changeDutyPerson(CleaningDuty $duty, int $newUserId, int $operatorId): CleaningDuty
    {
        $duty->update([
            'user_id' => $newUserId,
            'assigned_by' => $operatorId,
            'is_manual' => true,
        ]);

        return $duty->fresh();
    }

    /**
     * Создать статусы уборки для дежурства.
     */
    protected function createCleaningStatusesForDuty(CleaningDuty $duty): void
    {
        // Получаем все комнаты филиала
        $rooms = Room::where('branch_id', $duty->branch_id)->get();

        // Создаём статус для каждой комнаты
        foreach ($rooms as $room) {
            CleaningStatus::create([
                'cleaning_duty_id' => $duty->id,
                'room_id' => $room->id,
                'area_type' => 'room',
                'status' => 'dirty',
            ]);
        }

        // Создаём статус для общей зоны
        CleaningStatus::create([
            'cleaning_duty_id' => $duty->id,
            'room_id' => null,
            'area_type' => 'common_area',
            'status' => 'dirty',
        ]);
    }

    /**
     * Получить дежурства для календаря.
     */
    public function getDutiesForCalendar(int $branchId, Carbon $startDate, Carbon $endDate): Collection
    {
        return CleaningDuty::with(['user', 'cleaningStatuses'])
            ->where('branch_id', $branchId)
            ->whereBetween('duty_date', [$startDate->toDateString(), $endDate->toDateString()])
            ->orderBy('duty_date')
            ->get();
    }

    /**
     * Получить дежурство на конкретную дату.
     */
    public function getDutyForDate(int $branchId, Carbon $date): ?CleaningDuty
    {
        return CleaningDuty::with(['user', 'cleaningStatuses.room'])
            ->where('branch_id', $branchId)
            ->where('duty_date', $date->toDateString())
            ->first();
    }

    /**
     * Получить список сотрудников филиала с их баллами.
     * ВАЖНО: Только массажистки (роль masseuse), закреплённые за этим филиалом.
     */
    public function getEmployeesWithPoints(int $branchId): Collection
    {
        return DutyPoints::with('user')
            ->where('branch_id', $branchId)
            ->whereHas('user', function ($query) use ($branchId) {
                $query->where('is_active', 1)
                    ->where('branch_id', $branchId) // Только сотрудники этого филиала
                    ->role('masseuse'); // Только массажистки
            })
            ->orderBy('points', 'asc')
            ->orderByRaw('last_duty_date IS NULL DESC')
            ->orderBy('last_duty_date', 'asc')
            ->get();
    }

    /**
     * Получить всех массажисток филиала (для бронирований и дежурств).
     * ВАЖНО: Только роль masseuse, операторы не участвуют в дежурствах.
     */
    public function getEmployeesForBranch(int $branchId): Collection
    {
        return User::where('branch_id', $branchId)
            ->where('is_active', 1)
            ->role('masseuse')
            ->orderBy('name')
            ->get();
    }
}
