<?php

namespace App\Http\Controllers;

use App\Models\SystemNotification;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class NotificationController extends Controller
{
    /**
     * Get notifications (API endpoint for polling)
     */
    public function check()
    {
        if (!Auth::check()) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $service = new NotificationService();
        
        // Run checks (will only execute if enough time passed)
        $service->runChecks();

        // Get unread notifications
        $notifications = SystemNotification::forCurrentUser()
            ->unread()
            ->latest()
            ->limit(10)
            ->get();

        $unreadCount = SystemNotification::forCurrentUser()->unread()->count();

        return response()->json([
            'success' => true,
            'count' => $unreadCount,
            'notifications' => $notifications->map(function ($n) {
                return [
                    'id' => $n->id,
                    'type' => $n->type,
                    'title' => $n->title,
                    'message' => $n->translated_message,
                    'link' => $n->link,
                    'icon' => $n->icon,
                    'color' => $n->color,
                    'time' => $n->created_at->diffForHumans(),
                ];
            }),
            'poll_interval' => $service->getPollInterval(),
        ]);
    }

    /**
     * Get all notifications (paginated)
     */
    public function index()
    {
        if (!Auth::user()->can('manage worker')) {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        $notifications = SystemNotification::forCurrentUser()
            ->latest()
            ->paginate(20);

        return view('notifications.index', compact('notifications'));
    }

    /**
     * Mark notification as read
     */
    public function markAsRead($id)
    {
        $notification = SystemNotification::forCurrentUser()->findOrFail($id);
        $notification->markAsRead();

        if (request()->ajax()) {
            return response()->json(['success' => true]);
        }

        return redirect()->back()->with('success', __('Notification marked as read.'));
    }

    /**
     * Mark all notifications as read
     */
    public function markAllAsRead()
    {
        SystemNotification::forCurrentUser()
            ->unread()
            ->update(['is_read' => true]);

        if (request()->ajax()) {
            return response()->json(['success' => true]);
        }

        return redirect()->back()->with('success', __('All notifications marked as read.'));
    }

    /**
     * Delete notification
     */
    public function destroy($id)
    {
        $notification = SystemNotification::forCurrentUser()->findOrFail($id);
        $notification->delete();

        if (request()->ajax()) {
            return response()->json(['success' => true]);
        }

        return redirect()->back()->with('success', __('Notification deleted.'));
    }

    /**
     * Clear all notifications
     */
    public function clearAll()
    {
        SystemNotification::forCurrentUser()->delete();

        if (request()->ajax()) {
            return response()->json(['success' => true]);
        }

        return redirect()->back()->with('success', __('All notifications cleared.'));
    }

    /**
     * Save notification settings (super admin only)
     */
    public function saveSettings(Request $request)
    {
        if (Auth::user()->type !== 'super admin') {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        $request->validate([
            'notifications_enabled' => 'nullable|string',
            'notification_poll_interval' => 'required|integer|min:1|max:60',
            'notification_create_interval' => 'required|integer|min:1|max:1440',
            'notification_hotel_occupancy_threshold' => 'required|integer|min:1|max:100',
            'notification_unemployed_days' => 'required|integer|min:1|max:30',
        ]);

        $settings = [
            'notifications_enabled' => $request->has('notifications_enabled') ? 'on' : 'off',
            'notification_poll_interval' => $request->notification_poll_interval,
            'notification_create_interval' => $request->notification_create_interval,
            'notification_hotel_occupancy_threshold' => $request->notification_hotel_occupancy_threshold,
            'notification_unemployed_days' => $request->notification_unemployed_days,
        ];

        foreach ($settings as $name => $value) {
            DB::table('settings')->updateOrInsert(
                ['name' => $name, 'created_by' => 1],
                ['value' => $value]
            );
        }

        // Clear notification cache so new settings take effect immediately
        \Illuminate\Support\Facades\Cache::flush();

        return redirect()->back()->with('success', __('Notification settings saved.'));
    }
}
