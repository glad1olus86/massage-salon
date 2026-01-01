<?php

namespace App\Http\Controllers\Infinity;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Room;
use App\Models\User;
use App\Services\Infinity\BookingService;
use App\Services\Infinity\DutyService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CalendarController extends Controller
{
    protected BookingService $bookingService;
    protected DutyService $dutyService;

    public function __construct(BookingService $bookingService, DutyService $dutyService)
    {
        $this->bookingService = $bookingService;
        $this->dutyService = $dutyService;
    }

    /**
     * Отображение календаря.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $creatorId = $user->creatorId();

        // Получаем филиалы пользователя
        $branches = Branch::where('created_by', $creatorId)->get();

        // Выбранный филиал (из запроса или первый доступный)
        $branchId = $request->get('branch_id', $branches->first()?->id);

        if (!$branchId) {
            return view('infinity.calendar.index', [
                'branches' => $branches,
                'selectedBranch' => null,
                'calendarData' => [],
                'month' => now()->month,
                'year' => now()->year,
            ]);
        }

        // Проверяем доступ к филиалу
        $branch = Branch::where('id', $branchId)
            ->where('created_by', $creatorId)
            ->firstOrFail();

        // Месяц и год
        $month = (int) $request->get('month', now()->month);
        $year = (int) $request->get('year', now()->year);

        // Даты начала и конца месяца
        $startDate = Carbon::create($year, $month, 1)->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();
        
        // Автоматически инициализируем баллы для сотрудников филиала
        $this->dutyService->initializePointsForBranch($branchId, $creatorId);
        
        // Утверждаем дежурства текущей недели
        $this->dutyService->confirmCurrentWeekDuties($branchId);
        
        // Автоматически назначаем дежурных на текущую и следующую неделю
        $weekStart = now()->startOfWeek();
        $nextWeekEnd = now()->addWeek()->endOfWeek();
        $this->dutyService->assignDutiesForPeriod($branchId, $weekStart, $nextWeekEnd);
        
        // Пересчитываем предварительные дежурства следующей недели
        $this->dutyService->recalculateFutureDuties($branchId);

        // Получаем бронирования и дежурства
        $bookings = $this->bookingService->getBookingsForCalendar($branchId, $startDate, $endDate);
        $duties = $this->dutyService->getDutiesForCalendar($branchId, $startDate, $endDate);

        // Группируем по дням
        $bookingsByDate = $this->bookingService->groupBookingsByDate($bookings);
        $dutiesByDate = $duties->keyBy(fn($d) => $d->duty_date->format('Y-m-d'));

        // Формируем данные календаря
        $calendarData = $this->buildCalendarData($startDate, $endDate, $bookingsByDate, $dutiesByDate);

        return view('infinity.calendar.index', [
            'branches' => $branches,
            'selectedBranch' => $branch,
            'calendarData' => $calendarData,
            'month' => $month,
            'year' => $year,
            'startDate' => $startDate,
        ]);
    }

    /**
     * Детали конкретного дня.
     */
    public function dayDetails(Request $request, string $date)
    {
        $user = Auth::user();
        $creatorId = $user->creatorId();

        $branchId = $request->get('branch_id');
        if (!$branchId) {
            return response()->json(['error' => 'Branch ID required'], 400);
        }

        // Проверяем доступ к филиалу
        $branch = Branch::where('id', $branchId)
            ->where('created_by', $creatorId)
            ->firstOrFail();

        $dateCarbon = Carbon::parse($date);

        // Получаем бронирования на этот день
        $bookings = $this->bookingService->getBookingsForDate($branchId, $dateCarbon);

        // Получаем дежурство на этот день
        $duty = $this->dutyService->getDutyForDate($branchId, $dateCarbon);

        // Получаем комнаты филиала
        $rooms = Room::where('branch_id', $branchId)->get();

        // Получаем сотрудников филиала для смены дежурного
        $employees = $this->dutyService->getEmployeesWithPoints($branchId);
        
        // Получаем всех сотрудников филиала для бронирований
        $branchEmployees = $this->dutyService->getEmployeesForBranch($branchId);

        if ($request->ajax()) {
            return response()->json([
                'date' => $dateCarbon->format('Y-m-d'),
                'formatted_date' => $dateCarbon->translatedFormat('j F Y (l)'),
                'bookings' => $bookings,
                'duty' => $duty,
                'rooms' => $rooms,
                'employees' => $employees,
                'branchEmployees' => $branchEmployees,
            ]);
        }

        return view('infinity.calendar.day-details', [
            'date' => $dateCarbon,
            'branch' => $branch,
            'bookings' => $bookings,
            'duty' => $duty,
            'rooms' => $rooms,
            'employees' => $employees,
            'branchEmployees' => $branchEmployees,
        ]);
    }

    /**
     * Построение данных календаря.
     */
    protected function buildCalendarData(Carbon $startDate, Carbon $endDate, $bookingsByDate, $dutiesByDate): array
    {
        $calendarData = [];
        $currentDate = $startDate->copy();

        while ($currentDate->lte($endDate)) {
            $dateKey = $currentDate->format('Y-m-d');

            $calendarData[$dateKey] = [
                'date' => $currentDate->copy(),
                'day' => $currentDate->day,
                'isToday' => $currentDate->isToday(),
                'isWeekend' => $currentDate->isWeekend(),
                'bookings' => $bookingsByDate->get($dateKey, collect()),
                'bookingsCount' => $bookingsByDate->get($dateKey, collect())->count(),
                'duty' => $dutiesByDate->get($dateKey),
            ];

            $currentDate->addDay();
        }

        return $calendarData;
    }

    /**
     * AJAX: Получить данные календаря для месяца.
     */
    public function getMonthData(Request $request)
    {
        $user = Auth::user();
        $creatorId = $user->creatorId();

        $branchId = $request->get('branch_id');
        $month = (int) $request->get('month', now()->month);
        $year = (int) $request->get('year', now()->year);

        if (!$branchId) {
            return response()->json(['error' => 'Branch ID required'], 400);
        }

        // Проверяем доступ к филиалу
        $branch = Branch::where('id', $branchId)
            ->where('created_by', $creatorId)
            ->first();

        if (!$branch) {
            return response()->json(['error' => 'Branch not found'], 404);
        }

        $startDate = Carbon::create($year, $month, 1)->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();

        $bookings = $this->bookingService->getBookingsForCalendar($branchId, $startDate, $endDate);
        $duties = $this->dutyService->getDutiesForCalendar($branchId, $startDate, $endDate);

        $bookingsByDate = $this->bookingService->groupBookingsByDate($bookings);
        $dutiesByDate = $duties->keyBy(fn($d) => $d->duty_date->format('Y-m-d'));

        $calendarData = $this->buildCalendarData($startDate, $endDate, $bookingsByDate, $dutiesByDate);

        return response()->json([
            'month' => $month,
            'year' => $year,
            'monthName' => $startDate->translatedFormat('F Y'),
            'calendarData' => $calendarData,
        ]);
    }
}
