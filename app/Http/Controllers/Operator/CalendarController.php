<?php

namespace App\Http\Controllers\Operator;

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
     * Display calendar for operator's branch only.
     */
    public function index(Request $request)
    {
        $operator = Auth::user();

        // Получаем филиал оператора
        $operatorBranchId = $operator->branch_id;

        if (!$operatorBranchId) {
            return view('operator.calendar.index', [
                'branches' => collect(),
                'selectedBranch' => null,
                'calendarData' => [],
                'month' => now()->month,
                'year' => now()->year,
            ]);
        }

        $branch = Branch::find($operatorBranchId);

        if (!$branch) {
            return view('operator.calendar.index', [
                'branches' => collect(),
                'selectedBranch' => null,
                'calendarData' => [],
                'month' => now()->month,
                'year' => now()->year,
            ]);
        }

        // Месяц и год
        $month = (int) $request->get('month', now()->month);
        $year = (int) $request->get('year', now()->year);

        // Даты начала и конца месяца
        $startDate = Carbon::create($year, $month, 1)->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();

        // Получаем бронирования и дежурства
        $bookings = $this->bookingService->getBookingsForCalendar($operatorBranchId, $startDate, $endDate);
        $duties = $this->dutyService->getDutiesForCalendar($operatorBranchId, $startDate, $endDate);

        // Группируем по дням
        $bookingsByDate = $this->bookingService->groupBookingsByDate($bookings);
        $dutiesByDate = $duties->keyBy(fn($d) => $d->duty_date->format('Y-m-d'));

        // Формируем данные календаря
        $calendarData = $this->buildCalendarData($startDate, $endDate, $bookingsByDate, $dutiesByDate);

        return view('operator.calendar.index', [
            'branches' => collect([$branch]),
            'selectedBranch' => $branch,
            'calendarData' => $calendarData,
            'month' => $month,
            'year' => $year,
            'startDate' => $startDate,
        ]);
    }

    /**
     * Day details for operator.
     */
    public function dayDetails(Request $request, string $date)
    {
        $operator = Auth::user();
        $operatorBranchId = $operator->branch_id;

        if (!$operatorBranchId) {
            return response()->json(['error' => 'No branch assigned'], 400);
        }

        $branch = Branch::find($operatorBranchId);
        if (!$branch) {
            return response()->json(['error' => 'Branch not found'], 404);
        }

        $dateCarbon = Carbon::parse($date);

        // Получаем бронирования на этот день
        $bookings = $this->bookingService->getBookingsForDate($operatorBranchId, $dateCarbon);

        // Получаем дежурство на этот день
        $duty = $this->dutyService->getDutyForDate($operatorBranchId, $dateCarbon);

        // Получаем комнаты филиала
        $rooms = Room::where('branch_id', $operatorBranchId)->get();

        // Получаем сотрудников филиала для смены дежурного
        $employees = $this->dutyService->getEmployeesWithPoints($operatorBranchId);

        // Получаем всех сотрудников филиала для бронирований
        $branchEmployees = $this->dutyService->getEmployeesForBranch($operatorBranchId);

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

        return view('operator.calendar.day-details', [
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
     * Build calendar data.
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
     * AJAX: Get month data.
     */
    public function getMonthData(Request $request)
    {
        $operator = Auth::user();
        $operatorBranchId = $operator->branch_id;

        $month = (int) $request->get('month', now()->month);
        $year = (int) $request->get('year', now()->year);

        if (!$operatorBranchId) {
            return response()->json(['error' => 'No branch assigned'], 400);
        }

        $branch = Branch::find($operatorBranchId);
        if (!$branch) {
            return response()->json(['error' => 'Branch not found'], 404);
        }

        $startDate = Carbon::create($year, $month, 1)->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();

        $bookings = $this->bookingService->getBookingsForCalendar($operatorBranchId, $startDate, $endDate);
        $duties = $this->dutyService->getDutiesForCalendar($operatorBranchId, $startDate, $endDate);

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
