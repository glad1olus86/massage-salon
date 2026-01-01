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

        // Сегодняшние бронирования массажиста
        $todayBookings = RoomBooking::where('user_id', $user->id)
            ->whereDate('booking_date', today())
            ->where('status', '!=', 'cancelled')
            ->orderBy('start_time')
            ->with(['room', 'client'])
            ->get();

        // Последние заказы массажиста (за последние 7 дней)
        $recentOrders = MassageOrder::where('employee_id', $user->id)
            ->where('order_date', '>=', now()->subDays(7))
            ->orderBy('order_date', 'desc')
            ->orderBy('order_time', 'desc')
            ->with(['client', 'service'])
            ->limit(5)
            ->get();

        // Количество заказов за сегодня
        $todayOrdersCount = MassageOrder::where('employee_id', $user->id)
            ->whereDate('order_date', today())
            ->count();

        // Количество клиентов массажиста (по responsible_id или created_by для обратной совместимости)
        $clientsCount = MassageClient::where(function ($q) use ($user) {
            $q->where('responsible_id', $user->id)
              ->orWhere('created_by', $user->id);
        })->count();

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
            'recentOrders',
            'todayOrdersCount',
            'clientsCount',
            'upcomingDuty',
            'notificationsCount'
        ));
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
