<?php

namespace App\Http\Controllers;

use App\Models\CashPeriod;
use App\Services\CashboxService;
use App\Services\CashDiagramService;
use App\Services\CashHierarchyService;
use Illuminate\Support\Facades\Auth;

class CashDiagramController extends Controller
{
    protected CashboxService $cashboxService;
    protected CashDiagramService $diagramService;
    protected CashHierarchyService $hierarchyService;

    public function __construct(
        CashboxService $cashboxService, 
        CashDiagramService $diagramService,
        CashHierarchyService $hierarchyService
    ) {
        $this->cashboxService = $cashboxService;
        $this->diagramService = $diagramService;
        $this->hierarchyService = $hierarchyService;
    }

    /**
     * Get diagram data as JSON
     * Requirement 9.1: Display diagram as tree with participant nodes
     * Role-based visibility: boss sees all, manager sees own branch, curator sees own transactions
     */
    public function getDiagram(CashPeriod $period)
    {
        if (!Auth::user()->can('cashbox_access')) {
            return response()->json(['error' => __('Permission denied.')], 403);
        }

        // Check company ownership
        if ($period->created_by != Auth::user()->creatorId()) {
            return response()->json(['error' => __('Permission denied.')], 403);
        }

        $user = Auth::user();
        $viewLevel = $this->hierarchyService->getViewPermissionLevel($user);

        // Build tree based on view permission level
        $tree = match($viewLevel) {
            'boss' => $this->diagramService->buildTree($period),
            'manager' => $this->diagramService->buildTreeForManager($period, $user),
            'curator' => $this->diagramService->buildTreeForCurator($period, $user),
            default => [
                'period' => [
                    'id' => $period->id,
                    'name' => $period->name,
                    'year' => $period->year,
                    'month' => $period->month,
                    'total_deposited' => (float) $period->total_deposited,
                    'is_frozen' => $period->is_frozen,
                ],
                'nodes' => [],
                'view_mode' => 'none',
            ],
        };

        $summary = $this->diagramService->getPeriodSummary($period);

        return response()->json([
            'success' => true,
            'diagram' => $tree,
            'summary' => $summary,
            'view_level' => $viewLevel,
        ]);
    }

    /**
     * Get current user's balance for a period
     * Requirement 10.1: Display budget block with Received/Sent
     * Requirement 10.2: Auto-update balance on each transaction
     */
    public function getBalance(CashPeriod $period)
    {
        if (!Auth::user()->can('cashbox_access')) {
            return response()->json(['error' => __('Permission denied.')], 403);
        }

        // Check company ownership
        if ($period->created_by != Auth::user()->creatorId()) {
            return response()->json(['error' => __('Permission denied.')], 403);
        }

        $balance = $this->cashboxService->getBalance($period, Auth::user());

        return response()->json([
            'success' => true,
            'balance' => [
                'received' => $balance['received'],
                'sent' => $balance['sent'],
                'available' => $balance['available'],
            ],
        ]);
    }
}
