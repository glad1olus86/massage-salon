<?php

namespace Tests\Unit\Services;

use App\Models\Branch;
use App\Models\CleaningDuty;
use App\Models\DutyPoints;
use App\Models\Room;
use App\Models\User;
use App\Services\Infinity\DutyService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DutyServiceTest extends TestCase
{
    use RefreshDatabase;

    protected DutyService $dutyService;
    protected Branch $branch;
    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->dutyService = new DutyService();

        // Создаём админа
        $this->admin = User::factory()->create([
            'type' => 'company',
            'is_active' => 1,
        ]);

        // Создаём филиал
        $this->branch = Branch::factory()->create([
            'created_by' => $this->admin->id,
        ]);
    }

    /** @test */
    public function it_initializes_points_for_new_employee_with_minimum_points()
    {
        // Создаём двух сотрудников с разными баллами
        $user1 = User::factory()->create(['is_active' => 1, 'branch_id' => $this->branch->id]);
        $user2 = User::factory()->create(['is_active' => 1, 'branch_id' => $this->branch->id]);

        DutyPoints::create([
            'branch_id' => $this->branch->id,
            'user_id' => $user1->id,
            'points' => 200,
            'last_duty_date' => now()->subWeeks(2),
            'created_by' => $this->admin->id,
        ]);

        DutyPoints::create([
            'branch_id' => $this->branch->id,
            'user_id' => $user2->id,
            'points' => 300,
            'last_duty_date' => now()->subWeek(),
            'created_by' => $this->admin->id,
        ]);

        // Добавляем нового сотрудника
        $newUser = User::factory()->create(['is_active' => 1, 'branch_id' => $this->branch->id]);
        $newPoints = $this->dutyService->initializePointsForNewEmployee(
            $this->branch->id,
            $newUser->id,
            $this->admin->id
        );

        // Новичок должен получить минимальные баллы (200)
        $this->assertEquals(200, $newPoints->points);
        $this->assertNull($newPoints->last_duty_date);
    }

    /** @test */
    public function it_initializes_points_with_zero_when_no_employees()
    {
        $newUser = User::factory()->create(['is_active' => 1, 'branch_id' => $this->branch->id]);
        $newPoints = $this->dutyService->initializePointsForNewEmployee(
            $this->branch->id,
            $newUser->id,
            $this->admin->id
        );

        $this->assertEquals(0, $newPoints->points);
    }

    /** @test */
    public function it_selects_person_with_lowest_points_as_next_duty()
    {
        $user1 = User::factory()->create(['is_active' => 1, 'branch_id' => $this->branch->id]);
        $user2 = User::factory()->create(['is_active' => 1, 'branch_id' => $this->branch->id]);
        $user3 = User::factory()->create(['is_active' => 1, 'branch_id' => $this->branch->id]);

        DutyPoints::create([
            'branch_id' => $this->branch->id,
            'user_id' => $user1->id,
            'points' => 200,
            'last_duty_date' => now()->subWeeks(2),
            'created_by' => $this->admin->id,
        ]);

        DutyPoints::create([
            'branch_id' => $this->branch->id,
            'user_id' => $user2->id,
            'points' => 100, // Минимум
            'last_duty_date' => now()->subWeek(),
            'created_by' => $this->admin->id,
        ]);

        DutyPoints::create([
            'branch_id' => $this->branch->id,
            'user_id' => $user3->id,
            'points' => 300,
            'last_duty_date' => now()->subDays(3),
            'created_by' => $this->admin->id,
        ]);

        $nextPerson = $this->dutyService->getNextDutyPerson($this->branch->id);

        $this->assertEquals($user2->id, $nextPerson->id);
    }

    /** @test */
    public function it_prioritizes_null_last_duty_date_when_points_are_equal()
    {
        $user1 = User::factory()->create(['is_active' => 1, 'branch_id' => $this->branch->id]);
        $user2 = User::factory()->create(['is_active' => 1, 'branch_id' => $this->branch->id]);

        DutyPoints::create([
            'branch_id' => $this->branch->id,
            'user_id' => $user1->id,
            'points' => 100,
            'last_duty_date' => now()->subWeek(), // Дежурил неделю назад
            'created_by' => $this->admin->id,
        ]);

        DutyPoints::create([
            'branch_id' => $this->branch->id,
            'user_id' => $user2->id,
            'points' => 100,
            'last_duty_date' => null, // Никогда не дежурил
            'created_by' => $this->admin->id,
        ]);

        $nextPerson = $this->dutyService->getNextDutyPerson($this->branch->id);

        // Должен быть выбран тот, кто никогда не дежурил
        $this->assertEquals($user2->id, $nextPerson->id);
    }

    /** @test */
    public function it_selects_oldest_duty_date_when_points_are_equal_and_no_nulls()
    {
        $user1 = User::factory()->create(['is_active' => 1, 'branch_id' => $this->branch->id]);
        $user2 = User::factory()->create(['is_active' => 1, 'branch_id' => $this->branch->id]);
        $user3 = User::factory()->create(['is_active' => 1, 'branch_id' => $this->branch->id]);

        DutyPoints::create([
            'branch_id' => $this->branch->id,
            'user_id' => $user1->id,
            'points' => 200,
            'last_duty_date' => now()->subWeeks(3), // Самый давний
            'created_by' => $this->admin->id,
        ]);

        DutyPoints::create([
            'branch_id' => $this->branch->id,
            'user_id' => $user2->id,
            'points' => 200,
            'last_duty_date' => now()->subWeeks(2),
            'created_by' => $this->admin->id,
        ]);

        DutyPoints::create([
            'branch_id' => $this->branch->id,
            'user_id' => $user3->id,
            'points' => 200,
            'last_duty_date' => now()->subWeek(),
            'created_by' => $this->admin->id,
        ]);

        $nextPerson = $this->dutyService->getNextDutyPerson($this->branch->id);

        // Должен быть выбран тот, кто дежурил давнее всех
        $this->assertEquals($user1->id, $nextPerson->id);
    }

    /** @test */
    public function it_adds_100_points_after_duty_completion()
    {
        $user = User::factory()->create(['is_active' => 1, 'branch_id' => $this->branch->id]);

        $dutyPoints = DutyPoints::create([
            'branch_id' => $this->branch->id,
            'user_id' => $user->id,
            'points' => 100,
            'last_duty_date' => null,
            'created_by' => $this->admin->id,
        ]);

        $duty = CleaningDuty::create([
            'branch_id' => $this->branch->id,
            'user_id' => $user->id,
            'duty_date' => now()->toDateString(),
            'status' => 'pending',
        ]);

        $this->dutyService->completeDuty($duty);

        $dutyPoints->refresh();
        $duty->refresh();

        $this->assertEquals(200, $dutyPoints->points);
        $this->assertEquals('completed', $duty->status);
        $this->assertNotNull($duty->completed_at);
        $this->assertEquals(now()->toDateString(), $dutyPoints->last_duty_date->toDateString());
    }

    /** @test */
    public function it_changes_duty_person_and_marks_as_manual()
    {
        $user1 = User::factory()->create(['is_active' => 1, 'branch_id' => $this->branch->id]);
        $user2 = User::factory()->create(['is_active' => 1, 'branch_id' => $this->branch->id]);
        $operator = User::factory()->create(['type' => 'company', 'is_active' => 1]);

        $duty = CleaningDuty::create([
            'branch_id' => $this->branch->id,
            'user_id' => $user1->id,
            'duty_date' => now()->toDateString(),
            'is_manual' => false,
            'status' => 'pending',
        ]);

        $updatedDuty = $this->dutyService->changeDutyPerson($duty, $user2->id, $operator->id);

        $this->assertEquals($user2->id, $updatedDuty->user_id);
        $this->assertEquals($operator->id, $updatedDuty->assigned_by);
        $this->assertTrue($updatedDuty->is_manual);
    }
}
