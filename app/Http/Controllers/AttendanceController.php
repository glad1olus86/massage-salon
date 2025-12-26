<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\ShiftTemplate;
use App\Models\Worker;
use App\Models\WorkPlace;
use App\Services\AttendanceService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class AttendanceController extends Controller
{
    protected AttendanceService $attendanceService;

    public function __construct(AttendanceService $attendanceService)
    {
        $this->attendanceService = $attendanceService;
    }

    /**
     * Display the weekly schedule calendar.
     */
    public function index(Request $request)
    {
        if (!Auth::user()->can('attendance_access')) {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        $workPlaces = WorkPlace::where('created_by', Auth::user()->creatorId())
            ->visibleToUser(Auth::user())
            ->get();

        $shiftTemplates = ShiftTemplate::where('created_by', Auth::user()->creatorId())->get();

        $selectedWorkPlaceId = $request->get('work_place_id');
        $startDate = $request->get('start_date', Carbon::now()->startOfWeek()->format('Y-m-d'));

        $scheduleData = $this->attendanceService->getScheduleForWeek(
            Auth::user()->creatorId(),
            $startDate,
            $selectedWorkPlaceId ? (int) $selectedWorkPlaceId : null,
            Auth::user()
        );

        $weekStart = Carbon::parse($startDate)->startOfWeek();
        $weekEnd = $weekStart->copy()->endOfWeek();
        $weekDays = [];
        for ($date = $weekStart->copy(); $date->lte($weekEnd); $date->addDay()) {
            $weekDays[] = $date->copy();
        }

        return view('attendance.index', compact(
            'workPlaces',
            'shiftTemplates',
            'selectedWorkPlaceId',
            'scheduleData',
            'weekStart',
            'weekEnd',
            'weekDays'
        ));
    }


    /**
     * Display attendance for a specific day.
     */
    public function daily(Request $request, ?string $date = null)
    {
        if (!Auth::user()->can('attendance_access')) {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        $date = $date ?? Carbon::today()->format('Y-m-d');
        $carbonDate = Carbon::parse($date);

        $workPlaces = WorkPlace::where('created_by', Auth::user()->creatorId())
            ->visibleToUser(Auth::user())
            ->get();

        $shiftTemplates = ShiftTemplate::where('created_by', Auth::user()->creatorId())->get();

        $selectedWorkPlaceId = $request->get('work_place_id');
        $selectedShiftId = $request->get('shift_template_id');

        $scheduledWorkers = $this->attendanceService->getWorkersForDate(
            $date,
            Auth::user()->creatorId(),
            $selectedWorkPlaceId ? (int) $selectedWorkPlaceId : null,
            $selectedShiftId ? (int) $selectedShiftId : null,
            Auth::user()
        );

        // Calculate statistics - workers without attendance record are considered present by default
        $markedPresent = $scheduledWorkers->where('attendance.status', Attendance::STATUS_PRESENT)->count();
        $notMarked = $scheduledWorkers->filter(fn($w) => !$w['attendance'])->count();
        
        $stats = [
            'total' => $scheduledWorkers->count(),
            'present' => $markedPresent + $notMarked, // Not marked = present by default
            'late' => $scheduledWorkers->where('attendance.status', Attendance::STATUS_LATE)->count(),
            'absent' => $scheduledWorkers->where('attendance.status', Attendance::STATUS_ABSENT)->count(),
            'sick' => $scheduledWorkers->where('attendance.status', Attendance::STATUS_SICK)->count(),
            'vacation' => $scheduledWorkers->where('attendance.status', Attendance::STATUS_VACATION)->count(),
        ];

        return view('attendance.daily', compact(
            'date',
            'carbonDate',
            'workPlaces',
            'shiftTemplates',
            'selectedWorkPlaceId',
            'selectedShiftId',
            'scheduledWorkers',
            'stats'
        ));
    }

    /**
     * Mark attendance for a single worker.
     */
    public function mark(Request $request)
    {
        if (!Auth::user()->can('attendance_mark')) {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        $validator = Validator::make($request->all(), [
            'worker_id' => 'required|exists:workers,id',
            'date' => 'required|date',
            'check_in' => 'nullable|date_format:H:i',
            'check_out' => 'nullable|date_format:H:i',
            'status' => 'required|in:present,late,absent,sick,vacation',
            'notes' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        // Verify worker belongs to user's company
        $worker = Worker::where('id', $request->worker_id)
            ->where('created_by', Auth::user()->creatorId())
            ->first();

        if (!$worker) {
            return redirect()->back()->with('error', __('Worker not found.'));
        }

        $this->attendanceService->markAttendance(
            $request->worker_id,
            $request->date,
            [
                'check_in' => $request->check_in,
                'check_out' => $request->check_out,
                'status' => $request->status,
                'notes' => $request->notes,
            ],
            Auth::user()
        );

        return redirect()->back()->with('success', __('Attendance marked successfully.'));
    }

    /**
     * Mark all scheduled workers as present for a date.
     */
    public function markBulk(Request $request)
    {
        if (!Auth::user()->can('attendance_mark')) {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        $validator = Validator::make($request->all(), [
            'date' => 'required|date',
            'worker_ids' => 'required|array|min:1',
            'worker_ids.*' => 'exists:workers,id',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        // Filter to only workers belonging to user's company
        $workerIds = Worker::whereIn('id', $request->worker_ids)
            ->where('created_by', Auth::user()->creatorId())
            ->pluck('id')
            ->toArray();

        $count = $this->attendanceService->markAllPresent(
            $request->date,
            $workerIds,
            Auth::user()
        );

        return redirect()->back()->with('success', __('Marked :count workers as present.', ['count' => $count]));
    }

    /**
     * Get calendar data as JSON for AJAX requests.
     */
    public function calendarData(Request $request)
    {
        if (!Auth::user()->can('attendance_access')) {
            return response()->json(['error' => __('Permission denied.')], 403);
        }

        $startDate = $request->get('start_date', Carbon::now()->startOfWeek()->format('Y-m-d'));
        $workPlaceId = $request->get('work_place_id');

        $scheduleData = $this->attendanceService->getScheduleForWeek(
            Auth::user()->creatorId(),
            $startDate,
            $workPlaceId ? (int) $workPlaceId : null,
            Auth::user()
        );

        $result = [];
        foreach ($scheduleData as $item) {
            $workerData = [
                'worker_id' => $item['id'],
                'worker_name' => $item['name'],
                'work_place' => $item['work_place'] ?? '',
                'schedule' => [],
            ];

            foreach ($item['days'] as $dayData) {
                $date = $dayData['date'];
                $shift = $dayData['shift'];
                
                if ($shift) {
                    $workerData['schedule'][$date] = [
                        'template_id' => $shift['id'],
                        'template_name' => $shift['name'],
                        'color' => $shift['color'],
                        'time_range' => $shift['time_range'],
                    ];
                } else {
                    $workerData['schedule'][$date] = null;
                }
            }

            $result[] = $workerData;
        }

        return response()->json([
            'data' => $result,
            'start_date' => $startDate,
        ]);
    }

    /**
     * Search workers for bulk assignment.
     */
    public function workersSearch(Request $request)
    {
        if (!Auth::user()->can('attendance_access')) {
            return response()->json(['error' => __('Permission denied.')], 403);
        }

        $search = $request->get('search', '');
        $workPlaceId = $request->get('work_place_id');

        $query = Worker::where('created_by', Auth::user()->creatorId())
            ->with(['currentWorkAssignment.workPlace']);

        // Filter by workplace if specified
        if ($workPlaceId) {
            $query->whereHas('currentWorkAssignment', function ($q) use ($workPlaceId) {
                $q->where('work_place_id', $workPlaceId)->whereNull('ended_at');
            });
        }

        // Search by name
        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'LIKE', "%{$search}%")
                    ->orWhere('last_name', 'LIKE', "%{$search}%")
                    ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%{$search}%"]);
            });
        }

        $workers = $query->limit(50)->get();

        $result = $workers->map(function ($worker) {
            return [
                'id' => $worker->id,
                'name' => $worker->first_name . ' ' . $worker->last_name,
                'work_place' => $worker->currentWorkAssignment?->workPlace?->name ?? '',
            ];
        });

        return response()->json(['data' => $result]);
    }
}
