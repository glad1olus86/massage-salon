<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;

class AuditService
{
    /**
     * Универсальный метод для логирования события
     */
    public static function log($eventType, $description, $subject = null, $oldValues = null, $newValues = null)
    {
        return AuditLog::create([
            'user_id' => Auth::id(),
            'event_type' => $eventType,
            'description' => $description,
            'subject_type' => $subject ? get_class($subject) : null,
            'subject_id' => $subject ? $subject->id : null,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'created_by' => Auth::user() ? Auth::user()->creatorId() : 1,
        ]);
    }

    /**
     * Логирование создания работника
     */
    public static function logWorkerCreated($worker)
    {
        return self::log(
            'worker.created',
            __('Worker created') . ": {$worker->first_name} {$worker->last_name}",
            $worker,
            null,
            [
                'name' => "{$worker->first_name} {$worker->last_name}",
                'dob' => $worker->dob,
                'gender' => $worker->gender,
                'nationality' => $worker->nationality,
            ]
        );
    }

    /**
     * Логирование обновления работника
     */
    public static function logWorkerUpdated($worker, $oldValues)
    {
        $changes = [];
        foreach ($oldValues as $key => $oldValue) {
            if ($worker->{$key} != $oldValue) {
                $changes[$key] = [
                    'old' => $oldValue,
                    'new' => $worker->{$key}
                ];
            }
        }

        if (!empty($changes)) {
            return self::log(
                'worker.updated',
                __('Worker updated') . ": {$worker->first_name} {$worker->last_name}",
                $worker,
                $oldValues,
                $worker->only(array_keys($oldValues))
            );
        }
    }

    /**
     * Логирование удаления работника
     */
    public static function logWorkerDeleted($worker)
    {
        return self::log(
            'worker.deleted',
            __('Worker deleted') . ": {$worker->first_name} {$worker->last_name}",
            $worker,
            [
                'name' => "{$worker->first_name} {$worker->last_name}",
                'dob' => $worker->dob,
            ],
            null
        );
    }

    /**
     * Логирование заселения работника
     */
    public static function logWorkerCheckedIn($assignment)
    {
        $worker = $assignment->worker;
        $room = $assignment->room;
        $hotel = $assignment->hotel;

        return self::log(
            'worker.checked_in',
            __('Checked in') . ": {$worker->first_name} {$worker->last_name} → " . __('Hotel') . " \"{$hotel->name}\", " . __('Room') . " {$room->room_number}",
            $worker,
            null,
            [
                'hotel' => $hotel->name,
                'room' => $room->room_number,
                'check_in_date' => $assignment->check_in_date,
            ]
        );
    }

    /**
     * Логирование выселения работника
     */
    public static function logWorkerCheckedOut($assignment)
    {
        $worker = $assignment->worker;
        $room = $assignment->room;
        $hotel = $assignment->hotel;

        return self::log(
            'worker.checked_out',
            __('Checked out') . ": {$worker->first_name} {$worker->last_name} ← " . __('Hotel') . " \"{$hotel->name}\", " . __('Room') . " {$room->room_number}",
            $worker,
            [
                'hotel' => $hotel->name,
                'room' => $room->room_number,
                'check_in_date' => $assignment->check_in_date,
            ],
            [
                'check_out_date' => $assignment->check_out_date,
            ]
        );
    }

    /**
     * Логирование устройства на работу
     */
    public static function logWorkerHired($workAssignment)
    {
        $worker = $workAssignment->worker;
        $workPlace = $workAssignment->workPlace;

        return self::log(
            'worker.hired',
            __('Hired') . ": {$worker->first_name} {$worker->last_name} → {$workPlace->name}",
            $worker,
            null,
            [
                'work_place' => $workPlace->name,
                'started_at' => $workAssignment->started_at,
            ]
        );
    }

    /**
     * Логирование увольнения
     */
    public static function logWorkerDismissed($workAssignment)
    {
        $worker = $workAssignment->worker;
        $workPlace = $workAssignment->workPlace;

        return self::log(
            'worker.dismissed',
            __('Dismissed') . ": {$worker->first_name} {$worker->last_name} ← {$workPlace->name}",
            $worker,
            [
                'work_place' => $workPlace->name,
                'started_at' => $workAssignment->started_at,
            ],
            [
                'ended_at' => $workAssignment->ended_at,
            ]
        );
    }

