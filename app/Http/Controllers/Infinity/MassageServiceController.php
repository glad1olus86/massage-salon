<?php

namespace App\Http\Controllers\Infinity;

use App\Http\Controllers\Controller;
use App\Models\MassageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MassageServiceController extends Controller
{
    /**
     * Display a listing of services.
     */
    public function index()
    {
        $regularServices = MassageService::where('created_by', Auth::user()->creatorId())
            ->where('is_extra', false)
            ->ordered()
            ->get();
            
        $extraServices = MassageService::where('created_by', Auth::user()->creatorId())
            ->where('is_extra', true)
            ->ordered()
            ->get();

        return view('infinity.services.index', compact('regularServices', 'extraServices'));
    }

    /**
     * Show the form for creating a new service.
     */
    public function create()
    {
        return view('infinity.services.create');
    }

    /**
     * Store a newly created service.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|max:255',
            'description' => 'nullable',
            'price' => 'required|numeric|min:0',
            'operator_share_60' => 'nullable|numeric|min:0',
            'operator_share_90' => 'nullable|numeric|min:0',
            'operator_share_120' => 'nullable|numeric|min:0',
            'duration' => 'nullable|integer|min:1',
            'is_active' => 'boolean',
            'is_extra' => 'boolean',
            'sort_order' => 'nullable|integer',
        ]);

        $service = new MassageService();
        $service->name = $validated['name'];
        $service->description = $validated['description'] ?? null;
        $service->price = $validated['price'];
        $service->operator_share_60 = $validated['operator_share_60'] ?? null;
        $service->operator_share_90 = $validated['operator_share_90'] ?? null;
        $service->operator_share_120 = $validated['operator_share_120'] ?? null;
        $service->duration = $validated['duration'] ?? null;
        $service->is_active = $request->boolean('is_active', true);
        $service->is_extra = $request->boolean('is_extra', false);
        $service->sort_order = $validated['sort_order'] ?? 0;
        $service->created_by = Auth::user()->creatorId();
        $service->save();

        return redirect()->route('infinity.services.index')
            ->with('success', __('Услуга успешно создана.'));
    }

    /**
     * Show the form for editing the specified service.
     */
    public function edit(MassageService $service)
    {
        if ($service->created_by != Auth::user()->creatorId()) {
            return redirect()->back()->with('error', __('Доступ запрещён.'));
        }

        return view('infinity.services.edit', compact('service'));
    }

    /**
     * Update the specified service.
     */
    public function update(Request $request, MassageService $service)
    {
        if ($service->created_by != Auth::user()->creatorId()) {
            return redirect()->back()->with('error', __('Доступ запрещён.'));
        }

        $validated = $request->validate([
            'name' => 'required|max:255',
            'description' => 'nullable',
            'price' => 'required|numeric|min:0',
            'operator_share_60' => 'nullable|numeric|min:0',
            'operator_share_90' => 'nullable|numeric|min:0',
            'operator_share_120' => 'nullable|numeric|min:0',
            'duration' => 'nullable|integer|min:1',
            'is_active' => 'boolean',
            'is_extra' => 'boolean',
            'sort_order' => 'nullable|integer',
        ]);

        $service->name = $validated['name'];
        $service->description = $validated['description'] ?? null;
        $service->price = $validated['price'];
        $service->operator_share_60 = $validated['operator_share_60'] ?? null;
        $service->operator_share_90 = $validated['operator_share_90'] ?? null;
        $service->operator_share_120 = $validated['operator_share_120'] ?? null;
        $service->duration = $validated['duration'] ?? null;
        $service->is_active = $request->boolean('is_active', true);
        $service->is_extra = $request->boolean('is_extra', false);
        $service->sort_order = $validated['sort_order'] ?? 0;
        $service->save();

        return redirect()->route('infinity.services.index')
            ->with('success', __('Услуга успешно обновлена.'));
    }

    /**
     * Remove the specified service.
     */
    public function destroy(MassageService $service)
    {
        if ($service->created_by != Auth::user()->creatorId()) {
            return redirect()->back()->with('error', __('Доступ запрещён.'));
        }

        $service->delete();

        return redirect()->route('infinity.services.index')
            ->with('success', __('Услуга успешно удалена.'));
    }

    /**
     * Get services list for AJAX (for assigning to users).
     */
    public function list()
    {
        $services = MassageService::where('created_by', Auth::user()->creatorId())
            ->active()
            ->ordered()
            ->get(['id', 'name', 'price']);
            
        return response()->json($services);
    }
}
