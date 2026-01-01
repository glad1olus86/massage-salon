<?php

namespace App\Http\Controllers\Masseuse;

use App\Http\Controllers\Controller;
use App\Models\MassageClient;
use App\Models\MassageOrder;
use App\Models\MassageService;
use Illuminate\Http\Request;

class ClientController extends Controller
{
    /**
     * Display a listing of the masseuse's clients.
     */
    public function index(Request $request)
    {
        // Показываем клиентов, созданных этой массажисткой
        // (по responsible_id или created_by для обратной совместимости)
        $query = MassageClient::where(function ($q) {
            $q->where('responsible_id', auth()->id())
              ->orWhere('created_by', auth()->id());
        });

        if ($search = $request->get('search')) {
            $query->search($search);
        }

        $clients = $query->orderBy('first_name')->paginate(20);

        return view('masseuse.clients.index', compact('clients'));
    }

    /**
     * Show the form for creating a new client.
     */
    public function create()
    {
        $services = MassageService::where('is_active', true)->get();
        return view('masseuse.clients.create', compact('services'));
    }

    /**
     * Store a newly created client.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'dob' => 'nullable|date',
            'nationality' => 'nullable|string|max:100',
            'preferred_service' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        // created_by = ID компании (для видимости в админке)
        // responsible_id = ID массажистки (кто создал)
        $validated['created_by'] = auth()->user()->creatorId();
        $validated['responsible_id'] = auth()->id();
        $validated['registration_date'] = now();
        $validated['status'] = MassageClient::STATUS_ACTIVE;

        MassageClient::create($validated);

        return redirect()->route('masseuse.clients.index')
            ->with('success', __('Клиент успешно добавлен.'));
    }

    /**
     * Display the specified client.
     */
    public function show(MassageClient $client)
    {
        $this->authorize('view', $client);
        
        $user = auth()->user();
        
        // История заказов этого клиента от текущей массажистки
        $orders = MassageOrder::where('client_id', $client->id)
            ->where('employee_id', $user->id)
            ->orderBy('order_date', 'desc')
            ->orderBy('order_time', 'desc')
            ->with('service')
            ->get();
        
        // Статистика
        $stats = [
            'total_orders' => $orders->count(),
            'total_spent' => $orders->sum('amount'),
            'total_tips' => $orders->sum('tip'),
            'avg_order' => $orders->count() > 0 ? $orders->avg('amount') : 0,
            'first_visit' => $orders->last()?->order_date,
            'last_visit' => $orders->first()?->order_date,
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
        
        return view('masseuse.clients.show', compact('client', 'orders', 'stats', 'favoriteServices'));
    }

    /**
     * Show the form for editing the specified client.
     */
    public function edit(MassageClient $client)
    {
        $this->authorize('update', $client);
        $services = MassageService::where('is_active', true)->get();
        return view('masseuse.clients.edit', compact('client', 'services'));
    }

    /**
     * Update the specified client.
     */
    public function update(Request $request, MassageClient $client)
    {
        $this->authorize('update', $client);

        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'dob' => 'nullable|date',
            'nationality' => 'nullable|string|max:100',
            'preferred_service' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        // Не позволяем менять created_by
        unset($validated['created_by']);

        $client->update($validated);

        return redirect()->route('masseuse.clients.index')
            ->with('success', __('Клиент успешно обновлён.'));
    }

    /**
     * Remove the specified client.
     */
    public function destroy(MassageClient $client)
    {
        $this->authorize('delete', $client);

        $client->delete();

        return redirect()->route('masseuse.clients.index')
            ->with('success', __('Клиент удалён.'));
    }
}
