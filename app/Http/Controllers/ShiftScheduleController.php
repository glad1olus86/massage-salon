<?php

namespace App\Http\Controllers;

use App\Models\ShiftSchedule;
use App\Models\ShiftTemplate;
use App\Models\Worker;
use App\Models\WorkPlace;
use App\Services\AttendanceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class ShiftScheduleController extends Controller
{
    protected AttendanceService $attendanceService;

    public function __construct(AttendanceService $attendanceService)
    {
        $this->attendanceService = $attendanceService;
    }

    /**
     * Display a listing of shift schedules.
     */
    public function index(Request $request)
    {
        if (!Auth::user()->can('attendance_manage_schedule')) {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        $workPlaces = WorkPlace::where('created_by', Auth::user()->creatorId())
            ->visibleToUser(Auth::user())
            ->get();

        $schedules = ShiftSchedule::whereHas('worker', function ($q) {
                $q->where('created_by', Auth::user()->creatorId());
                $this->attendanceService->applyVisibilityFilter($q, Auth::user());
            })
            ->with(['worker', 'shiftTemplate.workPlace'])
            ->active()
            ->orderBy('created_at', 'desc')
            ->paginate(50);

        return view('attendance.schedule.index', compact('workPlaces', 'schedules'));
    }


    /**
     * Assign a shift schedule to a single worker.
     */
    public function assign(Request $request)
    {
        if (!Auth::user()->can('attendance_manage_schedule')) {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        $validator = Validator::make($request->all(), [
            'worker_id' => 'required|exists:workers,id',
            'shift_template_id' => 'required|exists:shift_templates,id',
            'work_days' => 'required|array|min:1',
            'work_days.*' => 'integer|min:1|max:7',
            'valid_from' => 'required|date',
            'valid_until' => 'nullable|date|after_or_equal:valid_from',
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

        // Verify shift template belongs to user's company
        $template = ShiftTemplate::where('id', $request->shift_template_id)
            ->where('created_by', Auth::user()->creatorId())
            ->first();

        if (!$template) {
            return redirect()->back()->with('error', __('Shift template not found.'));
        }

        // End any existing active schedules for this worker (except if same template)
        ShiftSchedule::where('worker_id', $worker->id)
            ->where('shift_template_id', '!=', $template->id)
            ->active()
            ->update(['valid_until' => now()->subDay()]);

        // Check if schedule already exists for this worker and template
        $existingSchedule = ShiftSchedule::where('worker_id', $worker->id)
            ->where('shift_template_id', $template->id)
            ->active()
            ->first();

        if ($existingSchedule) {
            // Update existing schedule
            $existingSchedule->work_days = $request->work_days;
            $existingSchedule->valid_from = $request->valid_from;
            $existingSchedule->valid_until = $request->valid_until;
            $existingSchedule->save();
        } else {
            // Create new schedule
            $schedule = new ShiftSchedule();
            $schedule->worker_id = $worker->id;
            $schedule->shift_template_id = $template->id;
            $schedule->work_days = $request->work_days;
            $schedule->valid_from = $request->valid_from;
            $schedule->valid_until = $request->valid_until;
            $schedule->created_by = Auth::user()->creatorId();
            $schedule->save();
        }

        return redirect()->back()->with('success', __('Schedule assigned successfully.'));
    }

    /**
     * Bulk assign shift schedules to multiple workers.
     */
    public function assignBulk(Request $request)
    {
        if (!Auth::user()->can('attendance_manage_schedule')) {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        $validator = Validator::make($request->all(), [
            'worker_ids' => 'required|array|min:1',
            'worker_ids.*' => 'exists:workers,id',
            'shift_template_id' => 'required|exists:shift_templates,id',
            'work_days' => 'required|array|min:1',
            'work_days.*' => 'integer|min:1|max:7',
            'valid_from' => 'required|date',
            'valid_until' => 'nullable|date|after_or_equal:valid_from',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        // Verify shift template belongs to user's company
        $template = ShiftTemplate::where('id', $request->shift_template_id)
            ->where('created_by', Auth::user()->creatorId())
            ->first();

        if (!$template) {
            return redirect()->back()->with('error', __('Shift template not found.'));
        }

        $assigned = 0;
        $skipped = 0;

        foreach ($request->worker_ids as $workerId) {
            $worker = Worker::where('id', $workerId)
                ->where('created_by', Auth::user()->creatorId())
                ->first();

            if (!$worker) {
                $skipped++;
                continue;
            }

            // End any existing active schedules for this worker (except if same template)
            ShiftSchedule::where('worker_id', $worker->id)
                ->where('shift_template_id', '!=', $template->id)
                ->active()
                ->update(['valid_until' => now()->subDay()]);

            // Check if schedule already exists for this worker and template
            $existingSchedule = ShiftSchedule::where('worker_id', $worker->id)
                ->where('shift_template_id', $template->id)
                ->active()
                ->first();

            if ($existingSchedule) {
                // Update existing schedule
                $existingSchedule->work_days = $request->work_days;
                $existingSchedule->valid_from = $request->valid_from;
                $existingSchedule->valid_until = $request->valid_until;
                $existingSchedule->save();
            } else {
                // Create new schedule
                $schedule = new ShiftSchedule();
                $schedule->worker_id = $worker->id;
                $schedule->shift_template_id = $template->id;
                $schedule->work_days = $request->work_days;
                $schedule->valid_from = $request->valid_from;
                $schedule->valid_until = $request->valid_until;
                $schedule->created_by = Auth::user()->creatorId();
                $schedule->save();
            }

            $assigned++;
        }

        $message = __('Schedules assigned: :count', ['count' => $assigned]);
        if ($skipped > 0) {
            $message .= '. ' . __('Skipped: :count', ['count' => $skipped]);
        }

        return redirect()->back()->with('success', $message);
    }

    /**
     * Remove the specified shift schedule.
     */
    public function destroy(ShiftSchedule $schedule)
    {
        if (!Auth::user()->can('attendance_manage_schedule')) {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        // Verify schedule belongs to user's company
        $worker = Worker::where('id', $schedule->worker_id)
            ->where('created_by', Auth::user()->creatorId())
            ->first();

        if (!$worker) {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        // Soft delete by setting valid_until to yesterday
        $schedule->valid_until = now()->subDay();
        $schedule->save();

        return redirect()->back()->with('success', __('Schedule removed successfully.'));
    }
}
