<?php

namespace App\Http\Controllers;

use App\Models\WorkPlace;
use App\Services\PlanLimitService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class WorkPlaceController extends Controller
{
    /**
     * Display a listing of the work places.
     */
    public function index()
    {
        if (Auth::user()->can('manage work place')) {
            $workPlaces = WorkPlace::where('created_by', '=', Auth::user()->creatorId())
                ->visibleToUser(Auth::user())
                ->with(['currentAssignments.worker', 'positions'])
                ->withCount('positions')
                ->get();

            return view('work_place.index', compact('workPlaces'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    /**
     * Show the form for creating a new work place.
     */
    public function create()
    {
        if (Auth::user()->can('create work place')) {
            return view('work_place.create');
        } else {
            return response()->json(['error' => __('Permission denied.')], 401);
        }
    }

    /**
     * Store a newly created work place in storage.
     */
    public function store(Request $request)
    {
        if (Auth::user()->can('create work place')) {
            // Check plan limit
            if (!PlanLimitService::canCreateWorkplace()) {
                return redirect()->back()->with('error', __('Workplace limit reached for your plan.'));
            }

            $validator = Validator::make(
                $request->all(),
                [
                    'name' => 'required|max:100',
                    'address' => 'required',
                ]
            );

            if ($validator->fails()) {
                $messages = $validator->getMessageBag();
                return redirect()->back()->with('error', $messages->first());
            }

            $workPlace = new WorkPlace();
            $workPlace->name = $request->name;
            $workPlace->address = $request->address;
            $workPlace->phone = $request->phone;
            $workPlace->email = $request->email;
            $workPlace->created_by = Auth::user()->creatorId();
            $workPlace->responsible_id = Auth::id(); // Auto-assign creator as responsible
            $workPlace->save();

            // Handle mobile redirect
            if ($request->has('redirect_to') && $request->redirect_to === 'mobile') {
                return redirect()->route('mobile.workplaces.index')->with('success', __('Work place successfully created.'));
            }

            return redirect()->route('work-place.index')->with('success', __('Work place successfully created.'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    /**
     * Show the form for editing the specified work place.
     */
    public function edit(WorkPlace $workPlace)
    {
        if (Auth::user()->can('edit work place')) {
            if ($workPlace->created_by == Auth::user()->creatorId()) {
                $responsibleService = new \App\Services\ResponsibleService();
                $canAssignResponsible = $responsibleService->canAssignResponsible();
                $assignableUsers = $canAssignResponsible ? $responsibleService->getAssignableUsers() : collect();
                
                return view('work_place.edit', compact('workPlace', 'canAssignResponsible', 'assignableUsers'));
            } else {
                return response()->json(['error' => __('Permission denied.')], 401);
            }
        } else {
            return response()->json(['error' => __('Permission denied.')], 401);
        }
    }

    /**
     * Update the specified work place in storage.
     */
    public function update(Request $request, WorkPlace $workPlace)
    {
        if (Auth::user()->can('edit work place')) {
            if ($workPlace->created_by == Auth::user()->creatorId()) {
                $validator = Validator::make(
                    $request->all(),
                    [
                        'name' => 'required|max:100',
                        'address' => 'required',
                    ]
                );

                if ($validator->fails()) {
                    $messages = $validator->getMessageBag();
                    return redirect()->back()->with('error', $messages->first());
                }

                $workPlace->name = $request->name;
                $workPlace->address = $request->address;
                $workPlace->phone = $request->phone;
                $workPlace->email = $request->email;
                
                // Handle responsible assignment
                if ($request->filled('responsible_id')) {
                    $responsibleService = new \App\Services\ResponsibleService();
                    if ($responsibleService->canAssignResponsible()) {
                        try {
                            $responsibleService->assignResponsible($workPlace, $request->responsible_id);
                        } catch (\Exception $e) {
                            return redirect()->back()->with('error', $e->getMessage());
                        }
                    }
                } else {
                    $workPlace->save();
                }

                // Handle mobile redirect
                if ($request->has('redirect_to') && $request->redirect_to === 'mobile') {
                    return redirect()->route('mobile.workplaces.show', $workPlace->id)->with('success', __('Work place successfully updated.'));
                }

                return redirect()->route('work-place.index')->with('success', __('Work place successfully updated.'));
            } else {
                return redirect()->back()->with('error', __('Permission denied.'));
            }
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    /**
     * Remove the specified work place from storage.
     */
    public function destroy(Request $request, WorkPlace $workPlace)
    {
        if (Auth::user()->can('delete work place')) {
            if ($workPlace->created_by == Auth::user()->creatorId()) {
                $workPlace->delete();

                // Handle mobile redirect
                if ($request->has('redirect_to') && $request->redirect_to === 'mobile') {
                    return redirect()->route('mobile.workplaces.index')->with('success', __('Work place successfully deleted.'));
                }

                return redirect()->route('work-place.index')->with('success', __('Work place successfully deleted.'));
            } else {
                return redirect()->back()->with('error', __('Permission denied.'));
            }
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    /**
     * Show workers assigned to this work place (for modal).
     */
    public function showWorkers(WorkPlace $workPlace)
    {
        if (Auth::user()->can('manage work place')) {
            if ($workPlace->created_by == Auth::user()->creatorId()) {
                $workPlace->load(['currentAssignments.worker', 'responsible']);
                return view('work_place.show', compact('workPlace'));
            } else {
                return response()->json(['error' => __('Permission denied.')], 403);
            }
        } else {
            return response()->json(['error' => __('Permission denied.')], 403);
        }
    }
}
