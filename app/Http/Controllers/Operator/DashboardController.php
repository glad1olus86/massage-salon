<?php

namespace App\Http\Controllers\Operator;

use App\Http\Controllers\Controller;
use App\Models\CleaningDuty;
use App\Models\MassageOrder;
use App\Models\User;
use App\Services\Infinity\BookingService;
use App\Services\Infinity\DutyService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    protected BookingService $bookingService;
    protected DutyService $dutyService;

    public function __construct(BookingService $bookingService, DutyService $dutyService)
    {
        $this->bookingService = $bookingService;
        $this->dutyService = $dutyService;
    }

    /**
     * Display the operator dashboard.
     */
    public function index(Request $request)
    {
        $operator = Auth::user();
        $subordinateIds = $operator->getSubordinateIds();

        // Период для заказов (день, неделя, месяц)
        $period = $request->get('period', 'month');
        $periodStart = match ($period) {
            'day' => now()->startOfDay(),
            'week' => now()->startOfWeek(),
            'month' => now()->startOfMonth(),
            default => now()->startOfMonth(),
        };

        // Заказы только от подопечных
        $orders = MassageOrder::whereIn('employee_id', $subordinateIds)
            ->where('order_date', '>=', $periodStart)
            ->with(['client', 'employee', 'service'])
            ->orderBy('order_date', 'desc')
            ->orderBy('order_time', 'desc')
            ->get();

        $ordersCount = $orders->count();
        $totalEarnings = $orders->sum('amount');

        // Расчёт дохода оператора на основе доли от услуг (только не отменённые)
        $operatorIncome = 0;
        foreach ($orders as $order) {
            if ($order->service && $order->status !== 'cancelled') {
                $duration = $order->duration ?? 60;
                $share = match ($duration) {
                    60 => $order->service->operator_share_60,
                    90 => $order->service->operator_share_90,
                    120 => $order->service->operator_share_120,
                    default => $order->service->operator_share_60,
                };
                $operatorIncome += $share ?? 0;
            }
        }

        // Доход за текущий месяц (для модуля)
        $monthStart = now()->startOfMonth();
        $monthOrders = MassageOrder::whereIn('employee_id', $subordinateIds)
            ->where('order_date', '>=', $monthStart)
            ->where('status', '!=', 'cancelled')
            ->with('service')
            ->get();
        
        $monthlyOperatorIncome = 0;
        foreach ($monthOrders as $order) {
            if ($order->service) {
                $duration = $order->duration ?? 60;
                $share = match ($duration) {
                    60 => $order->service->operator_share_60,
                    90 => $order->service->operator_share_90,
                    120 => $order->service->operator_share_120,
                    default => $order->service->operator_share_60,
                };
                $monthlyOperatorIncome += $share ?? 0;
            }
        }

        // Последние 8 заказов для модуля дохода (только не отменённые)
        $recentIncomeOrders = MassageOrder::whereIn('employee_id', $subordinateIds)
            ->where('order_date', '>=', $monthStart)
            ->where('status', '!=', 'cancelled')
            ->with(['service', 'employee'])
            ->orderBy('order_date', 'desc')
            ->orderBy('order_time', 'desc')
            ->take(8)
            ->get()
            ->map(function ($order) {
                $duration = $order->duration ?? 60;
                $share = 0;
                if ($order->service) {
                    $share = match ($duration) {
                        60 => $order->service->operator_share_60,
                        90 => $order->service->operator_share_90,
                        120 => $order->service->operator_share_120,
                        default => $order->service->operator_share_60,
                    };
                }
                $order->operator_share = $share ?? 0;
                return $order;
            });

        // Комиссия оператора (старый расчёт - оставляем для совместимости)
        $commissionRate = 0.10;
        $operatorCommission = $totalEarnings * $commissionRate;

        // Дежурства подопечных на текущую неделю
        $weekStart = now()->startOfWeek();
        $weekEnd = now()->endOfWeek();

        $duties = CleaningDuty::whereIn('user_id', $subordinateIds)
            ->whereBetween('duty_date', [$weekStart, $weekEnd])
            ->with(['user', 'branch'])
            ->orderBy('duty_date')
            ->get();

        // Группируем дежурства по сотрудникам для отображения недели
        $dutyEmployees = $this->buildDutyWeekData($duties, $weekStart, $weekEnd);
        $dutyCount = $dutyEmployees->count();

        // TOP 10 подопечных по эффективности
        $performancePeriod = $request->get('performance_period', 'month');
        $performancePeriodStart = match ($performancePeriod) {
            'day' => now()->startOfDay(),
            'week' => now()->startOfWeek(),
            'month' => now()->startOfMonth(),
            default => now()->startOfMonth(),
        };

        $topEmployees = User::whereIn('id', $subordinateIds)
            ->withCount(['massageOrdersAsEmployee as orders_count' => function ($q) use ($performancePeriodStart) {
                $q->where('order_date', '>=', $performancePeriodStart);
            }])
            ->get()
            ->map(function ($employee) {
                $avatarPath = $employee->getAttributes()['avatar'] ?? null;
                $hasRealAvatar = !empty($avatarPath) && \Storage::disk('public')->exists($avatarPath);
                $employee->has_avatar = $hasRealAvatar;
                $employee->avatar_url = $hasRealAvatar ? asset('storage/' . $avatarPath) : null;
                $employee->initials = $this->getInitials($employee->name);

                return $employee;
            })
            ->sortByDesc('orders_count')
            ->take(10)
            ->values();

        // Подопечные сотрудники
        $subordinates = User::whereIn('id', $subordinateIds)
            ->with('branch')
            ->orderBy('name')
            ->get();

        return view('operator.dashboard', compact(
            'orders',
            'ordersCount',
            'totalEarnings',
            'operatorCommission',
            'monthlyOperatorIncome',
            'recentIncomeOrders',
            'dutyEmployees',
            'dutyCount',
            'topEmployees',
            'subordinates',
            'period',
            'performancePeriod'
        ));
    }

    /**
     * Build duty week data for display (like admin dashboard).
     */
    protected function buildDutyWeekData($duties, $weekStart, $weekEnd): \Illuminate\Support\Collection
    {
        // Группируем дежурства по сотрудникам
        $dutiesByUser = $duties->groupBy('user_id');

        return $dutiesByUser->map(function ($userDuties) use ($weekStart, $weekEnd) {
            $user = $userDuties->first()->user;
            $branch = $userDuties->first()->branch;

            // Создаём массив дней недели
            $weekDays = [];
            $currentDate = $weekStart->copy();

            while ($currentDate->lte($weekEnd)) {
                $dayDuty = $userDuties->first(function ($duty) use ($currentDate) {
                    return $duty->duty_date->isSameDay($currentDate);
                });

                $weekDays[] = (object) [
                    'date' => $currentDate->copy(),
                    'has_duty' => $dayDuty !== null,
                    'is_completed' => $dayDuty?->status === 'completed',
                ];

                $currentDate->addDay();
            }

            $avatarPath = $user?->getAttributes()['avatar'] ?? null;
            $hasRealAvatar = !empty($avatarPath) && \Storage::disk('public')->exists($avatarPath);

            return (object) [
                'user_id' => $user?->id,
                'name' => $user?->name ?? 'N/A',
                'branch' => $branch?->name ?? 'N/A',
                'avatar' => $hasRealAvatar ? asset('storage/' . $avatarPath) : null,
                'has_avatar' => $hasRealAvatar,
                'initials' => $this->getInitials($user?->name),
                'week_days' => $weekDays,
            ];
        })->values();
    }

    /**
     * Get initials from name.
     */
    protected function getInitials(?string $name): string
    {
        if (!$name) {
            return '?';
        }

        $words = explode(' ', trim($name));
        $initials = '';

        foreach ($words as $word) {
            if (!empty($word)) {
                $initials .= mb_strtoupper(mb_substr($word, 0, 1));
            }
            if (strlen($initials) >= 2) {
                break;
            }
        }

        return $initials ?: '?';
    }
}
