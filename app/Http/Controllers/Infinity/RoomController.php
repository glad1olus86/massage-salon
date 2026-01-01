<?php

namespace App\Http\Controllers\Infinity;

use App\Http\Controllers\Controller;
use App\Models\Room;
use App\Models\Branch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class RoomController extends Controller
{
    /**
     * Store a newly created room.
     */
    public function store(Request $request, Branch $branch)
    {
        if ($branch->created_by != Auth::user()->creatorId()) {
            return redirect()->back()->with('error', __('Доступ запрещён.'));
        }

        $validated = $request->validate([
            'room_number' => 'required|string|max:20',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
        ]);

        $room = new Room();
        $room->branch_id = $branch->id;
        $room->hotel_id = null; // Legacy field - set to null
        $room->room_number = $validated['room_number'];
        $room->created_by = Auth::user()->creatorId();

        if ($request->hasFile('photo')) {
            $path = $request->file('photo')->store('rooms', 'public');
            $room->photo = $path;
        }

        $room->save();

        return redirect()->route('infinity.branches.show', $branch)
            ->with('success', __('Комната успешно создана.'));
    }

    /**
     * Update the specified room.
     */
    public function update(Request $request, Branch $branch, Room $room)
    {
        if ($branch->created_by != Auth::user()->creatorId()) {
            return redirect()->back()->with('error', __('Доступ запрещён.'));
        }

        $validated = $request->validate([
            'room_number' => 'required|string|max:20',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
        ]);

        $room->room_number = $validated['room_number'];

        if ($request->hasFile('photo')) {
            // Delete old photo if exists
            if ($room->photo && Storage::disk('public')->exists($room->photo)) {
                Storage::disk('public')->delete($room->photo);
            }
            $path = $request->file('photo')->store('rooms', 'public');
            $room->photo = $path;
        }

        $room->save();

        return redirect()->route('infinity.branches.show', $branch)
            ->with('success', __('Комната успешно обновлена.'));
    }

    /**
     * Remove the specified room.
     */
    public function destroy(Branch $branch, Room $room)
    {
        if ($branch->created_by != Auth::user()->creatorId()) {
            return redirect()->back()->with('error', __('Доступ запрещён.'));
        }

        // Delete photo if exists
        if ($room->photo && Storage::disk('public')->exists($room->photo)) {
            Storage::disk('public')->delete($room->photo);
        }

        $room->delete();

        return redirect()->route('infinity.branches.show', $branch)
            ->with('success', __('Комната успешно удалена.'));
    }
}
