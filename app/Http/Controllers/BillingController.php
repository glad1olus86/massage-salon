<?php

namespace App\Http\Controllers;

use App\Models\UserBillingPeriod;
use App\Services\UserBillingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BillingController extends Controller
{
    protected UserBillingService $billingService;

    public function __construct()
    {
        $this->billingService = new UserBillingService();
    }

    /**
     * Display billing dashboard
     */
    public function index()
    {
        $companyId = Auth::user()->creatorId();
        
        $billing = $this->billingService->getBillingBreakdown($companyId);
        $history = $this->billingService->getBillingHistory($companyId);

        return view('billing.index', compact('billing', 'history'));
    }

    /**
     * Get billing info via AJAX (for modals/widgets)
     */
    public function getBillingInfo(Request $request)
    {
        $companyId = Auth::user()->creatorId();
        $billing = $this->billingService->getBillingBreakdown($companyId);

        return response()->json([
            'success' => true,
            'billing' => $billing,
        ]);
    }

    /**
     * Check role limit before user creation (AJAX)
     */
    public function checkRoleLimit(Request $request)
    {
        $request->validate([
            'role' => 'required|string',
        ]);

        $companyId = Auth::user()->creatorId();
        $limitInfo = $this->billingService->checkRoleLimit($companyId, $request->role);

        return response()->json([
            'success' => true,
            'limit_info' => $limitInfo,
        ]);
    }

    /**
     * Get delete warning info (AJAX)
     */
    public function getDeleteWarning(Request $request)
    {
        $request->validate([
            'role' => 'required|string',
        ]);

        $companyId = Auth::user()->creatorId();
        $warning = $this->billingService->getDeleteWarning($companyId, $request->role);

        return response()->json([
            'success' => true,
            'warning' => $warning,
        ]);
    }

    /**
     * Get billing history
     */
    public function history()
    {
        $companyId = Auth::user()->creatorId();
        $history = $this->billingService->getBillingHistory($companyId, 50);

        return view('billing.history', compact('history'));
    }

    /**
     * Get period details
     */
    public function periodDetails($id)
    {
        $period = UserBillingPeriod::with('logs.user')->findOrFail($id);
        
        // Ensure user can only view their own periods
        if ($period->company_id !== Auth::user()->creatorId()) {
            abort(403);
        }

        return view('billing.period', compact('period'));
    }
}
