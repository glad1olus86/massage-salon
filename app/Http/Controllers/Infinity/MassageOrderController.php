<?php

namespace App\Http\Controllers\Infinity;

use App\Http\Controllers\Controller;
use App\Models\MassageOrder;
use App\Models\MassageClient;
use App\Models\MassageService;
use App\Models\Branch;
use App\Models\User;
use Illuminate\Http\Request;

class MassageOrderController extends Controller
{
    public function index(Request $request)
    {
        $query = MassageOrder::with(['client', 'employee', 'branch', 'service'])
            ->where('created_by', \Auth::user()->creatorId());

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
                $query->whereMonth('order_date', $now->month)
                      ->whereYear('order_date', $now->year);
                break;
            case 'all':
                // Без фильтра по дате
                break;
        }

        $orders = $query->orderBy('order_date', 'desc')
                       ->orderBy('order_time', 'desc')
                       ->paginate(20);

        $ordersCount = $orders->total();

        return view('infinity.orders.index', compact('orders', 'ordersCount', 'period'));
    }

    public function create()
    {
        $clients = MassageClient::where('created_by', \Auth::user()->creatorId())
            ->orderBy('first_name')
            ->get();
        
        $services = MassageService::where('created_by', \Auth::user()->creatorId())
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
        
        $branches = Branch::where('created_by', \Auth::user()->creatorId())
            ->orderBy('name')
            ->get();
        
        // Получаем только массажисток (users с ролью masseuse через Spatie)
        $employees = User::where(function ($q) {
                $q->where('created_by', \Auth::user()->creatorId())
                  ->orWhere('id', \Auth::user()->creatorId());
            })
            ->role('masseuse')
            ->orderBy('name')
            ->get();

        $statuses = MassageOrder::getStatuses();
        $paymentMethods = MassageOrder::getPaymentMethods();

        return view('infinity.orders.create', compact(
            'clients', 'services', 'branches', 'employees', 'statuses', 'paymentMethods'
        ));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'client_id' => 'nullable|exists:massage_clients,id',
            'client_name' => 'nullable|string|max:255',
            'employee_id' => 'nullable|exists:users,id',
            'branch_id' => 'nullable|exists:branches,id',
            'service_id' => 'nullable|exists:massage_services,id',
            'service_name' => 'nullable|string|max:255',
            'order_date' => 'required|date',
            'order_time' => 'nullable|date_format:H:i',
            'duration' => 'nullable|integer|min:1',
            'amount' => 'required|numeric|min:0',
            'tip' => 'nullable|numeric|min:0',
            'payment_method' => 'nullable|in:cash,card,transfer',
            'status' => 'required|in:pending,confirmed,completed,cancelled',
            'notes' => 'nullable|string',
        ]);

        $validated['created_by'] = \Auth::user()->creatorId();

        MassageOrder::create($validated);

        return redirect()->route('infinity.orders.index')
            ->with('success', __('Заказ успешно создан'));
    }

    public function edit(MassageOrder $order)
    {
        $clients = MassageClient::where('created_by', \Auth::user()->creatorId())
            ->orderBy('first_name')
            ->get();
        
        $services = MassageService::where('created_by', \Auth::user()->creatorId())
            ->orderBy('name')
            ->get();
        
        $branches = Branch::where('created_by', \Auth::user()->creatorId())
            ->orderBy('name')
            ->get();
        
        // Получаем только массажисток (users с ролью masseuse через Spatie)
        $employees = User::where(function ($q) {
                $q->where('created_by', \Auth::user()->creatorId())
                  ->orWhere('id', \Auth::user()->creatorId());
            })
            ->role('masseuse')
            ->orderBy('name')
            ->get();

        $statuses = MassageOrder::getStatuses();
        $paymentMethods = MassageOrder::getPaymentMethods();

        return view('infinity.orders.edit', compact(
            'order', 'clients', 'services', 'branches', 'employees', 'statuses', 'paymentMethods'
        ));
    }

    public function update(Request $request, MassageOrder $order)
    {
        $validated = $request->validate([
            'client_id' => 'nullable|exists:massage_clients,id',
            'client_name' => 'nullable|string|max:255',
            'employee_id' => 'nullable|exists:users,id',
            'branch_id' => 'nullable|exists:branches,id',
            'service_id' => 'nullable|exists:massage_services,id',
            'service_name' => 'nullable|string|max:255',
            'order_date' => 'required|date',
            'order_time' => 'nullable|date_format:H:i',
            'duration' => 'nullable|integer|min:1',
            'amount' => 'required|numeric|min:0',
            'tip' => 'nullable|numeric|min:0',
            'payment_method' => 'nullable|in:cash,card,transfer',
            'status' => 'required|in:pending,confirmed,completed,cancelled',
            'notes' => 'nullable|string',
        ]);

        // Debug: логируем что пришло
        \Log::info('Order update', [
            'order_id' => $order->id,
            'old_status' => $order->status,
            'new_status' => $validated['status'],
            'all_validated' => $validated
        ]);

        $order->update($validated);

        return redirect()->route('infinity.orders.index')
            ->with('success', __('Заказ успешно обновлён'));
    }

    public function destroy(MassageOrder $order)
    {
        $order->delete();

        return redirect()->route('infinity.orders.index')
            ->with('success', __('Заказ удалён'));
    }
}
