<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\User;
use App\Models\Worker;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuditLogController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if (Auth::user()->can('view audit log')) {
            $query = AuditLog::forCurrentUser()->with('user');

            // Filter by date
            if ($request->filled('start_date') && $request->filled('end_date')) {
                $query->byDateRange($request->start_date, $request->end_date);
            }

            // Filter by user (who performed the action)
            if ($request->filled('user_id')) {
                $query->byUser($request->user_id);
            }

            // Filter by worker (who was affected)
            if ($request->filled('worker_id')) {
                $query->where('subject_type', 'App\Models\Worker')
                    ->where('subject_id', $request->worker_id);
            }

            // Filter by event type
            if ($request->filled('event_type')) {
                $query->byEventType($request->event_type);
            }

            $auditLogs = $query->latest()->paginate(20);
            $users = User::where('created_by', '=', Auth::user()->creatorId())->get()->pluck('name', 'id');

            // Workers list for filter
            $workers = \App\Models\Worker::where('created_by', Auth::user()->creatorId())
                ->get()
                ->mapWithKeys(function ($worker) {
                    return [$worker->id => $worker->first_name . ' ' . $worker->last_name];
                });

            // List of all possible event types for filter
            $eventTypes = [
                'worker.created' => __('Worker created'),
                'worker.updated' => __('Worker updated'),
                'worker.deleted' => __('Worker deleted'),
                'worker.checked_in' => __('Check-in'),
                'worker.checked_out' => __('Check-out'),
                'worker.hired' => __('Employment'),
                'worker.dismissed' => __('Dismissal'),
                'room.created' => __('Room created'),
                'room.updated' => __('Room updated'),
                'room.deleted' => __('Room deleted'),
                'work_place.created' => __('Work place created'),
                'work_place.updated' => __('Work place updated'),
                'work_place.deleted' => __('Work place deleted'),
                'hotel.created' => __('Hotel created'),
                'hotel.updated' => __('Hotel updated'),
                'hotel.deleted' => __('Hotel deleted'),
                // Cashbox events
                'cashbox.deposit' => __('Cashbox deposit'),
                'cashbox.distribution' => __('Cashbox distribution'),
                'cashbox.refund' => __('Cashbox refund'),
                'cashbox.self_salary' => __('Cashbox self salary'),
                'cashbox.status_change' => __('Cashbox status change'),
                // Document events
                'document.generated' => __('Document generated'),
                'document_template.created' => __('Document template created'),
                'document_template.updated' => __('Document template updated'),
                'document_template.deleted' => __('Document template deleted'),
            ];

            return view('audit_log.index', compact('auditLogs', 'users', 'workers', 'eventTypes'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function calendarView(Request $request)
    {
        if (Auth::user()->can('view audit log')) {
            return view('audit_log.calendar');
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    /**
     * Get calendar data (API).
     */
    public function calendar($year, $month)
    {
        if (Auth::user()->can('view audit log')) {
            $startDate = Carbon::create($year, $month, 1)->startOfMonth();
            $endDate = $startDate->copy()->endOfMonth();

            $logs = AuditLog::forCurrentUser()
                ->whereBetween('created_at', [$startDate, $endDate])
                ->get()
                ->groupBy(function ($log) {
                    return $log->created_at->day;
                });

            $days = [];
            foreach ($logs as $day => $dayLogs) {
                $days[$day] = [
                    'total' => $dayLogs->count(),
                    'events' => $dayLogs->groupBy('event_type')
                        ->map->count()
                        ->toArray()
                ];
            }

            return response()->json([
                'year' => $year,
                'month' => $month,
                'days' => $days
            ]);
        } else {
            return response()->json(['error' => 'Permission denied'], 403);
        }
    }

    /**
     * Get details for a specific day.
     */
    public function dayDetails($date)
    {
        if (Auth::user()->can('view audit log')) {
            $parsedDate = Carbon::parse($date);

            $logs = AuditLog::forCurrentUser()
                ->whereDate('created_at', $parsedDate)
                ->with('user')
                ->latest()
                ->get()
                ->groupBy(function ($log) {
                    return $log->user_name;
                });

            return view('audit_log.day_details', compact('logs', 'parsedDate'));
        } else {
            return response()->json(['error' => 'Permission denied'], 403);
        }
    }
}
