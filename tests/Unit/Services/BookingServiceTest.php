<?php

namespace Tests\Unit\Services;

use App\Models\Branch;
use App\Models\Room;
use App\Models\RoomBooking;
use App\Models\User;
use App\Services\Infinity\BookingService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookingServiceTest extends TestCase
{
    use RefreshDatabase;

    protected BookingService $bookingService;
    protected Branch $branch;
    protected Room $room;
    protected User $user;
    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->bookingService = new BookingService();

        // Создаём админа
        $this->admin = User::factory()->create([
            'type' => 'company',
            'is_active' => 1,
        ]);

        // Создаём филиал
        $this->branch = Branch::factory()->create([
            'created_by' => $this->admin->id,
        ]);

        // Создаём комнату
        $this->room = Room::factory()->create([
            'branch_id' => $this->branch->id,
            'created_by' => $this->admin->id,
        ]);

        // Создаём пользователя (массажистку)
        $this->user = User::factory()->create([
            'type' => 'masseuse',
            'is_active' => 1,
            'branch_id' => $this->branch->id,
        ]);
    }

    /** @test */
    public function it_checks_user_booking_for_date_returns_true_when_exists()
    {
        $date = Carbon::today();

        RoomBooking::create([
            'room_id' => $this->room->id,
            'branch_id' => $this->branch->id,
            'user_id' => $this->user->id,
            'booking_date' => $date->toDateString(),
            'start_time' => '10:00',
            'end_time' => '12:00',
            'status' => 'confirmed',
            'created_by' => $this->user->id,
        ]);

        $result = $this->bookingService->hasUserBookingForDate(
            $this->room->id,
            $this->user->id,
            $date
        );

        $this->assertTrue($result);
    }

    /** @test */
    public function it_checks_user_booking_for_date_returns_false_when_not_exists()
    {
        $date = Carbon::today();

        $result = $this->bookingService->hasUserBookingForDate(
            $this->room->id,
            $this->user->id,
            $date
        );

        $this->assertFalse($result);
    }

    /** @test */
    public function it_ignores_cancelled_bookings_when_checking_user_booking()
    {
        $date = Carbon::today();

        RoomBooking::create([
            'room_id' => $this->room->id,
            'branch_id' => $this->branch->id,
            'user_id' => $this->user->id,
            'booking_date' => $date->toDateString(),
            'start_time' => '10:00',
            'end_time' => '12:00',
            'status' => 'cancelled',
            'created_by' => $this->user->id,
        ]);

        $result = $this->bookingService->hasUserBookingForDate(
            $this->room->id,
            $this->user->id,
            $date
        );

        $this->assertFalse($result);
    }

    /** @test */
    public function it_counts_consecutive_booking_days_before_range()
    {
        $startDate = Carbon::today();

        // Создаём 3 бронирования до начальной даты
        for ($i = 1; $i <= 3; $i++) {
            RoomBooking::create([
                'room_id' => $this->room->id,
                'branch_id' => $this->branch->id,
                'user_id' => $this->user->id,
                'booking_date' => $startDate->copy()->subDays($i)->toDateString(),
                'start_time' => '10:00',
                'end_time' => '12:00',
                'status' => 'confirmed',
                'created_by' => $this->user->id,
            ]);
        }

        $consecutiveDays = $this->bookingService->getConsecutiveBookingDays(
            $this->room->id,
            $this->user->id,
            $startDate->toDateString(),
            $startDate->toDateString()
        );

        $this->assertEquals(3, $consecutiveDays);
    }

    /** @test */
    public function it_counts_consecutive_booking_days_after_range()
    {
        $endDate = Carbon::today();

        // Создаём 2 бронирования после конечной даты
        for ($i = 1; $i <= 2; $i++) {
            RoomBooking::create([
                'room_id' => $this->room->id,
                'branch_id' => $this->branch->id,
                'user_id' => $this->user->id,
                'booking_date' => $endDate->copy()->addDays($i)->toDateString(),
                'start_time' => '10:00',
                'end_time' => '12:00',
                'status' => 'confirmed',
                'created_by' => $this->user->id,
            ]);
        }

        $consecutiveDays = $this->bookingService->getConsecutiveBookingDays(
            $this->room->id,
            $this->user->id,
            $endDate->toDateString(),
            $endDate->toDateString()
        );

        $this->assertEquals(2, $consecutiveDays);
    }

    /** @test */
    public function it_counts_consecutive_days_both_before_and_after()
    {
        $startDate = Carbon::today();
        $endDate = Carbon::today()->addDays(2);

        // 2 дня до
        for ($i = 1; $i <= 2; $i++) {
            RoomBooking::create([
                'room_id' => $this->room->id,
                'branch_id' => $this->branch->id,
                'user_id' => $this->user->id,
                'booking_date' => $startDate->copy()->subDays($i)->toDateString(),
                'start_time' => '10:00',
                'end_time' => '12:00',
                'status' => 'confirmed',
                'created_by' => $this->user->id,
            ]);
        }

        // 1 день после
        RoomBooking::create([
            'room_id' => $this->room->id,
            'branch_id' => $this->branch->id,
            'user_id' => $this->user->id,
            'booking_date' => $endDate->copy()->addDay()->toDateString(),
            'start_time' => '10:00',
            'end_time' => '12:00',
            'status' => 'confirmed',
            'created_by' => $this->user->id,
        ]);

        $consecutiveDays = $this->bookingService->getConsecutiveBookingDays(
            $this->room->id,
            $this->user->id,
            $startDate->toDateString(),
            $endDate->toDateString()
        );

        $this->assertEquals(3, $consecutiveDays); // 2 до + 1 после
    }


    /** @test */
    public function it_checks_range_availability_all_days_available()
    {
        $startDate = Carbon::today()->addDay();
        $endDate = Carbon::today()->addDays(3);

        $result = $this->bookingService->checkRangeAvailability(
            $this->room->id,
            $startDate->toDateString(),
            $endDate->toDateString(),
            '10:00',
            '12:00',
            $this->user->id
        );

        $this->assertTrue($result['available']);
        $this->assertEmpty($result['unavailable_dates']);
        $this->assertEquals(3, $result['total_days']);
        $this->assertFalse($result['would_exceed_limit']);
    }

    /** @test */
    public function it_checks_range_availability_some_days_unavailable()
    {
        $startDate = Carbon::today()->addDay();
        $endDate = Carbon::today()->addDays(3);

        // Создаём бронирование на второй день
        RoomBooking::create([
            'room_id' => $this->room->id,
            'branch_id' => $this->branch->id,
            'user_id' => $this->admin->id, // Другой пользователь
            'booking_date' => $startDate->copy()->addDay()->toDateString(),
            'start_time' => '10:00',
            'end_time' => '12:00',
            'status' => 'confirmed',
            'created_by' => $this->admin->id,
        ]);

        $result = $this->bookingService->checkRangeAvailability(
            $this->room->id,
            $startDate->toDateString(),
            $endDate->toDateString(),
            '10:00',
            '12:00',
            $this->user->id
        );

        $this->assertFalse($result['available']);
        $this->assertCount(1, $result['unavailable_dates']);
    }

    /** @test */
    public function it_detects_weekly_limit_exceeded()
    {
        $startDate = Carbon::today();

        // Создаём 5 бронирований до начальной даты
        for ($i = 1; $i <= 5; $i++) {
            RoomBooking::create([
                'room_id' => $this->room->id,
                'branch_id' => $this->branch->id,
                'user_id' => $this->user->id,
                'booking_date' => $startDate->copy()->subDays($i)->toDateString(),
                'start_time' => '10:00',
                'end_time' => '12:00',
                'status' => 'confirmed',
                'created_by' => $this->user->id,
            ]);
        }

        // Пытаемся забронировать ещё 3 дня (5 + 3 = 8 > 7)
        $result = $this->bookingService->checkRangeAvailability(
            $this->room->id,
            $startDate->toDateString(),
            $startDate->copy()->addDays(2)->toDateString(),
            '10:00',
            '12:00',
            $this->user->id
        );

        $this->assertFalse($result['available']);
        $this->assertTrue($result['would_exceed_limit']);
        $this->assertEquals(5, $result['consecutive_days']);
    }

    /** @test */
    public function it_allows_booking_within_weekly_limit()
    {
        $startDate = Carbon::today();

        // Создаём 3 бронирования до начальной даты
        for ($i = 1; $i <= 3; $i++) {
            RoomBooking::create([
                'room_id' => $this->room->id,
                'branch_id' => $this->branch->id,
                'user_id' => $this->user->id,
                'booking_date' => $startDate->copy()->subDays($i)->toDateString(),
                'start_time' => '10:00',
                'end_time' => '12:00',
                'status' => 'confirmed',
                'created_by' => $this->user->id,
            ]);
        }

        // Пытаемся забронировать ещё 4 дня (3 + 4 = 7 <= 7)
        $result = $this->bookingService->checkRangeAvailability(
            $this->room->id,
            $startDate->toDateString(),
            $startDate->copy()->addDays(3)->toDateString(),
            '10:00',
            '12:00',
            $this->user->id
        );

        $this->assertTrue($result['available']);
        $this->assertFalse($result['would_exceed_limit']);
    }

    /** @test */
    public function it_creates_multi_day_booking_successfully()
    {
        $startDate = Carbon::today()->addDay();
        $endDate = Carbon::today()->addDays(3);

        $bookings = $this->bookingService->createMultiDayBooking([
            'room_id' => $this->room->id,
            'branch_id' => $this->branch->id,
            'booking_date' => $startDate->toDateString(),
            'end_date' => $endDate->toDateString(),
            'start_time' => '10:00',
            'end_time' => '12:00',
            'notes' => 'Test booking',
        ], $this->user->id);

        $this->assertCount(3, $bookings);

        // Проверяем что все бронирования созданы
        $this->assertDatabaseCount('room_bookings', 3);

        // Проверяем даты
        $dates = collect($bookings)->pluck('booking_date')->map(fn($d) => $d->toDateString())->toArray();
        $this->assertContains($startDate->toDateString(), $dates);
        $this->assertContains($startDate->copy()->addDay()->toDateString(), $dates);
        $this->assertContains($endDate->toDateString(), $dates);
    }

    /** @test */
    public function it_creates_single_day_booking_when_dates_equal()
    {
        $date = Carbon::today()->addDay();

        $bookings = $this->bookingService->createMultiDayBooking([
            'room_id' => $this->room->id,
            'branch_id' => $this->branch->id,
            'booking_date' => $date->toDateString(),
            'end_date' => $date->toDateString(),
            'start_time' => '10:00',
            'end_time' => '12:00',
        ], $this->user->id);

        $this->assertCount(1, $bookings);
        $this->assertDatabaseCount('room_bookings', 1);
    }

    /** @test */
    public function it_throws_exception_when_slot_unavailable_during_multi_day_booking()
    {
        $startDate = Carbon::today()->addDay();
        $endDate = Carbon::today()->addDays(3);

        // Создаём бронирование на второй день
        RoomBooking::create([
            'room_id' => $this->room->id,
            'branch_id' => $this->branch->id,
            'user_id' => $this->admin->id,
            'booking_date' => $startDate->copy()->addDay()->toDateString(),
            'start_time' => '10:00',
            'end_time' => '12:00',
            'status' => 'confirmed',
            'created_by' => $this->admin->id,
        ]);

        $this->expectException(\Exception::class);

        $this->bookingService->createMultiDayBooking([
            'room_id' => $this->room->id,
            'branch_id' => $this->branch->id,
            'booking_date' => $startDate->toDateString(),
            'end_date' => $endDate->toDateString(),
            'start_time' => '10:00',
            'end_time' => '12:00',
        ], $this->user->id);
    }
}
