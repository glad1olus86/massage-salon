<?php

namespace App\Http\Controllers\Infinity;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Room;
use App\Models\RoomBooking;
use App\Services\Infinity\BookingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BookingController extends Controller
{
    protected BookingService $bookingService;

    public function __construct(BookingService $bookingService)
    {
        $this->bookingService = $bookingService;
    }

    /**
     * Создание бронирования.
     */
    public function store(Request $request)
    {
        $user = Auth::user();
        $creatorId = $user->creatorId();

        $validated = $request->validate([
            'room_id' => 'required|exists:rooms,id',
            'branch_id' => 'required|exists:branches,id',
            'user_id' => 'required|exists:users,id',
            'client_id' => 'nullable|exists:massage_clients,id',
            'booking_date' => 'required|date|after_or_equal:today',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'notes' => 'nullable|string|max:1000',
        ]);

        // Проверяем доступ к филиалу
        $branch = Branch::where('id', $validated['branch_id'])
            ->where('created_by', $creatorId)
            ->firstOrFail();

        // Проверяем что комната принадлежит филиалу
        $room = Room::where('id', $validated['room_id'])
            ->where('branch_id', $branch->id)
            ->firstOrFail();

        try {
            $booking = $this->bookingService->createBooking([
                'room_id' => $validated['room_id'],
                'branch_id' => $validated['branch_id'],
                'user_id' => $validated['user_id'],
                'client_id' => $validated['client_id'] ?? null,
                'booking_date' => $validated['booking_date'],
                'start_time' => $validated['start_time'],
                'end_time' => $validated['end_time'],
                'notes' => $validated['notes'] ?? null,
                'status' => 'confirmed',
                'created_by' => $user->id,
            ]);

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => __('Бронирование создано.'),
                    'booking' => $booking->load(['room', 'user', 'client']),
                ]);
            }

            return redirect()->back()->with('success', __('Бронирование создано.'));

        } catch (\Exception $e) {
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 409);
            }

            return redirect()->back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * Обновление бронирования.
     */
    public function update(Request $request, RoomBooking $booking)
    {
        $user = Auth::user();
        $creatorId = $user->creatorId();

        // Проверяем доступ
        $branch = Branch::where('id', $booking->branch_id)
            ->where('created_by', $creatorId)
            ->firstOrFail();

        $validated = $request->validate([
            'room_id' => 'sometimes|exists:rooms,id',
            'user_id' => 'sometimes|exists:users,id',
            'client_id' => 'nullable|exists:massage_clients,id',
            'booking_date' => 'sometimes|date',
            'start_time' => 'sometimes|date_format:H:i',
            'end_time' => 'sometimes|date_format:H:i|after:start_time',
            'status' => 'sometimes|in:pending,confirmed,completed,cancelled',
            'notes' => 'nullable|string|max:1000',
        ]);

        try {
            $booking = $this->bookingService->updateBooking($booking, $validated);

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => __('Бронирование обновлено.'),
                    'booking' => $booking->load(['room', 'user', 'client']),
                ]);
            }

            return redirect()->back()->with('success', __('Бронирование обновлено.'));

        } catch (\Exception $e) {
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 409);
            }

            return redirect()->back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * Удаление (отмена) бронирования.
     */
    public function destroy(Request $request, RoomBooking $booking)
    {
        $user = Auth::user();
        $creatorId = $user->creatorId();

        // Проверяем доступ
        $branch = Branch::where('id', $booking->branch_id)
            ->where('created_by', $creatorId)
            ->firstOrFail();

        try {
            $this->bookingService->cancelBooking($booking);

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => __('Бронирование отменено.'),
                ]);
            }

            return redirect()->back()->with('success', __('Бронирование отменено.'));

        } catch (\Exception $e) {
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 422);
            }

            return redirect()->back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * AJAX: Проверка доступности слота.
     */
    public function checkAvailability(Request $request)
    {
        $validated = $request->validate([
            'room_id' => 'required|exists:rooms,id',
            'booking_date' => 'required|date',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'exclude_booking_id' => 'nullable|exists:room_bookings,id',
        ]);

        $isAvailable = $this->bookingService->isSlotAvailable(
            $validated['room_id'],
            $validated['booking_date'],
            $validated['start_time'],
            $validated['end_time'],
            $validated['exclude_booking_id'] ?? null
        );

        return response()->json([
            'available' => $isAvailable,
            'message' => $isAvailable
                ? __('Слот свободен')
                : __('Этот временной слот уже занят'),
        ]);
    }

    /**
     * AJAX: Получить доступные слоты для комнаты.
     */
    public function getAvailableSlots(Request $request)
    {
        $validated = $request->validate([
            'room_id' => 'required|exists:rooms,id',
            'date' => 'required|date',
        ]);

        $slots = $this->bookingService->getAvailableSlots(
            $validated['room_id'],
            $validated['date']
        );

        return response()->json([
            'slots' => $slots,
        ]);
    }
}
