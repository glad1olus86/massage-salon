<?php

namespace App\Http\Controllers;

use App\Models\Worker;
use App\Models\WorkPlace;
use App\Models\WorkAssignment;
use App\Models\Position;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class WorkAssignmentController extends Controller
{
    /**
     * Show the form for assigning a worker to a work place.
     */
    public function assignForm(WorkPlace $workPlace)
    {
        if (Auth::user()->can('manage work place')) {
            if ($workPlace->created_by == Auth::user()->creatorId()) {
                return view('work_place.assign_form', compact('workPlace'));
            } else {
                return response()->json(['error' => __('Permission denied.')], 403);
            }
        } else {
            return response()->json(['error' => __('Permission denied.')], 403);
        }
    }

    /**
     * Assign a worker to a work place.
     */
    public function assignWorker(Request $request, WorkPlace $workPlace)
    {
        if (Auth::user()->can('manage work place')) {
            if ($workPlace->created_by == Auth::user()->creatorId()) {
                $validator = Validator::make(
                    $request->all(),
                    [
                        'worker_id' => 'required|exists:workers,id',
                    ]
                );

                if ($validator->fails()) {
                    return redirect()->back()->with('error', $validator->errors()->first());
                }

                $worker = Worker::find($request->worker_id);

                // Check if worker belongs to the same company
                if ($worker->created_by != Auth::user()->creatorId()) {
                    return redirect()->back()->with('error', __('Permission denied.'));
                }

                // Check if worker already has an active work assignment
                if ($worker->currentWorkAssignment) {
                    return redirect()->back()->with('error', __('Worker is already employed. Dismiss them first.'));
                }

                // Create the assignment
                $assignment = new WorkAssignment();
                $assignment->worker_id = $worker->id;
                $assignment->work_place_id = $workPlace->id;
                $assignment->started_at = now();
                $assignment->created_by = Auth::user()->creatorId();
                $assignment->save();

                return redirect()->back()->with('success', __('Worker successfully assigned to work.'));
            } else {
                return redirect()->back()->with('error', __('Permission denied.'));
            }
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    /**
     * Dismiss a worker from their work place (fire them).
     */
    public function dismissWorker(Request $request, Worker $worker)
    {
        if (Auth::user()->can('manage work place')) {
            if ($worker->created_by == Auth::user()->creatorId()) {
                $assignment = $worker->currentWorkAssignment;

                if (!$assignment) {
                    return redirect()->back()->with('error', __('Worker is not employed.'));
                }

                // Set the end date to dismiss the worker
                $assignment->ended_at = now();
                $assignment->save();

                // Check if redirect to mobile
                if ($request->input('redirect_to') === 'mobile') {
                    return redirect()->route('mobile.workers.show', $worker->id)->with('success', __('Worker successfully dismissed.'));
                }
                return redirect()->back()->with('success', __('Worker successfully dismissed.'));
            } else {
                return redirect()->back()->with('error', __('Permission denied.'));
            }
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    /**
     * Bulk assign workers to a work place.
     */
    public function assignWorkersBulk(Request $request, WorkPlace $workPlace)
    {
        if (Auth::user()->can('manage work place')) {
            if ($workPlace->created_by == Auth::user()->creatorId()) {
                $validator = Validator::make($request->all(), [
                    'position_id' => 'required|exists:positions,id',
                ]);

                if ($validator->fails()) {
                    return redirect()->back()->with('error', __('Select a position'));
                }

                // Verify position belongs to this work place
                $position = Position::where('id', $request->position_id)
                    ->where('work_place_id', $workPlace->id)
                    ->where('created_by', Auth::user()->creatorId())
                    ->first();

                if (!$position) {
                    return redirect()->back()->with('error', __('Position not found'));
                }

                $workerIds = array_filter(explode(',', $request->worker_ids ?? ''));
                $assigned = 0;

                foreach ($workerIds as $workerId) {
                    $worker = Worker::where('id', $workerId)
                        ->where('created_by', Auth::user()->creatorId())
                        ->first();

                    if (!$worker || $worker->currentWorkAssignment) continue;

                    $assignment = new WorkAssignment();
                    $assignment->worker_id = $worker->id;
                    $assignment->work_place_id = $workPlace->id;
                    $assignment->position_id = $position->id;
                    $assignment->started_at = now();
                    $assignment->created_by = Auth::user()->creatorId();
                    $assignment->save();
                    $assigned++;
                }

                return redirect()->back()->with('success', __('Workers assigned: :count', ['count' => $assigned]));
            }
            return redirect()->back()->with('error', __('Permission denied.'));
        }
        return redirect()->back()->with('error', __('Permission denied.'));
    }

    /**
     * Bulk dismiss workers from a work place.
     */
    public function dismissBulk(Request $request, WorkPlace $workPlace)
    {
        if (Auth::user()->can('manage work place')) {
            if ($workPlace->created_by == Auth::user()->creatorId()) {
                $workerIds = array_filter(explode(',', $request->worker_ids ?? ''));
                $dismissed = 0;

                foreach ($workerIds as $workerId) {
                    $worker = Worker::where('id', $workerId)
                        ->where('created_by', Auth::user()->creatorId())
                        ->first();

                    if (!$worker) continue;

                    $assignment = $worker->currentWorkAssignment;
                    if ($assignment && $assignment->work_place_id == $workPlace->id) {
                        $assignment->ended_at = now();
                        $assignment->save();
                        $dismissed++;
                    }
                }

                return redirect()->back()->with('success', __('Workers dismissed: :count', ['count' => $dismissed]));
            }
            return redirect()->back()->with('error', __('Permission denied.'));
        }
        return redirect()->back()->with('error', __('Permission denied.'));
    }

    /**
     * Assign workers to a position (bulk)
     */
    public function assignToPosition(Request $request, Position $position)
    {
        if (!Auth::user()->can('manage work place')) {
            return redirect()->back()->with('error', __('Insufficient permissions'));
        }

        // Multi-tenancy check
        if ($position->created_by !== Auth::user()->creatorId()) {
            return redirect()->back()->with('error', __('Position not found'));
        }

        $workerIds = array_filter(explode(',', $request->worker_ids ?? ''));
        $assigned = 0;

        foreach ($workerIds as $workerId) {
            $worker = Worker::where('id', $workerId)
                ->where('created_by', Auth::user()->creatorId())
                ->first();

            if (!$worker || $worker->currentWorkAssignment) {
                continue;
            }

            WorkAssignment::create([
                'worker_id' => $worker->id,
                'work_place_id' => $position->work_place_id,
                'position_id' => $position->id,
                'started_at' => now(),
                'created_by' => Auth::user()->creatorId(),
            ]);
            $assigned++;
        }

        return redirect()->back()->with('success', __('Workers assigned: :count', ['count' => $assigned]));
    }

    /**
     * Get unassigned workers (AJAX)
     */
    public function getUnassignedWorkers()
    {
        if (!Auth::user()->can('manage work place')) {
            return response()->json(['error' => __('Insufficient permissions')], 403);
        }

        $assignedWorkerIds = WorkAssignment::whereNull('ended_at')
            ->whereHas('worker', function ($q) {
                $q->where('created_by', Auth::user()->creatorId());
            })
            ->pluck('worker_id');

        $workers = Worker::where('created_by', Auth::user()->creatorId())
            ->whereNotIn('id', $assignedWorkerIds)
            ->orderBy('first_name')
            ->get(['id', 'first_name', 'last_name']);

        return response()->json($workers);
    }

    /**
     * Assign a worker to a work place from worker profile.
     */
    public function assignWorkerFromProfile(Request $request, Worker $worker)
    {
        if (Auth::user()->can('manage worker')) {
            if ($worker->created_by == Auth::user()->creatorId()) {
                $validator = Validator::make(
                    $request->all(),
                    [
                        'work_place_id' => 'required|exists:work_places,id',
                        'position_id' => 'required|exists:positions,id',
                    ]
                );

                if ($validator->fails()) {
                    return redirect()->back()->with('error', $validator->errors()->first());
                }

                $workPlace = WorkPlace::find($request->work_place_id);

                // Check if work place belongs to the same company
                if ($workPlace->created_by != Auth::user()->creatorId()) {
                    return redirect()->back()->with('error', __('Permission denied.'));
                }

                // Verify position belongs to this work place
                $position = Position::where('id', $request->position_id)
                    ->where('work_place_id', $workPlace->id)
                    ->where('created_by', Auth::user()->creatorId())
                    ->first();

                if (!$position) {
                    return redirect()->back()->with('error', __('Position not found'));
                }

                // Check if worker already has an active work assignment
                if ($worker->currentWorkAssignment) {
                    return redirect()->back()->with('error', __('Worker is already employed. Dismiss them first.'));
                }

                // Create the assignment
                $assignment = new WorkAssignment();
                $assignment->worker_id = $worker->id;
                $assignment->work_place_id = $workPlace->id;
                $assignment->position_id = $position->id;
                $assignment->started_at = now();
                $assignment->created_by = Auth::user()->creatorId();
                $assignment->save();

                // Check if redirect to mobile
                if ($request->input('redirect_to') === 'mobile') {
                    return redirect()->route('mobile.workers.show', $worker->id)->with('success', __('Worker successfully assigned to work.'));
                }
                return redirect()->route('worker.show', $worker->id)->with('success', __('Worker successfully assigned to work.'));
            } else {
                return redirect()->back()->with('error', __('Permission denied.'));
            }
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }
}
