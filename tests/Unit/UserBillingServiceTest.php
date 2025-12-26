<?php

namespace Tests\Unit;

use App\Models\Plan;
use App\Models\User;
use App\Models\UserBillingLog;
use App\Models\UserBillingPeriod;
use App\Services\UserBillingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UserBillingServiceTest extends TestCase
{
    use RefreshDatabase;

    protected UserBillingService $service;
    protected User $company;
    protected Plan $plan;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new UserBillingService();

        // Create a plan with pricing
        $this->plan = Plan::create([
            'name' => 'Test Plan',
            'price' => 200.00,
            'duration' => 'month',
            'max_users' => 10,
            'base_users_limit' => 3,
            'manager_price' => 50.00,
            'curator_price' => 30.00,
        ]);

        // Create company user
        $this->company = User::create([
            'name' => 'Test Company',
            'email' => 'company@test.com',
            'password' => bcrypt('password'),
            'type' => 'company',
            'plan' => $this->plan->id,
        ]);

        // Create roles
        Role::create(['name' => 'manager', 'guard_name' => 'web', 'created_by' => $this->company->id]);
        Role::create(['name' => 'curator', 'guard_name' => 'web', 'created_by' => $this->company->id]);
    }

    /** @test */
    public function it_creates_billing_period_if_not_exists()
    {
        $period = $this->service->getCurrentPeriod($this->company->id);

        $this->assertInstanceOf(UserBillingPeriod::class, $period);
        $this->assertEquals($this->company->id, $period->company_id);
        $this->assertEquals('active', $period->status);
    }

    /** @test */
    public function it_returns_existing_active_period()
    {
        $period1 = $this->service->getCurrentPeriod($this->company->id);
        $period2 = $this->service->getCurrentPeriod($this->company->id);

        $this->assertEquals($period1->id, $period2->id);
    }

    /** @test */
    public function it_checks_role_limit_correctly()
    {
        // Create period with 3 managers (at limit)
        $period = $this->service->getCurrentPeriod($this->company->id);
        $period->current_managers = 3;
        $period->max_managers_used = 3;
        $period->save();

        $result = $this->service->checkRoleLimit($this->company->id, 'manager');

        $this->assertTrue($result['would_exceed_limit']);
        $this->assertTrue($result['already_over_limit']);
        $this->assertEquals(3, $result['current_count']);
        $this->assertEquals(3, $result['base_limit']);
        $this->assertEquals(50.00, $result['role_price']);
        $this->assertNotNull($result['message']);
    }

    /** @test */
    public function it_returns_no_limit_exceeded_when_under_limit()
    {
        $period = $this->service->getCurrentPeriod($this->company->id);
        $period->current_managers = 1;
        $period->max_managers_used = 1;
        $period->save();

        $result = $this->service->checkRoleLimit($this->company->id, 'manager');

        $this->assertFalse($result['would_exceed_limit']);
        $this->assertFalse($result['already_over_limit']);
        $this->assertNull($result['message']);
    }

    /** @test */
    public function it_increments_current_and_max_used_when_user_added()
    {
        $period = $this->service->getCurrentPeriod($this->company->id);
        $initialManagers = $period->current_managers;
        $initialMaxUsed = $period->max_managers_used;

        // Create a test user
        $user = User::create([
            'name' => 'Test Manager',
            'email' => 'manager@test.com',
            'password' => bcrypt('password'),
            'type' => 'manager',
            'created_by' => $this->company->id,
        ]);

        $this->service->recordUserAdded($this->company->id, $user->id, 'manager');

        $period->refresh();

        $this->assertEquals($initialManagers + 1, $period->current_managers);
        $this->assertEquals($initialMaxUsed + 1, $period->max_managers_used);

        // Check log was created
        $log = UserBillingLog::where('user_id', $user->id)->first();
        $this->assertNotNull($log);
        $this->assertEquals('user_added', $log->action);
        $this->assertEquals('manager', $log->role);
    }

    /** @test */
    public function it_decrements_current_but_not_max_used_when_user_removed()
    {
        $period = $this->service->getCurrentPeriod($this->company->id);
        $period->current_managers = 5;
        $period->max_managers_used = 5;
        $period->save();

        $user = User::create([
            'name' => 'Test Manager',
            'email' => 'manager@test.com',
            'password' => bcrypt('password'),
            'type' => 'manager',
            'created_by' => $this->company->id,
        ]);

        $this->service->recordUserRemoved($this->company->id, $user->id, 'manager');

        $period->refresh();

        // Current should decrease
        $this->assertEquals(4, $period->current_managers);
        // Max used should stay the same (anti-abuse protection!)
        $this->assertEquals(5, $period->max_managers_used);
    }

    /** @test */
    public function it_calculates_billing_breakdown_correctly()
    {
        $period = $this->service->getCurrentPeriod($this->company->id);
        $period->current_managers = 5;
        $period->current_curators = 4;
        $period->max_managers_used = 5;
        $period->max_curators_used = 4;
        $period->save();
        $period->calculateTotal($this->plan);

        $breakdown = $this->service->getBillingBreakdown($this->company->id);

        // Base limit is 3, so:
        // Managers over: 5 - 3 = 2 × $50 = $100
        // Curators over: 4 - 3 = 1 × $30 = $30
        // Total additional: $130
        // Total: $200 + $130 = $330

        $this->assertEquals('Test Plan', $breakdown['plan_name']);
        $this->assertEquals(200.00, $breakdown['base_price']);
        $this->assertEquals(3, $breakdown['base_limit']);

        $this->assertEquals(5, $breakdown['managers']['current']);
        $this->assertEquals(5, $breakdown['managers']['max_used']);
        $this->assertEquals(2, $breakdown['managers']['over_limit']);
        $this->assertEquals(100.00, $breakdown['managers']['additional_cost']);

        $this->assertEquals(4, $breakdown['curators']['current']);
        $this->assertEquals(4, $breakdown['curators']['max_used']);
        $this->assertEquals(1, $breakdown['curators']['over_limit']);
        $this->assertEquals(30.00, $breakdown['curators']['additional_cost']);

        $this->assertEquals(130.00, $breakdown['total_additional']);
        $this->assertEquals(330.00, $breakdown['total_charge']);
    }

    /** @test */
    public function it_handles_role_change_correctly()
    {
        $period = $this->service->getCurrentPeriod($this->company->id);
        $period->current_managers = 2;
        $period->current_curators = 1;
        $period->max_managers_used = 2;
        $period->max_curators_used = 1;
        $period->save();

        $user = User::create([
            'name' => 'Test User',
            'email' => 'user@test.com',
            'password' => bcrypt('password'),
            'type' => 'curator',
            'created_by' => $this->company->id,
        ]);

        // Change from curator to manager
        $this->service->recordRoleChanged($this->company->id, $user->id, 'curator', 'manager');

        $period->refresh();

        // Curator should decrease, manager should increase
        $this->assertEquals(0, $period->current_curators);
        $this->assertEquals(3, $period->current_managers);
        // Max used for manager should increase
        $this->assertEquals(3, $period->max_managers_used);
        // Max used for curator stays (was 1, now current is 0, but max stays 1)
        $this->assertEquals(1, $period->max_curators_used);

        // Check log
        $log = UserBillingLog::where('user_id', $user->id)->first();
        $this->assertEquals('role_changed', $log->action);
        $this->assertEquals('manager', $log->role);
        $this->assertEquals('curator', $log->previous_role);
    }

    /** @test */
    public function it_ignores_non_billable_roles()
    {
        $result = $this->service->checkRoleLimit($this->company->id, 'employee');

        $this->assertFalse($result['would_exceed_limit']);
        $this->assertFalse($result['is_billable_role']);
    }

    /** @test */
    public function it_returns_delete_warning_when_over_limit()
    {
        $period = $this->service->getCurrentPeriod($this->company->id);
        $period->max_managers_used = 5; // Over limit of 3
        $period->save();

        $warning = $this->service->getDeleteWarning($this->company->id, 'manager');

        $this->assertNotNull($warning);
        $this->assertEquals('manager', $warning['role']);
        $this->assertEquals(50.00, $warning['role_price']);
        $this->assertNotNull($warning['message']);
    }

    /** @test */
    public function it_returns_no_delete_warning_when_under_limit()
    {
        $period = $this->service->getCurrentPeriod($this->company->id);
        $period->max_managers_used = 2; // Under limit of 3
        $period->save();

        $warning = $this->service->getDeleteWarning($this->company->id, 'manager');

        $this->assertNull($warning);
    }
}
