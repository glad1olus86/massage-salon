<?php

namespace App\Http\Controllers\Masseuse;

use App\Http\Controllers\Controller;
use App\Models\MassageClient;
use App\Models\Room;
use App\Models\RoomBooking;
use App\Services\Infinity\BookingService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class BookingController extends Controller
{
    protected BookingService $bookingService;

    public function __construct(BookingService $bookingService)
    {
        $this->bookingService = $bookingService;
    }

    /**
     * Show the form for creating a new booking.
     */
    public function create()
    {
        $user = auth()->user();

        // Проверяем, что пользователь привязан к филиалу
        if (!$user->branch_id) {
            return redirect()->route('masseuse.dashboard')
                ->withErrors(['error' => __('Вы не привязаны к филиалу. Обратитесь к администратору.')]);
        }

        // Комнаты только из филиала пользователя
        $rooms = Room::where('branch_id', $user->branch_id)->get();

        // Клиенты массажиста (по responsible_id или created_by для обратной совместимости)
        $clients = MassageClient::where(function ($q) use ($user) {
            $q->where('responsible_id', $user->id)
              ->orWhere('created_by', $user->id);
        })
            ->orderBy('first_name')
            ->get();

        return view('masseuse.bookings.create', compact('rooms', 'clients'));
    }

    /**
     * Store a newly created booking.
     */
    public function store(Request $request)
    {
        $user = auth()->user();

        $validated = $request->validate([
            'room_id' => 'required|exists:rooms,id',
            'booking_date' => 'required|date|after_or_equal:today',
            'end_date' => 'required|date|after_or_equal:booking_date',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'client_id' => 'nullable|exists:massage_clients,id',
            'notes' => 'nullable|string|max:500',
        ]);

        // Проверка что комната в филиале пользователя
        $room = Room::findOrFail($validated['room_id']);
        if ($room->branch_id !== $user->branch_id) {
            abort(403, __('Доступ запрещён.'));
        }

        // Проверка лимита 7 дней
        $startDate = Carbon::parse($validated['booking_date']);
        $endDate = Carbon::parse($validated['end_date']);
        $daysDiff = $startDate->diffInDays($endDate) + 1;

        if ($daysDiff > 7) {
            return back()
                ->withInput()
                ->withErrors(['end_date' => __('Максимальный период бронирования — 7 дней.')]);
        }

        // Проверка лимита с учётом существующих последовательных бронирований
        $consecutiveDays = $this->bookingService->getConsecutiveBookingDays(
            $validated['room_id'],
            $user->id,
            $validated['booking_date'],
            $validated['end_date']
        );

        if (($consecutiveDays + $daysDiff) > 7) {
            return back()
                ->withInput()
                ->withErrors(['room_id' => __('Вы уже забронировали эту комнату на неделю. Выберите другую комнату.')]);
        }

        // Создаём многодневное бронирование
        try {
            $bookings = $this->bookingService->createMultiDayBooking([
                'room_id' => $validated['room_id'],
                'branch_id' => $user->branch_id,
                'booking_date' => $validated['booking_date'],
                'end_date' => $validated['end_date'],
                'start_time' => $validated['start_time'],
                'end_time' => $validated['end_time'],
                'client_id' => $validated['client_id'] ?? null,
                'notes' => $validated['notes'] ?? null,
            ], $user->id);

            $message = count($bookings) > 1
                ? __('Создано бронирований: :count', ['count' => count($bookings)])
                : __('Бронирование создано.');

            return redirect()->route('masseuse.dashboard')
                ->with('success', $message);
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->withErrors(['time' => $e->getMessage()]);
        }
    }

    /**
     * Remove the specified booking.
     */
    public function destroy(RoomBooking $booking)
    {
        $this->authorize('delete', $booking);

        if ($booking->booking_date < today()) {
            return back()->withErrors(['error' => __('Нельзя отменить прошедшее бронирование.')]);
        }

        $booking->update(['status' => 'cancelled']);

        return redirect()->route('masseuse.schedule')
            ->with('success', __('Бронирование отменено.'));
    }

    /**
     * Check slot availability via AJAX.
     */
    public function checkAvailability(Request $request)
    {
        $validated = $request->validate([
            'room_id' => 'required|exists:rooms,id',
            'booking_date' => 'required|date',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i',
        ]);

        $isAvailable = $this->bookingService->isSlotAvailable(
            $validated['room_id'],
            $validated['booking_date'],
            $validated['start_time'],
            $validated['end_time']
        );

        return response()->json(['available' => $isAvailable]);
    }

    /**
     * Get available slots for a room on a date via AJAX.
     */
    public function getSlots(Request $request)
    {
        $validated = $request->validate([
            'room_id' => 'required|exists:rooms,id',
            'date' => 'required|date',
        ]);

        $slots = $this->bookingService->getAvailableSlots(
            $validated['room_id'],
            $validated['date']
        );

        return response()->json(['slots' => $slots]);
    }

    /**
     * Check range availability for multi-day booking via AJAX.
     */
    public function checkRangeAvailability(Request $request)
    {
        $validated = $request->validate([
            'room_id' => 'required|exists:rooms,id',
            'booking_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:booking_date',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i',
        ]);

        $user = auth()->user();

        $result = $this->bookingService->checkRangeAvailability(
            $validated['room_id'],
            $validated['booking_date'],
            $validated['end_date'],
            $validated['start_time'],
            $validated['end_time'],
            $user->id
        );

        return response()->json($result);
    }
}