    /**
     * Логирование создания комнаты
     */
    public static function logRoomCreated($room)
    {
        $hotel = $room->hotel;

        return self::log(
            'room.created',
            __('Room created') . ": {$room->room_number} " . __('in hotel') . " \"{$hotel->name}\"",
            $room,
            null,
            [
                'room_number' => $room->room_number,
                'hotel' => $hotel->name,
                'capacity' => $room->capacity,
            ]
        );
    }

    /**
     * Логирование обновления комнаты
     */
    public static function logRoomUpdated($room, $oldValues)
    {
        $hotel = $room->hotel;

        return self::log(
            'room.updated',
            __('Room updated') . ": {$room->room_number} " . __('in hotel') . " \"{$hotel->name}\"",
            $room,
            $oldValues,
            $room->only(array_keys($oldValues))
        );
    }

    /**
     * Логирование удаления комнаты
     */
    public static function logRoomDeleted($room)
    {
        return self::log(
            'room.deleted',
            __('Room deleted') . ": {$room->room_number}",
            $room,
            ['room_number' => $room->room_number, 'capacity' => $room->capacity],
            null
        );
    }

    /**
     * Логирование создания рабочего места
     */
    public static function logWorkPlaceCreated($workPlace)
    {
        return self::log(
            'work_place.created',
            __('Work place created') . ": {$workPlace->name}",
            $workPlace,
            null,
            [
                'name' => $workPlace->name,
                'address' => $workPlace->address,
            ]
        );
    }

    /**
     * Логирование обновления рабочего места
     */
    public static function logWorkPlaceUpdated($workPlace, $oldValues)
    {
        return self::log(
            'work_place.updated',
            __('Work place updated') . ": {$workPlace->name}",
            $workPlace,
            $oldValues,
            $workPlace->only(array_keys($oldValues))
        );
    }

    /**
     * Логирование удаления рабочего места
     */
    public static function logWorkPlaceDeleted($workPlace)
    {
        return self::log(
            'work_place.deleted',
            __('Work place deleted') . ": {$workPlace->name}",
            $workPlace,
            ['name' => $workPlace->name, 'address' => $workPlace->address],
            null
        );
    }

    /**
     * Логирование создания отеля
     */
    public static function logHotelCreated($hotel)
    {
        return self::log(
            'hotel.created',
            __('Hotel created') . ": {$hotel->name}",
            $hotel,
            null,
            [
                'name' => $hotel->name,
                'address' => $hotel->address,
            ]
        );
    }

    /**
     * Логирование обновления отеля
     */
    public static function logHotelUpdated($hotel, $oldValues)
    {
        return self::log(
            'hotel.updated',
            __('Hotel updated') . ": {$hotel->name}",
            $hotel,
            $oldValues,
            $hotel->only(array_keys($oldValues))
        );
    }

    /**
     * Логирование удаления отеля
     */
    public static function logHotelDeleted($hotel)
    {
        return self::log(
            'hotel.deleted',
            __('Hotel deleted') . ": {$hotel->name}",
            $hotel,
            ['name' => $hotel->name, 'address' => $hotel->address],
            null
        );
    }

    /**
     * Логирование изменения ответственного
     */
    public static function logResponsibleChanged($entity, $oldResponsibleId, $newResponsibleId)
    {
        $oldUser = $oldResponsibleId ? \App\Models\User::find($oldResponsibleId) : null;
        $newUser = \App\Models\User::find($newResponsibleId);
        
        $entityType = class_basename($entity);
        $entityName = $entity->name ?? $entity->first_name ?? $entity->id;
        
        return self::log(
            'responsible.changed',
            __('Responsible changed') . ": {$entityType} \"{$entityName}\"",
            $entity,
            ['responsible' => $oldUser ? $oldUser->name : null],
            ['responsible' => $newUser ? $newUser->name : null]
        );
    }

    /**
     * Логирование назначения координатора на менеджера
     */
    public static function logCuratorAssigned($manager, $curator)
    {
        return self::log(
            'curator.assigned',
            __('Curator assigned') . ": {$curator->name} → {$manager->name}",
            $curator,
            null,
            ['manager' => $manager->name, 'curator' => $curator->name]
        );
    }

    /**
     * Логирование удаления координатора от менеджера
     */
    public static function logCuratorRemoved($manager, $curator)
    {
        return self::log(
            'curator.removed',
            __('Curator removed') . ": {$curator->name} ← {$manager->name}",
            $curator,
            ['manager' => $manager->name, 'curator' => $curator->name],
            null
        );
    }
}
