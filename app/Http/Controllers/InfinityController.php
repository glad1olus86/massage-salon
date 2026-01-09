<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\CleaningDuty;
use App\Models\MassageOrder;
use App\Services\Infinity\BookingService;
use App\Services\Infinity\DutyService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class InfinityController extends Controller
{
    protected BookingService $bookingService;
    protected DutyService $dutyService;

    public function __construct(BookingService $bookingService, DutyService $dutyService)
    {
        $this->bookingService = $bookingService;
        $this->dutyService = $dutyService;
    }

    /**
     * Display the Infinity dashboard
     */
    public function dashboard(Request $request)
    {
        $user = Auth::user();
        $creatorId = $user->creatorId();

        // Период для заказов (день, неделя, месяц)
        $period = $request->get('period', 'week');
        $periodStart = match($period) {
            'day' => now()->startOfDay(),
            'week' => now()->startOfWeek(),
            'month' => now()->startOfMonth(),
            default => now()->startOfWeek(),
        };

        // Реальные заказы
        $ordersQuery = MassageOrder::where('created_by', $creatorId)
            ->where('order_date', '>=', $periodStart)
            ->with(['client', 'employee', 'service']);
        
        $ordersCount = $ordersQuery->count();
        $recentOrders = MassageOrder::where('created_by', $creatorId)
            ->with(['client', 'employee', 'service'])
            ->orderBy('order_date', 'desc')
            ->orderBy('order_time', 'desc')
            ->limit(8)
            ->get();

        // Реальные дежурные на текущую неделю - группируем по сотруднику и филиалу
        $weekStart = now()->startOfWeek();
        $weekEnd = now()->endOfWeek();
        
        // Получаем все дежурства за неделю
        $allDuties = CleaningDuty::whereHas('branch', function($q) use ($creatorId) {
                $q->where('created_by', $creatorId);
            })
            ->whereBetween('duty_date', [$weekStart, $weekEnd])
            ->with(['user', 'branch'])
            ->orderBy('duty_date')
            ->get();
        
        // Группируем по user_id + branch_id
        $dutyEmployees = $allDuties->groupBy(function($duty) {
            return $duty->user_id . '_' . $duty->branch_id;
        })->map(function($duties) use ($weekStart) {
            $firstDuty = $duties->first();
            $avatarPath = $firstDuty->user?->getAttributes()['avatar'] ?? null;
            $hasAvatar = !empty($avatarPath) && \Storage::disk('public')->exists($avatarPath);
            
            // Создаём массив дней недели (пн-вс)
            $weekDays = [];
            for ($i = 0; $i < 7; $i++) {
                $dayDate = $weekStart->copy()->addDays($i);
                $dayDuty = $duties->first(fn($d) => $d->duty_date->format('Y-m-d') === $dayDate->format('Y-m-d'));
                $weekDays[] = (object)[
                    'date' => $dayDate,
                    'day_name' => $dayDate->format('D'),
                    'has_duty' => $dayDuty !== null,
                    'status' => $dayDuty?->status ?? null,
                    'is_completed' => $dayDuty?->status === 'completed',
                ];
            }
            
            return (object)[
                'name' => $firstDuty->user?->name ?? 'N/A',
                'branch' => $firstDuty->branch?->name ?? 'N/A',
                'branch_id' => $firstDuty->branch_id,
                'user_id' => $firstDuty->user_id,
                'avatar' => $hasAvatar ? asset('storage/' . $avatarPath) : null,
                'has_avatar' => $hasAvatar,
                'initials' => $this->getInitials($firstDuty->user?->name),
                'week_days' => $weekDays,
                'completed_count' => $duties->where('status', 'completed')->count(),
                'total_count' => $duties->count(),
            ];
        })->values();
        
        $dutyCount = $allDuties->count();

        // Calendar data from our system
        $branches = Branch::where('created_by', $creatorId)->get();
        
        // Выбранный филиал (из запроса или первый доступный)
        $branchId = $request->get('branch_id');
        if ($branchId) {
            $selectedBranch = $branches->firstWhere('id', $branchId);
        }
        if (!isset($selectedBranch) || !$selectedBranch) {
            $selectedBranch = $branches->first();
        }
        
        // Инициализируем баллы дежурств для всех сотрудников всех филиалов
        foreach ($branches as $branch) {
            $this->dutyService->initializePointsForBranch($branch->id, $creatorId);
        }
        
        $month = now()->month;
        $year = now()->year;
        $startDate = Carbon::create($year, $month, 1)->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();

        $calendarData = [];
        if ($selectedBranch) {
            $bookings = $this->bookingService->getBookingsForCalendar($selectedBranch->id, $startDate, $endDate);
            $duties = $this->dutyService->getDutiesForCalendar($selectedBranch->id, $startDate, $endDate);

            $bookingsByDate = $this->bookingService->groupBookingsByDate($bookings);
            $dutiesByDate = $duties->keyBy(fn($d) => $d->duty_date->format('Y-m-d'));

            $calendarData = $this->buildCalendarData($startDate, $endDate, $bookingsByDate, $dutiesByDate);
        }

        // Top employees data - только массажистки с ролью masseuse (Spatie)
        $performancePeriod = $request->get('performance_period', 'week');
        $performancePeriodStart = match($performancePeriod) {
            'day' => now()->startOfDay(),
            'week' => now()->startOfWeek(),
            'month' => now()->startOfMonth(),
            default => now()->startOfWeek(),
        };

        // Получаем только массажисток (роль Spatie masseuse) с подсчётом заказов и очков уборки
        $topEmployees = \App\Models\User::where('created_by', $creatorId)
            ->role('masseuse') // Spatie role
            ->withCount(['massageOrdersAsEmployee as orders_count' => function($q) use ($performancePeriodStart) {
                $q->where('order_date', '>=', $performancePeriodStart);
            }])
            ->with(['cleaningDuties' => function($q) use ($performancePeriodStart) {
                $q->where('duty_date', '>=', $performancePeriodStart)
                  ->with('cleaningStatuses');
            }])
            ->get()
            ->map(function($employee) {
                // Подсчёт коэффициента уборки (очки за выполненные уборки)
                $cleaningCoeff = 0;
                foreach ($employee->cleaningDuties as $duty) {
                    $cleanCount = $duty->cleaningStatuses->where('status', 'clean')->count();
                    $totalCount = $duty->cleaningStatuses->count();
                    if ($totalCount > 0) {
                        // 100 очков за каждую убранную комнату
                        $cleaningCoeff += $cleanCount * 100;
                    }
                    // Бонус за полностью выполненное дежурство
                    if ($duty->status === 'completed') {
                        $cleaningCoeff += 50;
                    }
                }
                $employee->cleaning_coeff = $cleaningCoeff;
                // Проверяем есть ли реальный аватар (не дефолтный) и существует ли файл
                $avatarPath = $employee->getAttributes()['avatar'] ?? null;
                $hasRealAvatar = !empty($avatarPath) && \Storage::disk('public')->exists($avatarPath);
                $employee->has_avatar = $hasRealAvatar;
                // Используем asset() для формирования URL
                if ($hasRealAvatar) {
                    $employee->avatar_url = asset('storage/' . $avatarPath);
                } else {
                    $employee->avatar_url = null;
                }
                $employee->initials = $this->getInitials($employee->name);
                return $employee;
            })
            ->sortByDesc('orders_count')
            ->take(10)
            ->values();

        // Leader employee - тот у кого больше всего заказов
        $leaderEmployee = $topEmployees->first();
        if (!$leaderEmployee || $leaderEmployee->orders_count == 0) {
            $leaderEmployee = (object)[
                'name' => '-',
                'avatar' => null,
                'avatar_url' => null,
                'has_avatar' => false,
                'orders_count' => 0,
                'initials' => '-'
            ];
        }

        // Chart data - заказы за выбранный период
        $chartPeriod = $request->get('chart_period', 'week');
        
        $chartData = match($chartPeriod) {
            'month' => collect(range(29, 0))->map(function ($daysAgo) use ($creatorId) {
                $date = now()->subDays($daysAgo);
                return [
                    'date' => $date->format('d.m'),
                    'count' => MassageOrder::where('created_by', $creatorId)
                        ->whereDate('order_date', $date)
                        ->where('status', '!=', 'cancelled')
                        ->count()
                ];
            }),
            'year' => collect(range(11, 0))->map(function ($monthsAgo) use ($creatorId) {
                $date = now()->subMonths($monthsAgo);
                return [
                    'date' => $date->translatedFormat('M'),
                    'count' => MassageOrder::where('created_by', $creatorId)
                        ->whereYear('order_date', $date->year)
                        ->whereMonth('order_date', $date->month)
                        ->where('status', '!=', 'cancelled')
                        ->count()
                ];
            }),
            default => collect(range(6, 0))->map(function ($daysAgo) use ($creatorId) {
                $date = now()->subDays($daysAgo);
                return [
                    'date' => $date->format('d.m'),
                    'count' => MassageOrder::where('created_by', $creatorId)
                        ->whereDate('order_date', $date)
                        ->where('status', '!=', 'cancelled')
                        ->count()
                ];
            }),
        };
        
        $chartLabels = $chartData->pluck('date')->toArray();
        $chartValues = $chartData->pluck('count')->toArray();

        return view('infinity.dashboard', compact(
            'ordersCount',
            'recentOrders',
            'dutyCount',
            'dutyEmployees',
            'calendarData',
            'branches',
            'selectedBranch',
            'startDate',
            'month',
            'year',
            'topEmployees',
            'leaderEmployee',
            'period',
            'chartPeriod',
            'chartLabels',
            'chartValues'
        ));
    }

    /**
     * Build calendar data for dashboard
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
     * Display employees list
     */
    public function employees()
    {
        // Получаем пользователей компании
        $employees = \App\Models\User::where('created_by', Auth::user()->creatorId())
            ->orWhere('id', Auth::user()->creatorId())
            ->with('branch')
            ->orderBy('name')
            ->get();
        
        return view('infinity.employees.index', compact('employees'));
    }

    /**
     * Display orders list
     */
    public function orders()
    {
        return view('infinity.orders.index');
    }

    /**
     * Display calendar
     */
    public function calendar()
    {
        return view('infinity.calendar');
    }

    /**
     * Get initials from name
     */
    protected function getInitials(?string $name): string
    {
        if (!$name) return '?';
        
        $words = explode(' ', trim($name));
        $initials = '';
        
        foreach ($words as $word) {
            if (!empty($word)) {
                $initials .= mb_strtoupper(mb_substr($word, 0, 1));
            }
            if (strlen($initials) >= 2) break;
        }
        
        return $initials ?: '?';
    }
}
