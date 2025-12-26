<?php

namespace App\Http\Controllers;

use App\Models\Position;
use App\Models\WorkPlace;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PositionController extends Controller
{
    /**
     * Display positions for a work place
     */
    public function index(WorkPlace $workPlace)
    {
        if (!Auth::user()->can('manage work place')) {
            return redirect()->back()->with('error', __('Insufficient permissions'));
        }

        // Multi-tenancy check
        if ($workPlace->created_by !== Auth::user()->creatorId()) {
            return redirect()->back()->with('error', __('Work place not found'));
        }

        $positions = $workPlace->positions()
            ->withCount(['currentAssignments as workers_count'])
            ->orderBy('name')
            ->get();

        return view('work_place.positions', compact('workPlace', 'positions'));
    }

    /**
     * Store a new position
     */
    public function store(Request $request, WorkPlace $workPlace)
    {
        if (!Auth::user()->can('manage work place')) {
            return redirect()->back()->with('error', __('Insufficient permissions'));
        }

        // Multi-tenancy check
        if ($workPlace->created_by !== Auth::user()->creatorId()) {
            return redirect()->back()->with('error', __('Work place not found'));
        }

        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        Position::create([
            'work_place_id' => $workPlace->id,
            'name' => $request->name,
            'created_by' => Auth::user()->creatorId(),
        ]);

        // Handle mobile redirect
        if ($request->has('redirect_to') && str_starts_with($request->redirect_to, 'mobile_workplace_')) {
            return redirect()->route('mobile.workplaces.show', $workPlace->id)->with('success', __('Position created'));
        }

        return redirect()->back()->with('success', __('Position created'));
    }

    /**
     * Update a position
     */
    public function update(Request $request, Position $position)
    {
        if (!Auth::user()->can('manage work place')) {
            return redirect()->back()->with('error', __('Insufficient permissions'));
        }

        // Multi-tenancy check
        if ($position->created_by !== Auth::user()->creatorId()) {
            return redirect()->back()->with('error', __('Position not found'));
        }

        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $position->update([
            'name' => $request->name,
        ]);

        // Handle mobile redirect
        if ($request->has('redirect_to') && str_starts_with($request->redirect_to, 'mobile_workplace_')) {
            return redirect()->route('mobile.workplaces.show', $position->work_place_id)->with('success', __('Position updated'));
        }

        return redirect()->back()->with('success', __('Position updated'));
    }

    /**
     * Delete a position
     */
    public function destroy(Request $request, Position $position)
    {
        if (!Auth::user()->can('manage work place')) {
            return redirect()->back()->with('error', __('Insufficient permissions'));
        }

        // Multi-tenancy check
        if ($position->created_by !== Auth::user()->creatorId()) {
            return redirect()->back()->with('error', __('Position not found'));
        }

        $workPlaceId = $position->work_place_id;
        $position->delete();

        // Handle mobile redirect
        if ($request->has('redirect_to') && str_starts_with($request->redirect_to, 'mobile_workplace_')) {
            return redirect()->route('mobile.workplaces.show', $workPlaceId)->with('success', __('Position deleted'));
        }

        return redirect()->back()->with('success', __('Position deleted'));
    }

    /**
     * Get positions for a work place as JSON (for AJAX)
     */
    public function getPositionsJson(WorkPlace $workPlace)
    {
        // Allow access for users who can manage workers or work places
        if (!Auth::user()->can('manage work place') && !Auth::user()->can('manage worker')) {
            return response()->json(['error' => __('Insufficient permissions')], 403);
        }

        if ($workPlace->created_by !== Auth::user()->creatorId()) {
            return response()->json(['error' => __('Work place not found')], 404);
        }

        $positions = $workPlace->positions()->orderBy('name')->get(['id', 'name']);

        return response()->json($positions);
    }

    /**
     * Show workers assigned to a specific position (AJAX popup)
     */
    public function showWorkers(Position $position)
    {
        if (!Auth::user()->can('manage work place')) {
            return response()->json(['error' => __('Insufficient permissions')], 403);
        }

        // Multi-tenancy check
        if ($position->created_by !== Auth::user()->creatorId()) {
            return response()->json(['error' => __('Position not found')], 404);
        }

        $position->load(['workPlace', 'currentAssignments.worker']);

        return view('work_place.position_workers', compact('position'));
    }

    /**
     * Assign workers to a position (bulk)
     */
    public function assignWorkers(Request $request, Position $position)
    {
        if (!Auth::user()->can('manage work place')) {
            return redirect()->back()->with('error', __('Insufficient permissions'));
        }

        // Multi-tenancy check
        if ($position->created_by !== Auth::user()->creatorId()) {
            return redirect()->back()->with('error', __('Position not found'));
        }

        $request->validate([
            'worker_ids' => 'required|string',
        ]);

        $workerIds = array_filter(explode(',', $request->worker_ids));
        $assigned = 0;

        foreach ($workerIds as $workerId) {
            $worker = \App\Models\Worker::where('id', $workerId)
                ->where('created_by', Auth::user()->creatorId())
                ->first();

            if (!$worker) continue;

            // Skip if already has active work assignment
            if ($worker->currentWorkAssignment) continue;

            $assignment = new \App\Models\WorkAssignment();
            $assignment->worker_id = $worker->id;
            $assignment->work_place_id = $position->work_place_id;
            $assignment->position_id = $position->id;
            $assignment->started_at = now();
            $assignment->created_by = Auth::user()->creatorId();
            $assignment->save();
            $assigned++;
        }

        return redirect()->back()->with('success', __('Workers assigned: :count', ['count' => $assigned]));
    }

    /**
     * Dismiss workers from a position (bulk)
     */
    public function dismissWorkers(Request $request, Position $position)
    {
        if (!Auth::user()->can('manage work place')) {
            return redirect()->back()->with('error', __('Insufficient permissions'));
        }

        // Multi-tenancy check
        if ($position->created_by !== Auth::user()->creatorId()) {
            return redirect()->back()->with('error', __('Position not found'));
        }

        $request->validate([
            'worker_ids' => 'required|string',
        ]);

        $workerIds = array_filter(explode(',', $request->worker_ids));
        $dismissed = 0;

        foreach ($workerIds as $workerId) {
            $assignment = \App\Models\WorkAssignment::where('worker_id', $workerId)
                ->where('position_id', $position->id)
                ->whereNull('ended_at')
                ->first();

            if ($assignment) {
                $assignment->ended_at = now();
                $assignment->save();
                $dismissed++;
            }
        }

        return redirect()->back()->with('success', __('Workers dismissed: :count', ['count' => $dismissed]));
    }
}
