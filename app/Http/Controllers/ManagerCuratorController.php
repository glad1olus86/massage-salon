<?php

namespace App\Http\Controllers;

use App\Services\ManagerCuratorService;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ManagerCuratorController extends Controller
{
    protected ManagerCuratorService $service;

    public function __construct(ManagerCuratorService $service)
    {
        $this->service = $service;
    }

    /**
     * Display curators assigned to a manager.
     */
    public function index(User $manager)
    {
        if (!Auth::user()->isDirector()) {
            return response()->json(['error' => __('Permission denied.')], 403);
        }

        // Verify manager belongs to same company
        if ($manager->created_by != Auth::user()->creatorId()) {
            return response()->json(['error' => __('Permission denied.')], 403);
        }

        $curators = $this->service->getCuratorsForManager($manager->id);

        if (request()->wantsJson()) {
            return response()->json([
                'manager' => [
                    'id' => $manager->id,
                    'name' => $manager->name,
                ],
                'curators' => $curators->map(fn($c) => [
                    'id' => $c->id,
                    'name' => $c->name,
                    'email' => $c->email,
                ]),
            ]);
        }

        // Get available curators for assignment dropdown
        $allCurators = $this->service->getAllCurators();
        $assignedCuratorIds = $curators->pluck('id');
        $availableCurators = $allCurators->filter(fn($c) => !$assignedCuratorIds->contains($c->id));

        return view('user.manager_curators', compact('manager', 'curators', 'availableCurators'));
    }

    /**
     * Assign a curator to a manager.
     */
    public function store(Request $request, User $manager)
    {
        if (!Auth::user()->isDirector()) {
            if ($request->wantsJson()) {
                return response()->json(['error' => __('Permission denied.')], 403);
            }
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        $request->validate([
            'curator_id' => 'required|exists:users,id',
        ]);

        try {
            $this->service->assignCuratorToManager($request->curator_id, $manager->id);

            if ($request->wantsJson()) {
                return response()->json(['success' => true, 'message' => __('Curator assigned successfully.')]);
            }

            return redirect()->back()->with('success', __('Curator assigned successfully.'));
        } catch (\Exception $e) {
            if ($request->wantsJson()) {
                return response()->json(['error' => $e->getMessage()], 400);
            }
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Remove a curator from a manager.
     */
    public function destroy(Request $request, User $manager, User $curator)
    {
        if (!Auth::user()->isDirector()) {
            if ($request->wantsJson()) {
                return response()->json(['error' => __('Permission denied.')], 403);
            }
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        try {
            $this->service->removeCuratorFromManager($curator->id, $manager->id);

            if ($request->wantsJson()) {
                return response()->json(['success' => true, 'message' => __('Curator removed successfully.')]);
            }

            return redirect()->back()->with('success', __('Curator removed successfully.'));
        } catch (\Exception $e) {
            if ($request->wantsJson()) {
                return response()->json(['error' => $e->getMessage()], 400);
            }
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Get available curators for assignment (not yet assigned to this manager).
     */
    public function available(User $manager)
    {
        if (!Auth::user()->isDirector()) {
            return response()->json(['error' => __('Permission denied.')], 403);
        }

        $allCurators = $this->service->getAllCurators();
        $assignedCuratorIds = $this->service->getCuratorsForManager($manager->id)->pluck('id');

        $availableCurators = $allCurators->filter(fn($c) => !$assignedCuratorIds->contains($c->id));

        return response()->json([
            'curators' => $availableCurators->map(fn($c) => [
                'id' => $c->id,
                'name' => $c->name,
                'email' => $c->email,
            ])->values(),
        ]);
    }
}
