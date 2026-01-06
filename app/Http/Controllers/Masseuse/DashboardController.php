<?php

namespace App\Http\Controllers\Masseuse;

use App\Http\Controllers\Controller;
use App\Models\CleaningDuty;
use App\Models\MassageClient;
use App\Models\MassageOrder;
use App\Models\RoomBooking;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Display the masseuse dashboard.
     */
    public function index()
    {
        $user = auth()->user();

        // Все активные бронирования на месяц (не просроченные)
        $monthEnd = now()->endOfMonth();
        
        $activeBookings = RoomBooking::where('user_id', $user->id)
            ->whereDate('booking_date', '>=', today())
            ->whereDate('booking_date', '<=', $monthEnd)
            ->where('status', '!=', 'cancelled')
            ->orderBy('booking_date')
            ->orderBy('start_time')
            ->with(['room', 'client'])
            ->get();

        // Сегодняшние бронирования для таблицы
        $todayBookings = $activeBookings->filter(fn($b) => $b->booking_date->isToday());

        // Заказы на эту неделю (сегодня и до конца недели)
        $weekEnd = now()->endOfWeek();
        
        $weeklyOrders = MassageOrder::where('employee_id', $user->id)
            ->whereDate('order_date', '>=', today())
            ->whereDate('order_date', '<=', $weekEnd)
            ->where('status', '!=', 'cancelled')
            ->orderBy('order_date')
            ->orderBy('order_time')
            ->with(['client', 'service'])
            ->get();

        // Заказы на сегодня со статусом "в ожидании" (для модалки "Клиент пришёл")
        $pendingTodayOrders = MassageOrder::where('employee_id', $user->id)
            ->whereDate('order_date', today())
            ->where('status', 'pending')
            ->orderBy('order_time')
            ->with(['client', 'service'])
            ->get();

        // Заказы на сегодня со статусом "подтверждён" (для модалки "Заказ завершён")
        $confirmedTodayOrders = MassageOrder::where('employee_id', $user->id)
            ->whereDate('order_date', today())
            ->where('status', 'confirmed')
            ->orderBy('order_time')
            ->with(['client', 'service'])
            ->get();

        // Количество клиентов массажиста (по responsible_id или created_by для обратной совместимости)
        $clientsCount = MassageClient::where(function ($q) use ($user) {
            $q->where('responsible_id', $user->id)
              ->orWhere('created_by', $user->id);
        })->count();

        // Последние 5 клиентов по заказам с датой последнего визита
        $recentClientsData = MassageOrder::where('employee_id', $user->id)
            ->whereNotNull('client_id')
            ->where('status', '!=', 'cancelled')
            ->orderBy('order_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->with('client')
            ->get()
            ->groupBy('client_id')
            ->map(function ($orders) {
                $lastOrder = $orders->first();
                return (object) [
                    'client' => $lastOrder->client,
                    'last_visit' => $lastOrder->order_date,
                ];
            })
            ->filter(fn($item) => $item->client !== null)
            ->take(5)
            ->values();

        // Ближайшее дежурство
        $upcomingDuty = CleaningDuty::where('user_id', $user->id)
            ->where('duty_date', '>=', today())
            ->where('status', 'pending')
            ->orderBy('duty_date')
            ->with('branch')
            ->first();

        // Количество непрочитанных уведомлений (заглушка)
        $notificationsCount = 0;

        return view('masseuse.dashboard', compact(
            'todayBookings',
            'activeBookings',
            'weeklyOrders',
            'pendingTodayOrders',
            'confirmedTodayOrders',
            'clientsCount',
            'recentClientsData',
            'upcomingDuty',
            'notificationsCount'
        ));
    }

    /**
     * Update order status (for quick actions).
     */
    public function updateOrderStatus(Request $request, MassageOrder $order)
    {
        $user = auth()->user();
        
        // Проверяем что это заказ этой массажистки
        if ($order->employee_id !== $user->id) {
            return response()->json(['success' => false, 'message' => __('Доступ запрещён')], 403);
        }
        
        $validated = $request->validate([
            'status' => 'required|in:confirmed,cancelled,completed',
        ]);
        
        $order->update(['status' => $validated['status']]);
        
        return response()->json([
            'success' => true,
            'message' => __('Статус заказа обновлён'),
        ]);
    }

    /**
     * Display the weekly schedule.
     */
    public function schedule(Request $request)
    {
        $user = auth()->user();
        $weekStart = Carbon::parse($request->get('week', now()))->startOfWeek();
        $weekEnd = $weekStart->copy()->endOfWeek();

        $bookings = RoomBooking::where('user_id', $user->id)
            ->whereBetween('booking_date', [$weekStart, $weekEnd])
            ->where('status', '!=', 'cancelled')
            ->orderBy('booking_date')
            ->orderBy('start_time')
            ->with(['room', 'client'])
            ->get();

        // Группируем бронирования по дням
        $bookingsByDay = $bookings->groupBy(fn($b) => $b->booking_date->format('Y-m-d'));
        
        // Получаем дежурства на эту неделю
        $duties = CleaningDuty::where('user_id', $user->id)
            ->whereBetween('duty_date', [$weekStart, $weekEnd])
            ->with(['cleaningStatuses.room'])
            ->get()
            ->keyBy(fn($d) => $d->duty_date->format('Y-m-d'));

        // Создаём массив дней недели
        $weekDays = [];
        $currentDay = $weekStart->copy();
        while ($currentDay->lte($weekEnd)) {
            $dateKey = $currentDay->format('Y-m-d');
            $weekDays[$dateKey] = [
                'date' => $currentDay->copy(),
                'dayName' => $currentDay->translatedFormat('l'),
                'dayNumber' => $currentDay->day,
                'isToday' => $currentDay->isToday(),
                'bookings' => $bookingsByDay->get($dateKey, collect()),
                'duty' => $duties->get($dateKey),
            ];
            $currentDay->addDay();
        }

        return view('masseuse.schedule', compact('weekDays', 'weekStart', 'weekEnd'));
    }
}
