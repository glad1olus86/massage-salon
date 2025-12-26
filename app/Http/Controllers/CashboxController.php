<?php

namespace App\Http\Controllers;

use App\Models\CashPeriod;
use App\Services\CashboxService;
use App\Services\CashHierarchyService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CashboxController extends Controller
{
    protected CashboxService $cashboxService;
    protected CashHierarchyService $hierarchyService;

    public function __construct(CashboxService $cashboxService, CashHierarchyService $hierarchyService)
    {
        $this->cashboxService = $cashboxService;
        $this->hierarchyService = $hierarchyService;
    }

    /**
     * Display list of cash periods as cards
     * Requirement 3.5: Display periods as cards with month/year name
     * Requirement 2.1: Filter by company (created_by)
     */
    public function index()
    {
        if (!Auth::user()->can('cashbox_access')) {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        $companyId = Auth::user()->creatorId();
        
        // Get or create current period
        $currentPeriod = $this->cashboxService->getOrCreateCurrentPeriod($companyId);
        
        // Get all periods for this company, ordered by year and month descending
        $periods = CashPeriod::forCompany($companyId)
            ->orderBy('year', 'desc')
            ->orderBy('month', 'desc')
            ->get();

        // Get user's cashbox role for UI permissions
        $user = Auth::user();
        $userRole = $this->hierarchyService->getUserCashboxRole($user);
        $isBoss = $userRole === CashHierarchyService::ROLE_BOSS;
        
        // Check specific permissions (company type OR has permission)
        $canDeposit = $user->type === 'company' || $user->can('cashbox_deposit');
        $canDistribute = $user->type === 'company' || $user->can('cashbox_distribute');
        $canRefund = $user->type === 'company' || $user->can('cashbox_refund');
        
        // Get view permission level for visibility control
        $viewLevel = $this->hierarchyService->getViewPermissionLevel($user);
        $canViewTotalDeposited = $viewLevel === 'boss' || $user->can('cashbox_view_boss');

        return view('cashbox.index', compact('periods', 'currentPeriod', 'userRole', 'isBoss', 'canViewTotalDeposited', 'canDeposit', 'canDistribute', 'canRefund'));
    }

    /**
     * Display diagram page for a specific period
     * Requirement 3.1: Auto-create period for new month
     */
    public function show(CashPeriod $period)
    {
        if (!Auth::user()->can('cashbox_access')) {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        // Check company ownership (Requirement 2.3, 2.4)
        if ($period->created_by != Auth::user()->creatorId()) {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        $user = Auth::user();
        $userRole = $this->hierarchyService->getUserCashboxRole($user);
        $isBoss = $userRole === CashHierarchyService::ROLE_BOSS;
        
        // Check specific permissions (company type OR has permission)
        $canDeposit = $user->type === 'company' || $user->can('cashbox_deposit');
        $canDistribute = $user->type === 'company' || $user->can('cashbox_distribute');
        $canRefund = $user->type === 'company' || $user->can('cashbox_refund');
        
        // Get user's balance for this period
        $balance = $this->cashboxService->getBalance($period, $user);
        
        // Get available recipients for distribution
        $recipients = $this->hierarchyService->getAvailableRecipients($user, $period->created_by);
        
        // Check if user can take self-salary
        $canSelfSalary = ($user->type === 'company' || $user->can('cashbox_self_salary')) && $this->hierarchyService->canDistributeToSelf($user);
        $hasSelfSalaryThisPeriod = $canSelfSalary 
            ? $this->cashboxService->hasSelfSalaryThisPeriod($period, $user) 
            : false;

        // Get transactions that can be refunded (where current user is recipient)
        // Only 'transfer' type distributions can be refunded (salary is personal money)
        // Also include old transactions without distribution_type for backwards compatibility
        $refundableTransactions = \App\Models\CashTransaction::where('cash_period_id', $period->id)
            ->where('recipient_id', $user->id)
            ->where('recipient_type', \App\Models\User::class)
            ->where('type', \App\Models\CashTransaction::TYPE_DISTRIBUTION)
            ->where(function($query) {
                $query->where('distribution_type', \App\Models\CashTransaction::DISTRIBUTION_TYPE_TRANSFER)
                      ->orWhereNull('distribution_type');
            })
            ->with('sender')
            ->orderBy('created_at', 'desc')
            ->get();

        // Get view permission level for visibility control
        $viewLevel = $this->hierarchyService->getViewPermissionLevel($user);
        $canViewTotalDeposited = $viewLevel === 'boss' || $user->can('cashbox_view_boss');

        return view('cashbox.show', compact(
            'period',
            'userRole',
            'isBoss',
            'balance',
            'recipients',
            'canSelfSalary',
            'hasSelfSalaryThisPeriod',
            'refundableTransactions',
            'canViewTotalDeposited',
            'canDeposit',
            'canDistribute',
            'canRefund'
        ));
    }

    /**
     * Get or create current period for the company
     * Requirement 3.1: Auto-create period for new month
     */
    public function getOrCreateCurrentPeriod()
    {
        if (!Auth::user()->can('cashbox_access')) {
            return response()->json(['error' => __('Permission denied.')], 403);
        }

        $companyId = Auth::user()->creatorId();
        $period = $this->cashboxService->getOrCreateCurrentPeriod($companyId);

        return response()->json([
            'success' => true,
            'period' => [
                'id' => $period->id,
                'name' => $period->name,
                'year' => $period->year,
                'month' => $period->month,
                'total_deposited' => $period->total_deposited,
                'is_frozen' => $period->is_frozen,
            ],
        ]);
    }

    /**
     * Display cashbox settings page
     */
    public function settings()
    {
        if (!Auth::user()->can('cashbox_access')) {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        $user = Auth::user();
        $companyId = $user->creatorId();
        $userRole = $this->hierarchyService->getUserCashboxRole($user);
        $isBoss = $userRole === CashHierarchyService::ROLE_BOSS;
        
        // Check specific permissions
        $canManageSettings = $user->type === 'company' || $isBoss;
        
        // Get current currency setting
        $currentCurrency = \DB::table('settings')
            ->where('name', 'cashbox_currency')
            ->where('created_by', $companyId)
            ->value('value') ?? 'EUR';
        
        // Get current period for reset button
        $currentPeriod = $this->cashboxService->getOrCreateCurrentPeriod($companyId);

        return view('cashbox.settings', compact('currentCurrency', 'currentPeriod', 'userRole', 'isBoss', 'canManageSettings'));
    }

    /**
     * Save cashbox settings
     * Requirement 11.3: Save selected currency in company settings
     */
    public function saveSettings(Request $request)
    {
        if (!Auth::user()->can('cashbox_access')) {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        $request->validate([
            'cashbox_currency' => 'required|in:EUR,USD,PLN,CZK',
        ]);

        $companyId = Auth::user()->creatorId();

        // Save the setting using INSERT ON DUPLICATE KEY UPDATE
        \DB::insert(
            'INSERT INTO settings (`value`, `name`, `created_by`, `created_at`, `updated_at`) 
             VALUES (?, ?, ?, ?, ?) 
             ON DUPLICATE KEY UPDATE `value` = VALUES(`value`), `updated_at` = VALUES(`updated_at`)',
            [
                $request->cashbox_currency,
                'cashbox_currency',
                $companyId,
                now(),
                now(),
            ]
        );

        return redirect()->back()->with('success', __('Cashbox settings saved successfully.'));
    }

    /**
     * Reset current period (debug function)
     * Deletes all transactions and resets total_deposited for current month
     */
    public function resetCurrentPeriod(Request $request)
    {
        if (!Auth::user()->can('cashbox_access')) {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        $userRole = $this->hierarchyService->getUserCashboxRole(Auth::user());
        if ($userRole !== CashHierarchyService::ROLE_BOSS) {
            return redirect()->back()->with('error', __('Only Boss can reset period.'));
        }

        $companyId = Auth::user()->creatorId();
        $currentPeriod = $this->cashboxService->getOrCreateCurrentPeriod($companyId);

        \DB::transaction(function () use ($currentPeriod) {
            // Delete all transactions for this period
            \App\Models\CashTransaction::where('cash_period_id', $currentPeriod->id)->delete();
            
            // Reset total deposited
            $currentPeriod->update(['total_deposited' => 0]);
        });

        return redirect()->back()->with('success', __('Period successfully reset.'));
    }
}
