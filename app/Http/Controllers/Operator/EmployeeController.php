<?php

namespace App\Http\Controllers\Operator;

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
     * Display a listing of subordinate employees.
     */
    public function index()
    {
        $operator = Auth::user();
        $subordinateIds = $operator->getSubordinateIds();

        // Только подопечные сотрудники этого оператора
        $employees = User::whereIn('id', $subordinateIds)
            ->with(['branch', 'massageServices'])
            ->orderBy('name')
            ->get();

        return view('operator.employees.index', compact('employees'));
    }

    /**
     * Show the form for creating a new employee.
     */
    public function create()
    {
        $operator = Auth::user();
        
        // Филиал оператора
        $branches = Branch::where('id', $operator->branch_id)->get();
        
        $services = MassageService::where('created_by', $operator->creatorId())
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();
        
        return view('operator.employees.create', compact('branches', 'services'));
    }

    /**
     * Store a newly created employee.
     */
    public function store(Request $request)
    {
        $operator = Auth::user();
        
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:6',
            'phone' => 'nullable|string|max:20',
            'birth_date' => 'nullable|date',
            'nationality' => 'nullable|string|max:50',
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'services' => 'nullable|array',
            'services.*' => 'exists:massage_services,id',
            'extra_services' => 'nullable|array',
            'extra_services.*' => 'exists:massage_services,id',
        ]);

        $user = new User();
        $user->name = $validated['name'];
        $user->email = $validated['email'];
        $user->password = Hash::make($validated['password']);
        $user->type = 'user';
        $user->lang = $operator->lang ?? 'cs';
        $user->created_by = $operator->creatorId();
        $user->branch_id = $operator->branch_id; // Филиал оператора
        $user->operator_id = $operator->id; // Привязка к оператору
        $user->birth_date = $validated['birth_date'] ?? null;
        $user->nationality = $validated['nationality'] ?? null;
        $user->is_active = 1;

        // Обработка аватарки
        if ($request->hasFile('avatar')) {
            $path = $request->file('avatar')->store('avatars/' . $operator->creatorId(), 'public');
            $user->avatar = $path;
        }

        $user->save();

        // Инициализируем баллы дежурств для нового сотрудника
        if ($user->branch_id) {
            $this->dutyService->initializePointsForNewEmployee(
                $user->branch_id,
                $user->id,
                $operator->creatorId()
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

        // Назначаем роль masseuse
        $user->assignRole('masseuse');

        return redirect()->route('operator.employees.index')
            ->with('success', __('Сотрудник успешно создан.'));
    }

    /**
     * Show the form for editing the specified employee.
     */
    public function edit(User $employee)
    {
        $operator = Auth::user();
        $subordinateIds = $operator->getSubordinateIds();
        
        // Проверяем что сотрудник подопечный этого оператора
        if (!in_array($employee->id, $subordinateIds)) {
            return redirect()->back()->with('error', __('Доступ запрещён.'));
        }

        $branches = Branch::where('id', $operator->branch_id)->get();
        
        $services = MassageService::where('created_by', $operator->creatorId())
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();
        
        // Получаем обычные и экстра услуги отдельно
        $selectedServices = $employee->regularServices->pluck('id')->toArray();
        $selectedExtraServices = $employee->extraServices->pluck('id')->toArray();
        
        return view('operator.employees.edit', compact('employee', 'branches', 'services', 'selectedServices', 'selectedExtraServices'));
    }

    /**
     * Update the specified employee.
     */
    public function update(Request $request, User $employee)
    {
        $operator = Auth::user();
        $subordinateIds = $operator->getSubordinateIds();
        
        // Проверяем что сотрудник подопечный этого оператора
        if (!in_array($employee->id, $subordinateIds)) {
            return redirect()->back()->with('error', __('Доступ запрещён.'));
        }

        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'email' => 'required|email|unique:users,email,' . $employee->id,
            'password' => 'nullable|min:6',
            'phone' => 'nullable|string|max:20',
            'birth_date' => 'nullable|date',
            'nationality' => 'nullable|string|max:50',
            'languages' => 'nullable|array',
            'languages.*' => 'string|max:50',
            'height' => 'nullable|integer|min:100|max:250',
            'weight' => 'nullable|integer|min:30|max:200',
            'breast_size' => 'nullable|integer|min:0|max:10',
            'bio' => 'nullable|string|max:2000',
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'photos' => 'nullable|array|max:10',
            'photos.*' => 'image|mimes:jpeg,png,jpg,webp|max:5120',
            'existing_photos' => 'nullable|array',
            'services' => 'nullable|array',
            'services.*' => 'exists:massage_services,id',
            'extra_services' => 'nullable|array',
            'extra_services.*' => 'exists:massage_services,id',
        ]);

        $employee->name = $validated['name'];
        $employee->email = $validated['email'];
        $employee->birth_date = $validated['birth_date'] ?? null;
        $employee->nationality = $validated['nationality'] ?? null;
        $employee->languages = $validated['languages'] ?? null;
        $employee->height = $validated['height'] ?? null;
        $employee->weight = $validated['weight'] ?? null;
        $employee->breast_size = $validated['breast_size'] ?? null;
        $employee->bio = $validated['bio'] ?? null;

        if (!empty($validated['password'])) {
            $employee->password = Hash::make($validated['password']);
        }

        // Обработка аватарки
        if ($request->hasFile('avatar')) {
            $path = $request->file('avatar')->store('avatars/' . $operator->creatorId(), 'public');
            $employee->avatar = $path;
        }

        // Обработка фото галереи
        $photos = $request->input('existing_photos', []);
        if ($request->hasFile('photos')) {
            foreach ($request->file('photos') as $photo) {
                if (count($photos) >= 10) break;
                $path = $photo->store('employees/' . $operator->creatorId(), 'public');
                $photos[] = $path;
            }
        }
        $employee->photos = $photos;

        $employee->save();

        // Обновление услуг (обычные и экстра)
        $syncData = [];
        foreach ($validated['services'] ?? [] as $serviceId) {
            $syncData[$serviceId] = ['is_extra' => false];
        }
        foreach ($validated['extra_services'] ?? [] as $serviceId) {
            if (!isset($syncData[$serviceId])) {
                $syncData[$serviceId] = ['is_extra' => true];
            }
        }
        $employee->massageServices()->sync($syncData);

        return redirect()->route('operator.employees.index')
            ->with('success', __('Сотрудник успешно обновлён.'));
    }

    /**
     * Remove the specified employee.
     */
    public function destroy(User $employee)
    {
        $operator = Auth::user();
        $subordinateIds = $operator->getSubordinateIds();
        
        // Проверяем что сотрудник подопечный этого оператора
        if (!in_array($employee->id, $subordinateIds)) {
            return redirect()->back()->with('error', __('Доступ запрещён.'));
        }

        $employee->delete();

        return redirect()->route('operator.employees.index')
            ->with('success', __('Сотрудник успешно удалён.'));
    }
}
