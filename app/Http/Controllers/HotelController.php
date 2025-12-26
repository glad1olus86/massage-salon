<?php

namespace App\Http\Controllers;

use App\Models\Hotel;
use App\Services\PlanLimitService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class HotelController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        if (Auth::user()->can('manage hotel')) {
            $hotels = Hotel::where('created_by', '=', Auth::user()->creatorId())
                ->visibleToUser(Auth::user())
                ->with(['rooms.currentAssignments'])
                ->get();

            return view('hotel.index', compact('hotels'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        if (Auth::user()->can('create hotel')) {
            return view('hotel.create');
        } else {
            return response()->json(['error' => __('Permission denied.')], 401);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        if (Auth::user()->can('create hotel')) {
            // Check plan limit
            if (!PlanLimitService::canCreateHotel()) {
                return redirect()->back()->with('error', __('Hotel limit reached for your plan.'));
            }

            $validator = \Validator::make(
                $request->all(),
                [
                    'name' => 'required|max:30',
                    'address' => 'required',
                ]
            );
            if ($validator->fails()) {
                $messages = $validator->getMessageBag();

                return redirect()->back()->with('error', $messages->first());
            }

            $hotel             = new Hotel();
            $hotel->name       = $request->name;
            $hotel->address    = $request->address;
            $hotel->phone      = $request->phone;
            $hotel->email      = $request->email;
            $hotel->created_by = Auth::user()->creatorId();
            $hotel->responsible_id = Auth::id(); // Auto-assign creator as responsible
            $hotel->save();

            if ($request->input('redirect_to') === 'mobile') {
                return redirect()->route('mobile.hotels.index')->with('success', __('Hotel successfully created.'));
            }

            return redirect()->route('hotel.index')->with('success', __('Hotel successfully created.'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    /**
     * Display rooms for a specific hotel.
     */
    public function showRooms(Hotel $hotel)
    {
        if (Auth::user()->can('manage hotel')) {
            if ($hotel->created_by == Auth::user()->creatorId()) {
                $hotel->load('responsible');
                $rooms = $hotel->rooms()->with('currentAssignments')->get();
                $hotels = Hotel::where('created_by', Auth::user()->creatorId())->get()->pluck('name', 'id');

                return view('hotel.rooms', compact('hotel', 'rooms', 'hotels'));
            } else {
                return redirect()->back()->with('error', __('Permission denied.'));
            }
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Hotel $hotel)
    {
        if (Auth::user()->can('edit hotel')) {
            if ($hotel->created_by == Auth::user()->creatorId()) {
                $responsibleService = new \App\Services\ResponsibleService();
                $canAssignResponsible = $responsibleService->canAssignResponsible();
                $assignableUsers = $canAssignResponsible ? $responsibleService->getAssignableUsers() : collect();
                
                return view('hotel.edit', compact('hotel', 'canAssignResponsible', 'assignableUsers'));
            } else {
                return response()->json(['error' => __('Permission denied.')], 401);
            }
        } else {
            return response()->json(['error' => __('Permission denied.')], 401);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Hotel $hotel)
    {
        if (Auth::user()->can('edit hotel')) {
            if ($hotel->created_by == Auth::user()->creatorId()) {
                $validator = \Validator::make(
                    $request->all(),
                    [
                        'name' => 'required|max:30',
                        'address' => 'required',
                        'phone' => 'required|max:20',
                        'email' => 'required|email|max:100',
                    ]
                );
                if ($validator->fails()) {
                    $messages = $validator->getMessageBag();

                    return redirect()->back()->with('error', $messages->first());
                }

                $hotel->name    = $request->name;
                $hotel->address = $request->address;
                $hotel->phone   = $request->phone;
                $hotel->email   = $request->email;
                
                // Handle responsible assignment
                if ($request->filled('responsible_id')) {
                    $responsibleService = new \App\Services\ResponsibleService();
                    if ($responsibleService->canAssignResponsible()) {
                        try {
                            $responsibleService->assignResponsible($hotel, $request->responsible_id);
                        } catch (\Exception $e) {
                            return redirect()->back()->with('error', $e->getMessage());
                        }
                    }
                } else {
                    $hotel->save();
                }

                if ($request->input('redirect_to') === 'mobile') {
                    return redirect()->route('mobile.hotels.index')->with('success', __('Hotel successfully updated.'));
                }

                return redirect()->route('hotel.index')->with('success', __('Hotel successfully updated.'));
            } else {
                return redirect()->back()->with('error', __('Permission denied.'));
            }
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, Hotel $hotel)
    {
        if (Auth::user()->can('delete hotel')) {
            if ($hotel->created_by == Auth::user()->creatorId()) {
                $hotel->delete();

                if ($request->input('redirect_to') === 'mobile') {
                    return redirect()->route('mobile.hotels.index')->with('success', __('Hotel successfully deleted.'));
                }

                return redirect()->route('hotel.index')->with('success', __('Hotel successfully deleted.'));
            } else {
                return redirect()->back()->with('error', __('Permission denied.'));
            }
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }
}
