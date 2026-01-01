<?php

namespace App\Http\Controllers\Infinity;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BranchController extends Controller
{
    /**
     * Display a listing of branches.
     */
    public function index()
    {
        $branches = Branch::where('created_by', Auth::user()->creatorId())
            ->with(['rooms.currentAssignments', 'users'])
            ->get();

        return view('infinity.branches.index', compact('branches'));
    }

    /**
     * Show the form for creating a new branch.
     */
    public function create()
    {
        return view('infinity.branches.create');
    }

    /**
     * Store a newly created branch.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|max:100',
            'address' => 'required|max:255',
            'phone' => 'nullable|max:20',
            'email' => 'nullable|email|max:100',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'working_hours' => 'nullable|max:100',
            'photos' => 'nullable|array|max:10',
            'photos.*' => 'image|mimes:jpeg,png,jpg,webp|max:5120',
        ]);

        $branch = new Branch();
        $branch->name = $validated['name'];
        $branch->address = $validated['address'];
        $branch->phone = $validated['phone'] ?? null;
        $branch->email = $validated['email'] ?? null;
        $branch->latitude = $validated['latitude'] ?? null;
        $branch->longitude = $validated['longitude'] ?? null;
        $branch->working_hours = $validated['working_hours'] ?? null;
        $branch->created_by = Auth::user()->creatorId();
        $branch->responsible_id = Auth::id();

        // Обработка фото
        if ($request->hasFile('photos')) {
            $photos = [];
            foreach ($request->file('photos') as $photo) {
                $path = $photo->store('branches/' . Auth::user()->creatorId(), 'public');
                $photos[] = $path;
            }
            $branch->photos = $photos;
        }

        $branch->save();

        return redirect()->route('infinity.branches.index')
            ->with('success', __('Филиал успешно создан.'));
    }

    /**
     * Display the specified branch with rooms.
     */
    public function show(Branch $branch)
    {
        if ($branch->created_by != Auth::user()->creatorId()) {
            return redirect()->back()->with('error', __('Доступ запрещён.'));
        }

        $branch->load(['rooms.currentAssignments.user', 'users']);
        
        return view('infinity.branches.show', compact('branch'));
    }

    /**
     * Show the form for editing the specified branch.
     */
    public function edit(Branch $branch)
    {
        if ($branch->created_by != Auth::user()->creatorId()) {
            return redirect()->back()->with('error', __('Доступ запрещён.'));
        }

        return view('infinity.branches.edit', compact('branch'));
    }

    /**
     * Update the specified branch.
     */
    public function update(Request $request, Branch $branch)
    {
        if ($branch->created_by != Auth::user()->creatorId()) {
            return redirect()->back()->with('error', __('Доступ запрещён.'));
        }

        $validated = $request->validate([
            'name' => 'required|max:100',
            'address' => 'required|max:255',
            'phone' => 'nullable|max:20',
            'email' => 'nullable|email|max:100',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'working_hours' => 'nullable|max:100',
            'photos' => 'nullable|array|max:10',
            'photos.*' => 'image|mimes:jpeg,png,jpg,webp|max:5120',
            'existing_photos' => 'nullable|array',
        ]);

        $branch->name = $validated['name'];
        $branch->address = $validated['address'];
        $branch->phone = $validated['phone'] ?? null;
        $branch->email = $validated['email'] ?? null;
        $branch->latitude = $validated['latitude'] ?? null;
        $branch->longitude = $validated['longitude'] ?? null;
        $branch->working_hours = $validated['working_hours'] ?? null;

        // Обработка фото
        $photos = $request->input('existing_photos', []);
        
        if ($request->hasFile('photos')) {
            foreach ($request->file('photos') as $photo) {
                if (count($photos) >= 10) break;
                $path = $photo->store('branches/' . Auth::user()->creatorId(), 'public');
                $photos[] = $path;
            }
        }
        
        $branch->photos = $photos;
        $branch->save();

        return redirect()->route('infinity.branches.index')
            ->with('success', __('Филиал успешно обновлён.'));
    }

    /**
     * Remove the specified branch.
     */
    public function destroy(Branch $branch)
    {
        if ($branch->created_by != Auth::user()->creatorId()) {
            return redirect()->back()->with('error', __('Доступ запрещён.'));
        }

        $branch->delete();

        return redirect()->route('infinity.branches.index')
            ->with('success', __('Филиал успешно удалён.'));
    }

    /**
     * Assign user (masseuse) to branch.
     */
    public function assignUser(Request $request, Branch $branch)
    {
        if ($branch->created_by != Auth::user()->creatorId()) {
            return redirect()->back()->with('error', __('Доступ запрещён.'));
        }

        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        $user = User::find($validated['user_id']);
        $user->branch_id = $branch->id;
        $user->save();

        return redirect()->back()->with('success', __('Сотрудник привязан к филиалу.'));
    }

    /**
     * Remove user from branch.
     */
    public function removeUser(Request $request, Branch $branch, User $user)
    {
        if ($branch->created_by != Auth::user()->creatorId()) {
            return redirect()->back()->with('error', __('Доступ запрещён.'));
        }

        if ($user->branch_id == $branch->id) {
            $user->branch_id = null;
            $user->save();
        }

        return redirect()->back()->with('success', __('Сотрудник откреплён от филиала.'));
    }
}
