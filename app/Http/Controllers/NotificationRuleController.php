<?php

namespace App\Http\Controllers;

use App\Models\NotificationRule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class NotificationRuleController extends Controller
{
    /**
     * Display notification rules in settings
     */
    public function index()
    {
        if (!Auth::user()->can('manage company settings')) {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        $rules = NotificationRule::forCurrentUser()->orderBy('created_at', 'desc')->get();
        $entityTypes = NotificationRule::getEntityTypes();
        $severityLevels = NotificationRule::getSeverityLevels();

        return view('notification_rules.index', compact('rules', 'entityTypes', 'severityLevels'));
    }

    /**
     * Show create form
     */
    public function create()
    {
        if (!Auth::user()->can('manage company settings')) {
            return response()->json(['error' => __('Permission denied.')], 403);
        }

        $entityTypes = NotificationRule::getEntityTypes();
        $severityLevels = NotificationRule::getSeverityLevels();

        return view('notification_rules.create', compact('entityTypes', 'severityLevels'));
    }

    /**
     * Store new rule
     */
    public function store(Request $request)
    {
        if (!Auth::user()->can('manage company settings')) {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'entity_type' => 'required|in:worker,room,hotel,work_place,cashbox',
            'conditions' => 'required|array|min:1',
            'period_from' => 'required|integer|min:0',
            'period_to' => 'nullable|integer|min:0',
            'severity' => 'required|in:info,warning,danger',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->with('error', $validator->errors()->first());
        }

        // Validate period_to > period_from if set (skip for event-based entities like cashbox)
        if ($request->entity_type !== 'cashbox' && $request->period_to && $request->period_to <= $request->period_from) {
            return redirect()->back()->with('error', __('Period "to" must be greater than period "from"'));
        }

        NotificationRule::create([
            'name' => $request->name,
            'entity_type' => $request->entity_type,
            'conditions' => $request->conditions,
            'period_from' => $request->period_from,
            'period_to' => $request->period_to ?: null,
            'severity' => $request->severity,
            'is_active' => true,
            'is_grouped' => $request->has('is_grouped'),
            'created_by' => Auth::user()->creatorId(),
        ]);

        return redirect()->route('notification-rules.index')->with('success', __('Notification rule created'));
    }

    /**
     * Show edit form
     */
    public function edit(NotificationRule $notificationRule)
    {
        if (!Auth::user()->can('manage company settings')) {
            return response()->json(['error' => __('Permission denied.')], 403);
        }

        if ($notificationRule->created_by != Auth::user()->creatorId()) {
            return response()->json(['error' => __('Permission denied.')], 403);
        }

        $entityTypes = NotificationRule::getEntityTypes();
        $severityLevels = NotificationRule::getSeverityLevels();

        return view('notification_rules.edit', compact('notificationRule', 'entityTypes', 'severityLevels'));
    }

    /**
     * Update rule
     */
    public function update(Request $request, NotificationRule $notificationRule)
    {
        if (!Auth::user()->can('manage company settings')) {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        if ($notificationRule->created_by != Auth::user()->creatorId()) {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'entity_type' => 'required|in:worker,room,hotel,work_place,cashbox',
            'conditions' => 'required|array|min:1',
            'period_from' => 'required|integer|min:0',
            'period_to' => 'nullable|integer|min:0',
            'severity' => 'required|in:info,warning,danger',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->with('error', $validator->errors()->first());
        }

        // Validate period_to > period_from if set (skip for event-based entities like cashbox)
        if ($request->entity_type !== 'cashbox' && $request->period_to && $request->period_to <= $request->period_from) {
            return redirect()->back()->with('error', __('Period "to" must be greater than period "from"'));
        }

        $notificationRule->update([
            'name' => $request->name,
            'entity_type' => $request->entity_type,
            'conditions' => $request->conditions,
            'period_from' => $request->period_from,
            'period_to' => $request->period_to ?: null,
            'severity' => $request->severity,
            'is_grouped' => $request->has('is_grouped'),
        ]);

        return redirect()->route('notification-rules.index')->with('success', __('Notification rule updated'));
    }

    /**
     * Toggle rule active status
     */
    public function toggle(NotificationRule $notificationRule)
    {
        if (!Auth::user()->can('manage company settings')) {
            return response()->json(['error' => __('Permission denied.')], 403);
        }

        if ($notificationRule->created_by != Auth::user()->creatorId()) {
            return response()->json(['error' => __('Permission denied.')], 403);
        }

        $notificationRule->update(['is_active' => !$notificationRule->is_active]);

        return redirect()->back()->with('success', 
            $notificationRule->is_active ? __('Rule enabled') : __('Rule disabled')
        );
    }

    /**
     * Delete rule
     */
    public function destroy(NotificationRule $notificationRule)
    {
        if (!Auth::user()->can('manage company settings')) {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        if ($notificationRule->created_by != Auth::user()->creatorId()) {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        $notificationRule->delete();

        return redirect()->route('notification-rules.index')->with('success', __('Rule deleted'));
    }

    /**
     * Get conditions for entity type (AJAX)
     */
    public function getConditions(Request $request)
    {
        $entityType = $request->get('entity_type');
        $conditions = NotificationRule::getConditionsForEntity($entityType);
        
        return response()->json($conditions);
    }

    /**
     * Get template variables for entity type (AJAX)
     * Requirement 12.3: Template variables for notifications
     */
    public function getTemplateVariables(Request $request)
    {
        $entityType = $request->get('entity_type');
        $variables = NotificationRule::getTemplateVariablesForEntity($entityType);
        
        return response()->json($variables);
    }
}
