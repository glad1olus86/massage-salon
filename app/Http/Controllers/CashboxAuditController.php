<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\CashPeriod;
use App\Models\User;
use App\Services\CashboxAuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Controller for cashbox audit page
 * Requirement 13.3: Allow Boss to view audit with filters
 */
class CashboxAuditController extends Controller
{
    /**
     * Display cashbox audit log
     * Requirement 13.3: Only Boss can view audit
     */
    public function index(Request $request)
    {
        // Check permission
        if (!Auth::user()->can('cashbox_view_audit')) {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        $companyId = Auth::user()->creatorId();

        // Build query for cashbox audit logs
        $query = AuditLog::where('created_by', $companyId)
            ->whereIn('event_type', [
                CashboxAuditService::EVENT_DEPOSIT,
                CashboxAuditService::EVENT_DISTRIBUTION,
                CashboxAuditService::EVENT_REFUND,
                CashboxAuditService::EVENT_SELF_SALARY,
                CashboxAuditService::EVENT_STATUS_CHANGE,
            ])
            ->with('user');

        // Filter by date range
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->byDateRange($request->start_date, $request->end_date);
        }

        // Filter by user (who performed the action)
        if ($request->filled('user_id')) {
            $query->byUser($request->user_id);
        }

        // Filter by period
        if ($request->filled('period_id')) {
            $query->where(function ($q) use ($request) {
                $q->whereJsonContains('new_values->period_id', (int) $request->period_id)
                  ->orWhereRaw("JSON_EXTRACT(new_values, '$.period_id') = ?", [$request->period_id]);
            });
        }

        // Filter by event type
        if ($request->filled('event_type')) {
            $query->byEventType($request->event_type);
        }

        $auditLogs = $query->latest()->paginate(20);

        // Get users for filter dropdown
        $users = User::where('created_by', $companyId)
            ->get()
            ->pluck('name', 'id');

        // Get periods for filter dropdown
        $periods = CashPeriod::where('created_by', $companyId)
            ->orderBy('year', 'desc')
            ->orderBy('month', 'desc')
            ->get()
            ->mapWithKeys(function ($period) {
                return [$period->id => $period->name];
            });

        // Event types for filter
        $eventTypes = [
            CashboxAuditService::EVENT_DEPOSIT => __('Money deposit'),
            CashboxAuditService::EVENT_DISTRIBUTION => __('Money distribution'),
            CashboxAuditService::EVENT_REFUND => __('Money refund'),
            CashboxAuditService::EVENT_SELF_SALARY => __('Self salary'),
            CashboxAuditService::EVENT_STATUS_CHANGE => __('Status change'),
        ];

        return view('cashbox.audit', compact('auditLogs', 'users', 'periods', 'eventTypes'));
    }
}
