<?php

namespace App\Services\Infinity;

use App\Models\RoomBooking;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class BookingService
{
    /**
     * Проверка доступности временного слота.
     * Возвращает true если слот свободен.
     */
    public function isSlotAvailable(
        int $roomId,
        string $date,
        string $startTime,
        string $endTime,
        ?int $excludeBookingId = null
    ): bool {
        $query = RoomBooking::where('room_id', $roomId)
            ->where('booking_date', $date)
            ->where('status', '!=', 'cancelled')
            ->where(function ($q) use ($startTime, $endTime) {
                // Проверяем пересечение временных интервалов
                $q->where(function ($inner) use ($startTime, $endTime) {
                    // Новое бронирование начинается во время существующего
                    $inner->where('start_time', '<', $endTime)
                          ->where('end_time', '>', $startTime);
                });
            });

        // Исключаем текущее бронирование при редактировании
        if ($excludeBookingId) {
            $query->where('id', '!=', $excludeBookingId);
        }

        return !$query->exists();
    }

    /**
     * Создание бронирования.
     */
    public function createBooking(array $data): RoomBooking
    {
        return DB::transaction(function () use ($data) {
            // Проверяем доступность слота
            if (!$this->isSlotAvailable(
                $data['room_id'],
                $data['booking_date'],
                $data['start_time'],
                $data['end_time']
            )) {
                throw new \Exception(__('Этот временной слот уже занят.'));
            }

            return RoomBooking::create([
                'room_id' => $data['room_id'],
                'branch_id' => $data['branch_id'],
                'user_id' => $data['user_id'],
                'client_id' => $data['client_id'] ?? null,
                'booking_date' => $data['booking_date'],
                'start_time' => $data['start_time'],
                'end_time' => $data['end_time'],
                'status' => $data['status'] ?? 'confirmed',
                'notes' => $data['notes'] ?? null,
                'created_by' => $data['created_by'],
            ]);
        });
    }

    /**
     * Обновление бронирования.
     */
    public function updateBooking(RoomBooking $booking, array $data): RoomBooking
    {
        return DB::transaction(function () use ($booking, $data) {
            // Проверяем доступность слота (исключая текущее бронирование)
            $roomId = $data['room_id'] ?? $booking->room_id;
            $date = $data['booking_date'] ?? $booking->booking_date;
            $startTime = $data['start_time'] ?? $booking->start_time;
            $endTime = $data['end_time'] ?? $booking->end_time;

            if (!$this->isSlotAvailable($roomId, $date, $startTime, $endTime, $booking->id)) {
                throw new \Exception(__('Этот временной слот уже занят.'));
            }

            $booking->update($data);

            return $booking->fresh();
        });
    }

    /**
     * Получение бронирований для календаря.
     */
    public function getBookingsForCalendar(int $branchId, Carbon $startDate, Carbon $endDate): Collection
    {
        return RoomBooking::with(['room', 'user', 'client'])
            ->where('branch_id', $branchId)
            ->whereBetween('booking_date', [$startDate->toDateString(), $endDate->toDateString()])
            ->where('status', '!=', 'cancelled')
            ->orderBy('booking_date')
            ->orderBy('start_time')
            ->get();
    }

    /**
     * Получение бронирований на конкретную дату.
     */
    public function getBookingsForDate(int $branchId, Carbon $date): Collection
    {
        return RoomBooking::with(['room', 'user', 'client'])
            ->where('branch_id', $branchId)
            ->where('booking_date', $date->toDateString())
            ->where('status', '!=', 'cancelled')
            ->orderBy('start_time')
            ->get();
    }

    /**
     * Получение бронирований пользователя.
     */
    public function getUserBookings(int $userId, ?Carbon $startDate = null, ?Carbon $endDate = null): Collection
    {
        $query = RoomBooking::with(['room', 'branch', 'client'])
            ->where('user_id', $userId)
            ->where('status', '!=', 'cancelled');

        if ($startDate && $endDate) {
            $query->whereBetween('booking_date', [$startDate->toDateString(), $endDate->toDateString()]);
        }

        return $query->orderBy('booking_date')
            ->orderBy('start_time')
            ->get();
    }

    /**
     * Отмена бронирования.
     */
    public function cancelBooking(RoomBooking $booking): bool
    {
        // Нельзя отменить прошедшее бронирование
        if (Carbon::parse($booking->booking_date)->lt(today())) {
            throw new \Exception(__('Нельзя отменить прошедшее бронирование.'));
        }

        $booking->update(['status' => 'cancelled']);

        return true;
    }

    /**
     * Получение доступных слотов для комнаты на дату.
     */
    public function getAvailableSlots(int $roomId, string $date, int $slotDurationMinutes = 60): array
    {
        $workingHoursStart = '09:00';
        $workingHoursEnd = '23:00';

        // Получаем все бронирования на эту дату
        $bookings = RoomBooking::where('room_id', $roomId)
            ->where('booking_date', $date)
            ->where('status', '!=', 'cancelled')
            ->orderBy('start_time')
            ->get(['start_time', 'end_time']);

        $availableSlots = [];
        $currentTime = Carbon::parse($date . ' ' . $workingHoursStart);
        $endOfDay = Carbon::parse($date . ' ' . $workingHoursEnd);

        foreach ($bookings as $booking) {
            $bookingStart = Carbon::parse($date . ' ' . $booking->start_time);
            $bookingEnd = Carbon::parse($date . ' ' . $booking->end_time);

            // Добавляем свободные слоты до этого бронирования
            while ($currentTime->copy()->addMinutes($slotDurationMinutes)->lte($bookingStart)) {
                $slotEnd = $currentTime->copy()->addMinutes($slotDurationMinutes);
                $availableSlots[] = [
                    'start' => $currentTime->format('H:i'),
                    'end' => $slotEnd->format('H:i'),
                ];
                $currentTime = $slotEnd;
            }

            // Перемещаем текущее время на конец бронирования
            if ($currentTime->lt($bookingEnd)) {
                $currentTime = $bookingEnd->copy();
            }
        }

        // Добавляем оставшиеся слоты до конца рабочего дня
        while ($currentTime->copy()->addMinutes($slotDurationMinutes)->lte($endOfDay)) {
            $slotEnd = $currentTime->copy()->addMinutes($slotDurationMinutes);
            $availableSlots[] = [
                'start' => $currentTime->format('H:i'),
                'end' => $slotEnd->format('H:i'),
            ];
            $currentTime = $slotEnd;
        }

        return $availableSlots;
    }

    /**
     * Получение статистики бронирований для дашборда.
     */
    public function getBookingStats(int $branchId, Carbon $date): array
    {
        $bookings = RoomBooking::where('branch_id', $branchId)
            ->where('booking_date', $date->toDateString())
            ->where('status', '!=', 'cancelled')
            ->get();

        return [
            'total' => $bookings->count(),
            'pending' => $bookings->where('status', 'pending')->count(),
            'confirmed' => $bookings->where('status', 'confirmed')->count(),
            'completed' => $bookings->where('status', 'completed')->count(),
        ];
    }

    /**
     * Группировка бронирований по дням для календаря.
     */
    public function groupBookingsByDate(Collection $bookings): Collection
    {
        return $bookings->groupBy(function ($booking) {
            return $booking->booking_date->format('Y-m-d');
        });
    }

    /**
     * Проверка наличия бронирования пользователя на конкретную дату.
     */
    public function hasUserBookingForDate(int $roomId, int $userId, Carbon $date): bool
    {
        return RoomBooking::where('room_id', $roomId)
            ->where('user_id', $userId)
            ->where('booking_date', $date->toDateString())
            ->where('status', '!=', 'cancelled')
            ->exists();
    }

    /**
     * Подсчёт последовательных дней бронирования комнаты пользователем.
     * Считает существующие бронирования до и после указанного диапазона.
     */
    public function getConsecutiveBookingDays(
        int $roomId,
        int $userId,
        string $startDate,
        string $endDate
    ): int {
        $start = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);

        // Ищем бронирования до начальной даты
        $daysBefore = 0;
        $checkDate = $start->copy()->subDay();
        while ($this->hasUserBookingForDate($roomId, $userId, $checkDate)) {
            $daysBefore++;
            $checkDate->subDay();
        }

        // Ищем бронирования после конечной даты
        $daysAfter = 0;
        $checkDate = $end->copy()->addDay();
        while ($this->hasUserBookingForDate($roomId, $userId, $checkDate)) {
            $daysAfter++;
            $checkDate->addDay();
        }

        return $daysBefore + $daysAfter;
    }

    /**
     * Проверка доступности диапазона дат для бронирования.
     */
    public function checkRangeAvailability(
        int $roomId,
        string $startDate,
        string $endDate,
        string $startTime,
        string $endTime,
        int $userId
    ): array {
        $start = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);
        $unavailableDates = [];
        $totalDays = $start->diffInDays($end) + 1;

        // Проверяем каждый день в диапазоне
        $current = $start->copy();
        while ($current->lte($end)) {
            if (!$this->isSlotAvailable($roomId, $current->toDateString(), $startTime, $endTime)) {
                $unavailableDates[] = $current->format('d.m.Y');
            }
            $current->addDay();
        }

        // Проверяем лимит 7 последовательных дней
        $consecutiveDays = $this->getConsecutiveBookingDays($roomId, $userId, $startDate, $endDate);
        $wouldExceedLimit = ($consecutiveDays + $totalDays) > 7;

        return [
            'available' => empty($unavailableDates) && !$wouldExceedLimit,
            'unavailable_dates' => $unavailableDates,
            'total_days' => $totalDays,
            'consecutive_days' => $consecutiveDays,
            'would_exceed_limit' => $wouldExceedLimit,
            'message' => $this->buildAvailabilityMessage($unavailableDates, $wouldExceedLimit, $totalDays),
        ];
    }

    /**
     * Формирование сообщения о доступности.
     */
    protected function buildAvailabilityMessage(array $unavailableDates, bool $wouldExceedLimit, int $totalDays): string
    {
        if (!empty($unavailableDates)) {
            return __('Следующие даты недоступны: ') . implode(', ', $unavailableDates);
        }

        if ($wouldExceedLimit) {
            return __('Вы уже забронировали эту комнату на неделю. Выберите другую комнату.');
        }

        if ($totalDays === 1) {
            return __('Слот доступен');
        }

        return __('Доступно для бронирования: ') . $totalDays . ' ' . $this->pluralizeDays($totalDays);
    }

    /**
     * Склонение слова "день".
     */
    protected function pluralizeDays(int $count): string
    {
        $mod10 = $count % 10;
        $mod100 = $count % 100;

        if ($mod10 === 1 && $mod100 !== 11) {
            return __('день');
        }

        if ($mod10 >= 2 && $mod10 <= 4 && ($mod100 < 10 || $mod100 >= 20)) {
            return __('дня');
        }

        return __('дней');
    }

    /**
     * Создание многодневного бронирования.
     */
    public function createMultiDayBooking(array $data, int $userId): array
    {
        return DB::transaction(function () use ($data, $userId) {
            $start = Carbon::parse($data['booking_date']);
            $end = Carbon::parse($data['end_date']);
            $createdBookings = [];

            // Проверяем доступность всех дней перед созданием
            $current = $start->copy();
            while ($current->lte($end)) {
                if (!$this->isSlotAvailable(
                    $data['room_id'],
                    $current->toDateString(),
                    $data['start_time'],
                    $data['end_time']
                )) {
                    throw new \Exception(__('Слот на дату :date уже занят.', ['date' => $current->format('d.m.Y')]));
                }
                $current->addDay();
            }

            // Создаём бронирования для каждого дня
            $current = $start->copy();
            while ($current->lte($end)) {
                $booking = RoomBooking::create([
                    'room_id' => $data['room_id'],
                    'branch_id' => $data['branch_id'],
                    'user_id' => $userId,
                    'client_id' => $data['client_id'] ?? null,
                    'booking_date' => $current->toDateString(),
                    'start_time' => $data['start_time'],
                    'end_time' => $data['end_time'],
                    'status' => 'confirmed',
                    'notes' => $data['notes'] ?? null,
                    'created_by' => $userId,
                ]);
                $createdBookings[] = $booking;
                $current->addDay();
            }

            return $createdBookings;
        });
    }
}
