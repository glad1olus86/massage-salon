<?php

namespace App\Http\Controllers;

use App\Services\JobsiDashboardService;
use App\Services\CashboxService;
use App\Services\CashHierarchyService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MobileDashboardController extends Controller
{
    protected JobsiDashboardService $dashboardService;
    protected CashboxService $cashboxService;
    protected CashHierarchyService $hierarchyService;

    public function __construct(
        JobsiDashboardService $dashboardService,
        CashboxService $cashboxService,
        CashHierarchyService $hierarchyService
    ) {
        $this->dashboardService = $dashboardService;
        $this->cashboxService = $cashboxService;
        $this->hierarchyService = $hierarchyService;
    }

    /**
     * Display mobile dashboard
     */
    public function index(Request $request)
    {
        // Super admin redirects to desktop
        if (Auth::user()->type == 'super admin') {
            return redirect()->route('dashboard');
        }

        $hotelId = $request->get('hotel_id');
        $workplaceId = $request->get('workplace_id');
        $month = $request->get('month');

        // Get statistics
        $hotelStats = $this->dashboardService->getHotelStats($hotelId);
        $workplaceStats = $this->dashboardService->getWorkplaceStats($workplaceId, $month);

        // Get filter options
        $hotels = $this->dashboardService->getHotels();
        $workplaces = $this->dashboardService->getWorkplaces();

        // Cashbox data
        $user = Auth::user();
        $companyId = $user->creatorId();
        $userRole = $this->hierarchyService->getUserCashboxRole($user);
        $isBoss = $userRole === CashHierarchyService::ROLE_BOSS;
        $canDistribute = $user->can('cashbox_distribute');

        $currentPeriod = null;
        $cashboxBalance = ['received' => 0, 'sent' => 0, 'available' => 0];

        if ($user->can('cashbox_access')) {
            $currentPeriod = $this->cashboxService->getOrCreateCurrentPeriod($companyId);
            $cashboxBalance = $this->cashboxService->getBalance($currentPeriod, $user);
        }

        return view('mobile.dashboard', compact(
            'hotelStats',
            'workplaceStats',
            'hotels',
            'workplaces',
            'hotelId',
            'workplaceId',
            'currentPeriod',
            'cashboxBalance',
            'isBoss',
            'canDistribute'
        ));
    }
}
