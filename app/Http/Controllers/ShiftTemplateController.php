<?php

namespace App\Http\Controllers;

use App\Models\ShiftTemplate;
use App\Models\WorkPlace;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class ShiftTemplateController extends Controller
{
    /**
     * Display a listing of shift templates.
     */
    public function index(Request $request)
    {
        if (!Auth::user()->can('attendance_manage_shifts')) {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        $workPlaces = WorkPlace::where('created_by', Auth::user()->creatorId())
            ->visibleToUser(Auth::user())
            ->with(['shiftTemplates' => function ($q) {
                $q->orderBy('start_time');
            }])
            ->get();

        $selectedWorkPlaceId = $request->get('work_place_id');

        return view('attendance.shifts.index', compact('workPlaces', 'selectedWorkPlaceId'));
    }

    /**
     * Show the form for creating a new shift template.
     */
    public function create(Request $request)
    {
        if (!Auth::user()->can('attendance_manage_shifts')) {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        $workPlaces = WorkPlace::where('created_by', Auth::user()->creatorId())
            ->visibleToUser(Auth::user())
            ->get();

        $selectedWorkPlaceId = $request->get('work_place_id');

        return view('attendance.shifts.create', compact('workPlaces', 'selectedWorkPlaceId'));
    }


    /**
     * Store a newly created shift template.
     */
    public function store(Request $request)
    {
        if (!Auth::user()->can('attendance_manage_shifts')) {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        $validator = Validator::make($request->all(), [
            'work_place_id' => 'required|exists:work_places,id',
            'name' => 'required|string|max:255',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i',
            'break_minutes' => 'nullable|integer|min:0|max:480',
            'color' => 'nullable|string|max:7',
            'pay_type' => 'required|in:per_shift,hourly',
            'pay_rate' => 'nullable|numeric|min:0',
            'night_bonus_enabled' => 'nullable|boolean',
            'night_bonus_percent' => 'nullable|numeric|min:0|max:100',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        // Verify work place belongs to user's company
        $workPlace = WorkPlace::where('id', $request->work_place_id)
            ->where('created_by', Auth::user()->creatorId())
            ->first();

        if (!$workPlace) {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        $template = new ShiftTemplate();
        $template->work_place_id = $request->work_place_id;
        $template->name = $request->name;
        $template->start_time = $request->start_time;
        $template->end_time = $request->end_time;
        $template->break_minutes = $request->break_minutes ?? 0;
        $template->color = $request->color ?? '#3788d8';
        $template->pay_type = $request->pay_type;
        $template->pay_rate = $request->pay_rate;
        $template->night_bonus_enabled = $request->boolean('night_bonus_enabled');
        $template->night_bonus_percent = $request->night_bonus_percent ?? 20;
        $template->created_by = Auth::user()->creatorId();
        $template->save();

        return redirect()->route('attendance.shifts.index', ['work_place_id' => $template->work_place_id])
            ->with('success', __('Shift template created successfully.'));
    }

    /**
     * Show the form for editing the specified shift template.
     */
    public function edit(ShiftTemplate $shift)
    {
        if (!Auth::user()->can('attendance_manage_shifts')) {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        if ($shift->created_by != Auth::user()->creatorId()) {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        $workPlaces = WorkPlace::where('created_by', Auth::user()->creatorId())
            ->visibleToUser(Auth::user())
            ->get();

        return view('attendance.shifts.edit', compact('shift', 'workPlaces'));
    }

    /**
     * Update the specified shift template.
     */
    public function update(Request $request, ShiftTemplate $shift)
    {
        if (!Auth::user()->can('attendance_manage_shifts')) {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        if ($shift->created_by != Auth::user()->creatorId()) {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        $validator = Validator::make($request->all(), [
            'work_place_id' => 'required|exists:work_places,id',
            'name' => 'required|string|max:255',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i',
            'break_minutes' => 'nullable|integer|min:0|max:480',
            'color' => 'nullable|string|max:7',
            'pay_type' => 'required|in:per_shift,hourly',
            'pay_rate' => 'nullable|numeric|min:0',
            'night_bonus_enabled' => 'nullable|boolean',
            'night_bonus_percent' => 'nullable|numeric|min:0|max:100',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $shift->work_place_id = $request->work_place_id;
        $shift->name = $request->name;
        $shift->start_time = $request->start_time;
        $shift->end_time = $request->end_time;
        $shift->break_minutes = $request->break_minutes ?? 0;
        $shift->color = $request->color ?? '#3788d8';
        $shift->pay_type = $request->pay_type;
        $shift->pay_rate = $request->pay_rate;
        $shift->night_bonus_enabled = $request->boolean('night_bonus_enabled');
        $shift->night_bonus_percent = $request->night_bonus_percent ?? 20;
        $shift->save();

        return redirect()->route('attendance.shifts.index', ['work_place_id' => $shift->work_place_id])
            ->with('success', __('Shift template updated successfully.'));
    }

    /**
     * Remove the specified shift template.
     */
    public function destroy(ShiftTemplate $shift)
    {
        if (!Auth::user()->can('attendance_manage_shifts')) {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        if ($shift->created_by != Auth::user()->creatorId()) {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        $workPlaceId = $shift->work_place_id;

        // Check if template has active schedules
        if ($shift->schedules()->exists()) {
            return redirect()->back()->with('error', __('Cannot delete shift template with active schedules.'));
        }

        $shift->delete();

        return redirect()->route('attendance.shifts.index', ['work_place_id' => $workPlaceId])
            ->with('success', __('Shift template deleted successfully.'));
    }
}
