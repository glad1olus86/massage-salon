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
                $operatorIncome += $this->getOperatorShare($order);
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
            $monthlyOperatorIncome += $this->getOperatorShare($order);
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
                $order->operator_share = $this->getOperatorShare($order);
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
                'branch_id' => $branch?->id,
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

    /**
     * Get operator share for an order based on duration.
     */
    protected function getOperatorShare($order): float
    {
        if (!$order->service) {
            return 0;
        }
        
        $duration = $order->duration ?? 60;
        
        $share = match ($duration) {
            15 => $order->service->operator_share_15,
            30 => $order->service->operator_share_30,
            45 => $order->service->operator_share_45,
            60 => $order->service->operator_share_60,
            90 => $order->service->operator_share_90,
            120 => $order->service->operator_share_120,
            180 => $order->service->operator_share_180,
            default => $order->service->operator_share_60,
        };
        
        return (float) ($share ?? 0);
    }

    /**
     * Get day details for modal (AJAX).
     */
    public function getDayDetails(Request $request, string $date)
    {
        $operator = Auth::user();
        $subordinateIds = $operator->getSubordinateIds();
        $branchId = $request->get('branch_id');
        
        $dateObj = Carbon::parse($date);
        
        // Получаем бронирования на этот день для подопечных
        $bookings = \App\Models\RoomBooking::whereDate('booking_date', $dateObj)
            ->whereIn('user_id', $subordinateIds)
            ->with(['room', 'user'])
            ->orderBy('start_time')
            ->get();
        
        // Получаем дежурство на этот день
        $duty = CleaningDuty::whereDate('duty_date', $dateObj)
            ->whereIn('user_id', $subordinateIds)
            ->with(['user', 'cleaningStatuses.room'])
            ->first();
        
        // Получаем список подопечных для смены дежурного
        $employees = \App\Models\DutyPoint::where('created_by', $operator->creatorId())
            ->with('user')
            ->orderBy('points', 'desc')
            ->get()
            ->filter(function ($point) use ($subordinateIds) {
                return in_array($point->user_id, $subordinateIds);
            })
            ->values();
        
        // Получаем комнаты для кнопки "Добавить"
        $rooms = \App\Models\Room::when($branchId, function ($q) use ($branchId) {
            $q->where('branch_id', $branchId);
        })->orderBy('room_number')->get();
        
        // Получаем подопечных сотрудников для бронирования
        $branchEmployees = User::whereIn('id', $subordinateIds)
            ->orderBy('name')
            ->get(['id', 'name']);
        
        // Получаем статусы уборки комнат на этот день (даже если нет дежурного)
        $cleaningStatuses = [];
        if (!$duty && $branchId) {
            // Если нет дежурного, показываем комнаты филиала
            $cleaningStatuses = $rooms->map(function ($room) {
                return (object) [
                    'id' => null,
                    'room_id' => $room->id,
                    'room' => $room,
                    'area_type' => 'room',
                    'status' => 'unknown',
                ];
            })->values();
        }
        
        return response()->json([
            'date' => $date,
            'formatted_date' => $dateObj->translatedFormat('j F Y (l)'),
            'bookings' => $bookings,
            'duty' => $duty,
            'employees' => $employees,
            'rooms' => $rooms,
            'branchEmployees' => $branchEmployees,
            'cleaningStatuses' => $cleaningStatuses,
        ]);
    }

    /**
     * Complete duty (AJAX).
     */
    public function completeDuty(Request $request, int $dutyId)
    {
        $operator = Auth::user();
        $subordinateIds = $operator->getSubordinateIds();
        
        $duty = CleaningDuty::whereIn('user_id', $subordinateIds)
            ->findOrFail($dutyId);
        
        $duty->status = 'completed';
        $duty->save();
        
        return response()->json(['success' => true]);
    }

    /**
     * Change duty person (AJAX).
     */
    public function changeDuty(Request $request, int $dutyId)
    {
        $operator = Auth::user();
        $subordinateIds = $operator->getSubordinateIds();
        
        $duty = CleaningDuty::whereIn('user_id', $subordinateIds)
            ->findOrFail($dutyId);
        
        $newUserId = $request->input('user_id');
        
        // Проверяем что новый пользователь - подопечный
        if (!in_array($newUserId, $subordinateIds)) {
            return response()->json(['success' => false, 'message' => __('Доступ запрещён')]);
        }
        
        $duty->user_id = $newUserId;
        $duty->save();
        
        return response()->json(['success' => true]);
    }

    /**
     * Toggle cleaning status (AJAX).
     */
    public function toggleCleaningStatus(Request $request, int $statusId)
    {
        $operator = Auth::user();
        $subordinateIds = $operator->getSubordinateIds();
        
        $status = \App\Models\CleaningStatus::whereHas('duty', function ($q) use ($subordinateIds) {
            $q->whereIn('user_id', $subordinateIds);
        })->findOrFail($statusId);
        
        $status->status = $request->input('status', 'clean');
        $status->save();
        
        return response()->json(['success' => true]);
    }

    /**
     * Store a new booking (AJAX).
     */
    public function storeBooking(Request $request)
    {
        $operator = Auth::user();
        $subordinateIds = $operator->getSubordinateIds();
        
        $request->validate([
            'booking_date' => 'required|date',
            'room_id' => 'required|exists:rooms,id',
            'user_id' => 'required|exists:users,id',
            'start_time' => 'required',
            'end_time' => 'required|after:start_time',
        ]);
        
        // Проверяем что пользователь - подопечный
        if (!in_array($request->user_id, $subordinateIds)) {
            return response()->json(['success' => false, 'message' => __('Доступ запрещён')]);
        }
        
        // Проверяем конфликты бронирования
        $conflict = \App\Models\RoomBooking::where('room_id', $request->room_id)
            ->whereDate('booking_date', $request->booking_date)
            ->where(function ($q) use ($request) {
                $q->whereBetween('start_time', [$request->start_time, $request->end_time])
                  ->orWhereBetween('end_time', [$request->start_time, $request->end_time])
                  ->orWhere(function ($q2) use ($request) {
                      $q2->where('start_time', '<=', $request->start_time)
                         ->where('end_time', '>=', $request->end_time);
                  });
            })
            ->exists();
        
        if ($conflict) {
            return response()->json(['success' => false, 'message' => __('Комната уже забронирована на это время')]);
        }
        
        $booking = \App\Models\RoomBooking::create([
            'booking_date' => $request->booking_date,
            'room_id' => $request->room_id,
            'user_id' => $request->user_id,
            'start_time' => $request->start_time,
            'end_time' => $request->end_time,
            'created_by' => $operator->id,
        ]);
        
        return response()->json(['success' => true, 'booking' => $booking]);
    }
}
