<?php

namespace App\Http\Controllers\Operator;

use App\Http\Controllers\Controller;
use App\Models\MassageOrder;
use App\Models\MassageClient;
use App\Models\MassageService;
use App\Models\Branch;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OrderController extends Controller
{
    /**
     * Display orders from subordinate employees only.
     */
    public function index(Request $request)
    {
        $operator = Auth::user();
        $subordinateIds = $operator->getSubordinateIds();

        $query = MassageOrder::with(['client', 'employee', 'branch', 'service'])
            ->whereIn('employee_id', $subordinateIds);

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

        return view('operator.orders.index', compact('orders', 'ordersCount', 'period'));
    }

    /**
     * Show form for creating new order.
     */
    public function create()
    {
        $operator = Auth::user();
        $subordinateIds = $operator->getSubordinateIds();
        $creatorId = $operator->creatorId();

        $clients = MassageClient::where('created_by', $creatorId)
            ->orderBy('first_name')
            ->get();

        $services = MassageService::where('created_by', $creatorId)
            ->where('is_active', true)
            ->where('is_extra', false)
            ->orderBy('name')
            ->get();

        // Филиалы подопечных сотрудников
        $branchIds = User::whereIn('id', $subordinateIds)->pluck('branch_id')->unique()->filter();
        $branches = Branch::whereIn('id', $branchIds)->orderBy('name')->get();

        // Только подопечные сотрудники с ролью masseuse через Spatie
        $employees = User::whereIn('id', $subordinateIds)
            ->role('masseuse')
            ->orderBy('name')
            ->get();

        $statuses = MassageOrder::getStatuses();
        $paymentMethods = MassageOrder::getPaymentMethods();

        return view('operator.orders.create', compact(
            'clients', 'services', 'branches', 'employees', 'statuses', 'paymentMethods'
        ));
    }

    /**
     * Store new order.
     */
    public function store(Request $request)
    {
        $operator = Auth::user();
        $subordinateIds = $operator->getSubordinateIds();

        $validated = $request->validate([
            'client_id' => 'nullable|exists:massage_clients,id',
            'client_name' => 'nullable|string|max:255',
            'employee_id' => 'required|exists:users,id',
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

        // Проверяем что сотрудник - подопечный оператора
        if (!in_array($validated['employee_id'], $subordinateIds)) {
            return back()->withErrors(['employee_id' => __('Вы можете создавать заказы только для своих подопечных')]);
        }

        $validated['created_by'] = $operator->creatorId();

        MassageOrder::create($validated);

        return redirect()->route('operator.orders.index')
            ->with('success', __('Заказ успешно создан'));
    }

    /**
     * Show form for editing order.
     */
    public function edit(MassageOrder $order)
    {
        $operator = Auth::user();
        $subordinateIds = $operator->getSubordinateIds();
        $creatorId = $operator->creatorId();

        // Проверяем доступ
        if (!in_array($order->employee_id, $subordinateIds)) {
            abort(403, __('Доступ запрещён'));
        }

        $clients = MassageClient::where('created_by', $creatorId)
            ->orderBy('first_name')
            ->get();

        $services = MassageService::where('created_by', $creatorId)
            ->where('is_extra', false)
            ->orderBy('name')
            ->get();

        $branchIds = User::whereIn('id', $subordinateIds)->pluck('branch_id')->unique()->filter();
        $branches = Branch::whereIn('id', $branchIds)->orderBy('name')->get();

        // Только подопечные сотрудники с ролью masseuse через Spatie
        $employees = User::whereIn('id', $subordinateIds)
            ->role('masseuse')
            ->orderBy('name')
            ->get();

        $statuses = MassageOrder::getStatuses();
        $paymentMethods = MassageOrder::getPaymentMethods();

        return view('operator.orders.edit', compact(
            'order', 'clients', 'services', 'branches', 'employees', 'statuses', 'paymentMethods'
        ));
    }

    /**
     * Update order.
     */
    public function update(Request $request, MassageOrder $order)
    {
        $operator = Auth::user();
        $subordinateIds = $operator->getSubordinateIds();

        // Проверяем доступ
        if (!in_array($order->employee_id, $subordinateIds)) {
            abort(403, __('Доступ запрещён'));
        }

        $validated = $request->validate([
            'client_id' => 'nullable|exists:massage_clients,id',
            'client_name' => 'nullable|string|max:255',
            'employee_id' => 'required|exists:users,id',
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

        // Проверяем что новый сотрудник тоже подопечный
        if (!in_array($validated['employee_id'], $subordinateIds)) {
            return back()->withErrors(['employee_id' => __('Вы можете назначать заказы только своим подопечным')]);
        }

        $order->update($validated);

        return redirect()->route('operator.orders.index')
            ->with('success', __('Заказ успешно обновлён'));
    }

    /**
     * Delete order.
     */
    public function destroy(MassageOrder $order)
    {
        $operator = Auth::user();
        $subordinateIds = $operator->getSubordinateIds();

        // Проверяем доступ
        if (!in_array($order->employee_id, $subordinateIds)) {
            abort(403, __('Доступ запрещён'));
        }

        $order->delete();

        return redirect()->route('operator.orders.index')
            ->with('success', __('Заказ удалён'));
    }

    /**
     * Update order status via AJAX.
     */
    public function updateStatus(Request $request, MassageOrder $order)
    {
        $operator = Auth::user();
        $subordinateIds = $operator->getSubordinateIds();

        // Проверяем доступ
        if (!in_array($order->employee_id, $subordinateIds)) {
            return response()->json([
                'success' => false,
                'message' => __('Доступ запрещён')
            ], 403);
        }

        $validated = $request->validate([
            'status' => 'required|in:pending,confirmed,completed,cancelled',
        ]);

        $order->update(['status' => $validated['status']]);

        return response()->json([
            'success' => true,
            'message' => __('Статус обновлён'),
            'status' => $validated['status']
        ]);
    }
}
