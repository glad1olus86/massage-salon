<?php

namespace App\Http\Controllers\Infinity;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Branch;
use App\Models\MassageService;
use App\Services\Infinity\DutyService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class EmployeeController extends Controller
{
    protected DutyService $dutyService;

    public function __construct(DutyService $dutyService)
    {
        $this->dutyService = $dutyService;
    }

    /**
     * Display a listing of employees.
     */
    public function index()
    {
        $creatorId = Auth::user()->creatorId();

        // Массажистки (с ролью masseuse или без роли operator)
        $employees = User::where('created_by', $creatorId)
            ->whereNotIn('type', ['company', 'client', 'super admin'])
            ->whereDoesntHave('roles', function ($q) {
                $q->where('name', 'operator');
            })
            ->with(['branch', 'massageServices'])
            ->orderBy('name')
            ->get();

        // Операторы (с ролью operator)
        $operators = User::where('created_by', $creatorId)
            ->whereNotIn('type', ['company', 'client', 'super admin'])
            ->role('operator')
            ->with(['branch'])
            ->orderBy('name')
            ->get();

        return view('infinity.employees.index', compact('employees', 'operators'));
    }

    /**
     * Show the form for creating a new employee.
     */
    public function create()
    {
        $branches = Branch::where('created_by', Auth::user()->creatorId())->get();
        
        // Обычные услуги (is_extra = false)
        $regularServices = MassageService::where('created_by', Auth::user()->creatorId())
            ->where('is_active', true)
            ->where('is_extra', false)
            ->orderBy('sort_order')
            ->get();
        
        // Экстра услуги (is_extra = true)
        $extraServices = MassageService::where('created_by', Auth::user()->creatorId())
            ->where('is_active', true)
            ->where('is_extra', true)
            ->orderBy('sort_order')
            ->get();
        
        $roles = Role::where('created_by', Auth::user()->creatorId())->get();
        
        // Получаем операторов (пользователи с ролью operator)
        $operators = User::where('created_by', Auth::user()->creatorId())
            ->role('operator')
            ->orderBy('name')
            ->get();
        
        return view('infinity.employees.create', compact('branches', 'regularServices', 'extraServices', 'roles', 'operators'));
    }

    /**
     * Store a newly created employee.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:6',
            'phone' => 'nullable|string|max:20',
            'birth_date' => 'nullable|date',
            'nationality' => 'nullable|string|max:50',
            'bio' => 'nullable|string|max:2000',
            'branch_id' => 'required|exists:branches,id',
            'operator_id' => 'nullable|exists:users,id',
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'services' => 'nullable|array',
            'services.*' => 'exists:massage_services,id',
            'extra_services' => 'nullable|array',
            'extra_services.*' => 'exists:massage_services,id',
            'role' => 'nullable|string',
        ]);

        $user = new User();
        $user->name = $validated['name'];
        $user->email = $validated['email'];
        $user->password = Hash::make($validated['password']);
        $user->type = 'user';
        $user->lang = Auth::user()->lang ?? 'cs';
        $user->created_by = Auth::user()->creatorId();
        $user->branch_id = $validated['branch_id'] ?? null;
        $user->operator_id = $validated['operator_id'] ?? null;
        $user->birth_date = $validated['birth_date'] ?? null;
        $user->nationality = $validated['nationality'] ?? null;
        $user->bio = $validated['bio'] ?? null;
        $user->is_active = 1;

        // Обработка аватарки
        if ($request->hasFile('avatar')) {
            $path = $request->file('avatar')->store('avatars/' . Auth::user()->creatorId(), 'public');
            $user->avatar = $path;
        }

        $user->save();

        // Инициализируем баллы дежурств для нового сотрудника
        if ($user->branch_id) {
            $this->dutyService->initializePointsForNewEmployee(
                $user->branch_id,
                $user->id,
                Auth::user()->creatorId()
            );
        }

        // Привязка услуг (обычные и экстра)
        $syncData = [];
        foreach ($validated['services'] ?? [] as $serviceId) {
            $syncData[$serviceId] = ['is_extra' => false];
        }
        foreach ($validated['extra_services'] ?? [] as $serviceId) {
            if (!isset($syncData[$serviceId])) {
                $syncData[$serviceId] = ['is_extra' => true];
            }
        }
        $user->massageServices()->sync($syncData);

        // Назначение роли
        if (!empty($validated['role'])) {
            $user->assignRole($validated['role']);
        }

        return redirect()->route('infinity.employees.index')
            ->with('success', __('Сотрудник успешно создан.'));
    }

    /**
     * Show the form for editing the specified employee.
     */
    public function edit(User $employee)
    {
        if ($employee->created_by != Auth::user()->creatorId() && $employee->id != Auth::user()->creatorId()) {
            return redirect()->back()->with('error', __('Доступ запрещён.'));
        }

        $branches = Branch::where('created_by', Auth::user()->creatorId())->get();
        
        // Обычные услуги (is_extra = false)
        $regularServices = MassageService::where('created_by', Auth::user()->creatorId())
            ->where('is_active', true)
            ->where('is_extra', false)
            ->orderBy('sort_order')
            ->get();
        
        // Экстра услуги (is_extra = true)
        $extraServices = MassageService::where('created_by', Auth::user()->creatorId())
            ->where('is_active', true)
            ->where('is_extra', true)
            ->orderBy('sort_order')
            ->get();
        
        $roles = Role::where('created_by', Auth::user()->creatorId())->get();
        
        // Получаем операторов (пользователи с ролью operator)
        $operators = User::where('created_by', Auth::user()->creatorId())
            ->role('operator')
            ->orderBy('name')
            ->get();
        
        // Получаем обычные и экстра услуги отдельно
        $selectedServices = $employee->regularServices->pluck('id')->toArray();
        $selectedExtraServices = $employee->extraServices->pluck('id')->toArray();
        
        return view('infinity.employees.edit', compact('employee', 'branches', 'regularServices', 'extraServices', 'roles', 'operators', 'selectedServices', 'selectedExtraServices'));
    }

    /**
     * Update the specified employee.
     */
    public function update(Request $request, User $employee)
    {
        if ($employee->created_by != Auth::user()->creatorId() && $employee->id != Auth::user()->creatorId()) {
            return redirect()->back()->with('error', __('Доступ запрещён.'));
        }

        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'email' => 'required|email|unique:users,email,' . $employee->id,
            'password' => 'nullable|min:6',
            'phone' => 'nullable|string|max:20',
            'birth_date' => 'nullable|date',
            'nationality' => 'nullable|string|max:50',
            'bio' => 'nullable|string|max:2000',
            'branch_id' => 'required|exists:branches,id',
            'operator_id' => 'nullable|exists:users,id',
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'photos' => 'nullable|array|max:10',
            'photos.*' => 'image|mimes:jpeg,png,jpg,webp|max:5120',
            'existing_photos' => 'nullable|array',
            'services' => 'nullable|array',
            'services.*' => 'exists:massage_services,id',
            'extra_services' => 'nullable|array',
            'extra_services.*' => 'exists:massage_services,id',
            'role' => 'nullable|string',
        ]);

        $oldBranchId = $employee->branch_id;
        $newBranchId = $validated['branch_id'] ?? null;

        $employee->name = $validated['name'];
        $employee->email = $validated['email'];
        $employee->branch_id = $newBranchId;
        $employee->operator_id = $validated['operator_id'] ?? null;
        $employee->birth_date = $validated['birth_date'] ?? null;
        $employee->nationality = $validated['nationality'] ?? null;
        $employee->bio = $validated['bio'] ?? null;

        if (!empty($validated['password'])) {
            $employee->password = Hash::make($validated['password']);
        }

        // Обработка аватарки
        if ($request->hasFile('avatar')) {
            $path = $request->file('avatar')->store('avatars/' . Auth::user()->creatorId(), 'public');
            $employee->avatar = $path;
        }

        // Обработка фото галереи
        $photos = $request->input('existing_photos', []);
        if ($request->hasFile('photos')) {
            foreach ($request->file('photos') as $photo) {
                if (count($photos) >= 10) break;
                $path = $photo->store('employees/' . Auth::user()->creatorId(), 'public');
                $photos[] = $path;
            }
        }
        $employee->photos = $photos;

        $employee->save();

        // Если сменился филиал, инициализируем баллы для нового филиала
        if ($newBranchId && $newBranchId != $oldBranchId) {
            $this->dutyService->initializePointsForNewEmployee(
                $newBranchId,
                $employee->id,
                Auth::user()->creatorId()
            );
        }

        // Обновление услуг (обычные и экстра)
        $syncData = [];
        foreach ($validated['services'] ?? [] as $serviceId) {
            $syncData[$serviceId] = ['is_extra' => false];
        }
        foreach ($validated['extra_services'] ?? [] as $serviceId) {
            // Не добавляем если уже есть в обычных
            if (!isset($syncData[$serviceId])) {
                $syncData[$serviceId] = ['is_extra' => true];
            }
        }
        $employee->massageServices()->sync($syncData);

        // Обновление роли
        $employee->syncRoles([]); // Сначала удаляем все роли
        if (!empty($validated['role'])) {
            $employee->assignRole($validated['role']);
        }

        return redirect()->route('infinity.employees.index')
            ->with('success', __('Сотрудник успешно обновлён.'));
    }

    /**
     * Remove the specified employee.
     */
    public function destroy(User $employee)
    {
        if ($employee->created_by != Auth::user()->creatorId()) {
            return redirect()->back()->with('error', __('Доступ запрещён.'));
        }

        $employee->delete();

        return redirect()->route('infinity.employees.index')
            ->with('success', __('Сотрудник успешно удалён.'));
    }
}
