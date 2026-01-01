<?php

namespace App\Http\Controllers\Masseuse;

use App\Http\Controllers\Controller;
use App\Models\MassageOrder;
use App\Models\MassageClient;
use App\Models\MassageService;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        
        $query = MassageOrder::with(['client', 'service'])
            ->where('employee_id', $user->id);

        // Фильтр по периоду
        $period = $request->get('period', 'week');
        $now = now();
        
        switch ($period) {
            case 'day':
                $query->whereDate('order_date', $now->toDateString());
                break;
            case 'week':
                $query->whereBetween('order_date', [$now->copy()->startOfWeek(), $now->copy()->endOfWeek()]);
                break;
            case 'month':
                $query->whereMonth('order_date', $now->month)->whereYear('order_date', $now->year);
                break;
            case 'all':
                break;
        }

        $orders = $query->orderBy('order_date', 'desc')
                       ->orderBy('order_time', 'desc')
                       ->paginate(20);

        return view('masseuse.orders.index', compact('orders', 'period'));
    }

    public function create()
    {
        $user = auth()->user();
        
        $clients = MassageClient::where('created_by', $user->creatorId())
            ->orderBy('first_name')
            ->get();
        
        // Услуги которые предоставляет эта массажистка
        $services = $user->massageServices()->where('is_active', true)->get();
        
        $statuses = MassageOrder::getStatuses();
        $paymentMethods = MassageOrder::getPaymentMethods();

        return view('masseuse.orders.create', compact('clients', 'services', 'statuses', 'paymentMethods'));
    }

    public function store(Request $request)
    {
        $user = auth()->user();
        
        $validated = $request->validate([
            'client_id' => 'nullable|exists:massage_clients,id',
            'client_name' => 'nullable|string|max:255',
            'service_id' => 'nullable|exists:massage_services,id',
            'service_name' => 'nullable|string|max:255',
            'order_date' => 'required|date',
            'order_time' => 'nullable',
            'amount' => 'required|numeric|min:0',
            'tip' => 'nullable|numeric|min:0',
            'payment_method' => 'nullable|in:cash,card,transfer',
            'status' => 'required|in:pending,confirmed,completed,cancelled',
            'notes' => 'nullable|string',
        ]);
        
        // Очищаем пустые значения
        if (empty($validated['order_time'])) {
            $validated['order_time'] = null;
        }

        $validated['employee_id'] = $user->id;
        $validated['branch_id'] = $user->branch_id;
        $validated['created_by'] = $user->creatorId();

        MassageOrder::create($validated);

        return redirect()->route('masseuse.orders.index')
            ->with('success', __('Заказ успешно создан'));
    }

    public function edit(MassageOrder $order)
    {
        $user = auth()->user();
        
        // Проверяем что это заказ этой массажистки
        if ($order->employee_id !== $user->id) {
            abort(403);
        }
        
        $clients = MassageClient::where('created_by', $user->creatorId())
            ->orderBy('first_name')
            ->get();
        
        $services = $user->massageServices()->where('is_active', true)->get();
        
        $statuses = MassageOrder::getStatuses();
        $paymentMethods = MassageOrder::getPaymentMethods();

        return view('masseuse.orders.edit', compact('order', 'clients', 'services', 'statuses', 'paymentMethods'));
    }

    public function update(Request $request, MassageOrder $order)
    {
        $user = auth()->user();
        
        if ($order->employee_id !== $user->id) {
            abort(403);
        }
        
        $validated = $request->validate([
            'client_id' => 'nullable|exists:massage_clients,id',
            'client_name' => 'nullable|string|max:255',
            'service_id' => 'nullable|exists:massage_services,id',
            'service_name' => 'nullable|string|max:255',
            'order_date' => 'required|date',
            'order_time' => 'nullable',
            'amount' => 'required|numeric|min:0',
            'tip' => 'nullable|numeric|min:0',
            'payment_method' => 'nullable|in:cash,card,transfer',
            'status' => 'required|in:pending,confirmed,completed,cancelled',
            'notes' => 'nullable|string',
        ]);
        
        if (empty($validated['order_time'])) {
            $validated['order_time'] = null;
        }

        $order->update($validated);

        return redirect()->route('masseuse.orders.index')
            ->with('success', __('Заказ обновлён'));
    }

    public function destroy(MassageOrder $order)
    {
        $user = auth()->user();
        
        if ($order->employee_id !== $user->id) {
            abort(403);
        }
        
        $order->delete();

        return redirect()->route('masseuse.orders.index')
            ->with('success', __('Заказ удалён'));
    }
}
