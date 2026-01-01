<?php

namespace App\Http\Controllers\Infinity;

use App\Http\Controllers\Controller;
use App\Models\MassageClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class MassageClientController extends Controller
{
    /**
     * Display a listing of clients.
     */
    public function index(Request $request)
    {
        $query = MassageClient::where('created_by', Auth::user()->creatorId());
        
        // Search
        if ($request->filled('search')) {
            $query->search($request->search);
        }
        
        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        
        $clients = $query->orderBy('created_at', 'desc')->paginate(20);
        $statuses = MassageClient::getStatuses();

        return view('infinity.clients.index', compact('clients', 'statuses'));
    }

    /**
     * Show the form for creating a new client.
     */
    public function create()
    {
        $statuses = MassageClient::getStatuses();
        return view('infinity.clients.create', compact('statuses'));
    }

    /**
     * Store a newly created client.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'first_name' => 'required|max:100',
            'last_name' => 'required|max:100',
            'phone' => 'nullable|max:20',
            'email' => 'nullable|email|max:100',
            'dob' => 'nullable|date',
            'gender' => 'nullable|in:male,female',
            'nationality' => 'nullable|max:100',
            'preferred_service' => 'nullable|max:255',
            'notes' => 'nullable',
            'source' => 'nullable|max:100',
            'status' => 'nullable|in:active,vip,blocked',
            'photo' => 'nullable|image|max:2048',
        ]);

        $client = new MassageClient();
        $client->first_name = $validated['first_name'];
        $client->last_name = $validated['last_name'];
        $client->phone = $validated['phone'] ?? null;
        $client->email = $validated['email'] ?? null;
        $client->dob = $validated['dob'] ?? null;
        $client->gender = $validated['gender'] ?? null;
        $client->nationality = $validated['nationality'] ?? null;
        $client->preferred_service = $validated['preferred_service'] ?? null;
        $client->notes = $validated['notes'] ?? null;
        $client->source = $validated['source'] ?? null;
        $client->status = $validated['status'] ?? 'active';
        $client->registration_date = now();
        $client->created_by = Auth::user()->creatorId();
        $client->responsible_id = Auth::id();

        // Handle photo upload
        if ($request->hasFile('photo')) {
            $path = $request->file('photo')->store('massage_clients', 'public');
            $client->photo = $path;
        }

        $client->save();

        return redirect()->route('infinity.clients.index')
            ->with('success', __('Клиент успешно создан.'));
    }

    /**
     * Display the specified client.
     */
    public function show(MassageClient $client)
    {
        if ($client->created_by != Auth::user()->creatorId()) {
            return redirect()->back()->with('error', __('Доступ запрещён.'));
        }

        // История заказов клиента
        $orders = $client->orders()
            ->orderBy('order_date', 'desc')
            ->orderBy('order_time', 'desc')
            ->with(['service', 'employee'])
            ->get();
        
        // Статистика
        $stats = [
            'total_orders' => $orders->count(),
            'total_spent' => $orders->sum('amount'),
            'total_tips' => $orders->sum('tip'),
            'avg_order' => $orders->count() > 0 ? $orders->avg('amount') : 0,
        ];
        
        // Популярные услуги клиента (топ 3)
        $favoriteServices = $orders->groupBy('service_id')
            ->map(fn($group) => [
                'service' => $group->first()->service,
                'service_name' => $group->first()->service_display_name,
                'count' => $group->count(),
                'total' => $group->sum('amount'),
            ])
            ->sortByDesc('count')
            ->take(3)
            ->values();
        
        return view('infinity.clients.show', compact('client', 'orders', 'stats', 'favoriteServices'));
    }

    /**
     * Show the form for editing the specified client.
     */
    public function edit(MassageClient $client)
    {
        if ($client->created_by != Auth::user()->creatorId()) {
            return redirect()->back()->with('error', __('Доступ запрещён.'));
        }

        $statuses = MassageClient::getStatuses();
        return view('infinity.clients.edit', compact('client', 'statuses'));
    }

    /**
     * Update the specified client.
     */
    public function update(Request $request, MassageClient $client)
    {
        if ($client->created_by != Auth::user()->creatorId()) {
            return redirect()->back()->with('error', __('Доступ запрещён.'));
        }

        $validated = $request->validate([
            'first_name' => 'required|max:100',
            'last_name' => 'required|max:100',
            'phone' => 'nullable|max:20',
            'email' => 'nullable|email|max:100',
            'dob' => 'nullable|date',
            'gender' => 'nullable|in:male,female',
            'nationality' => 'nullable|max:100',
            'preferred_service' => 'nullable|max:255',
            'notes' => 'nullable',
            'source' => 'nullable|max:100',
            'status' => 'nullable|in:active,vip,blocked',
            'photo' => 'nullable|image|max:2048',
        ]);

        $client->first_name = $validated['first_name'];
        $client->last_name = $validated['last_name'];
        $client->phone = $validated['phone'] ?? null;
        $client->email = $validated['email'] ?? null;
        $client->dob = $validated['dob'] ?? null;
        $client->gender = $validated['gender'] ?? null;
        $client->nationality = $validated['nationality'] ?? null;
        $client->preferred_service = $validated['preferred_service'] ?? null;
        $client->notes = $validated['notes'] ?? null;
        $client->source = $validated['source'] ?? null;
        $client->status = $validated['status'] ?? 'active';

        // Handle photo upload
        if ($request->hasFile('photo')) {
            // Delete old photo
            if ($client->photo) {
                Storage::disk('public')->delete($client->photo);
            }
            $path = $request->file('photo')->store('massage_clients', 'public');
            $client->photo = $path;
        }

        $client->save();

        return redirect()->route('infinity.clients.index')
            ->with('success', __('Клиент успешно обновлён.'));
    }

    /**
     * Remove the specified client.
     */
    public function destroy(MassageClient $client)
    {
        if ($client->created_by != Auth::user()->creatorId()) {
            return redirect()->back()->with('error', __('Доступ запрещён.'));
        }

        // Delete photo if exists
        if ($client->photo) {
            Storage::disk('public')->delete($client->photo);
        }

        $client->delete();

        return redirect()->route('infinity.clients.index')
            ->with('success', __('Клиент успешно удалён.'));
    }

    /**
     * Search clients (AJAX).
     */
    public function search(Request $request)
    {
        $search = $request->get('q', '');
        
        $clients = MassageClient::where('created_by', Auth::user()->creatorId())
            ->search($search)
            ->limit(10)
            ->get(['id', 'first_name', 'last_name', 'phone']);
            
        return response()->json($clients->map(function ($client) {
            return [
                'id' => $client->id,
                'text' => $client->full_name . ($client->phone ? " ({$client->phone})" : ''),
            ];
        }));
    }
}
