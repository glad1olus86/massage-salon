<?php

namespace App\Http\Controllers;

use App\Services\JobsiDashboardService;
use App\Services\CashboxService;
use App\Services\CashHierarchyService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class JobsiDashboardController extends Controller
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
     * Display the JOBSI dashboard
     */
    public function index(Request $request)
    {
        $hotelId = $request->get('hotel_id');
        $workplaceId = $request->get('workplace_id');
        $month = $request->get('month');

        // Get statistics
        $hotelStats = $this->dashboardService->getHotelStats($hotelId);
        $workplaceStats = $this->dashboardService->getWorkplaceStats($workplaceId, $month);
        $cashboxStats = $this->dashboardService->getCashboxStats($month);
        $chartData = $this->dashboardService->getCashboxChartData();

        // Get filter options
        $hotels = $this->dashboardService->getHotels();
        $workplaces = $this->dashboardService->getWorkplaces();
        $months = $this->dashboardService->getAvailableMonths();

        // Cashbox quick actions data
        $user = Auth::user();
        $companyId = $user->creatorId();
        $userRole = $this->hierarchyService->getUserCashboxRole($user);
        $isBoss = $userRole === CashHierarchyService::ROLE_BOSS;
        $canDistribute = $user->can('cashbox_distribute');
        
        // Get current period for cashbox operations
        $currentPeriod = null;
        $cashboxBalance = ['received' => 0, 'sent' => 0, 'available' => 0];
        $recipients = collect();
        
        if ($user->can('cashbox_access')) {
            $currentPeriod = $this->cashboxService->getOrCreateCurrentPeriod($companyId);
            $cashboxBalance = $this->cashboxService->getBalance($currentPeriod, $user);
            $recipients = $this->hierarchyService->getAvailableRecipients($user, $companyId);
        }

        return view('dashboard.jobsi-dashboard', compact(
            'hotelStats',
            'workplaceStats',
            'cashboxStats',
            'chartData',
            'hotels',
            'workplaces',
            'months',
            'hotelId',
            'workplaceId',
            'month',
            'currentPeriod',
            'cashboxBalance',
            'recipients',
            'userRole',
            'isBoss',
            'canDistribute'
        ));
    }

    /**
     * Get hotel stats via AJAX
     */
    public function getHotelStats(Request $request)
    {
        $hotelId = $request->get('hotel_id');
        $stats = $this->dashboardService->getHotelStats($hotelId);
        
        return response()->json($stats);
    }

    /**
     * Get workplace stats via AJAX
     */
    public function getWorkplaceStats(Request $request)
    {
        $workplaceId = $request->get('workplace_id');
        $month = $request->get('month');
        $stats = $this->dashboardService->getWorkplaceStats($workplaceId, $month);
        
        return response()->json($stats);
    }

    /**
     * Get cashbox stats via AJAX
     */
    public function getCashboxStats(Request $request)
    {
        $month = $request->get('month');
        $stats = $this->dashboardService->getCashboxStats($month);
        
        return response()->json($stats);
    }
}
