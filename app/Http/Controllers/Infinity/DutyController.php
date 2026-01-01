<?php

namespace App\Http\Controllers\Infinity;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\CleaningDuty;
use App\Models\CleaningStatus;
use App\Services\Infinity\DutyService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DutyController extends Controller
{
    protected DutyService $dutyService;

    public function __construct(DutyService $dutyService)
    {
        $this->dutyService = $dutyService;
    }

    /**
     * Смена дежурного (только для операторов).
     */
    public function changeDuty(Request $request, CleaningDuty $duty)
    {
        $user = Auth::user();
        $creatorId = $user->creatorId();

        // Проверяем доступ к филиалу
        $branch = Branch::where('id', $duty->branch_id)
            ->where('created_by', $creatorId)
            ->firstOrFail();

        // TODO: Проверка роли оператора
        // if (!$user->hasPermission('duty.change')) {
        //     return response()->json(['error' => __('Доступ запрещён.')], 403);
        // }

        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        $duty = $this->dutyService->changeDutyPerson($duty, $validated['user_id'], $user->id);

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => __('Дежурный изменён.'),
                'duty' => $duty->load('user'),
            ]);
        }

        return redirect()->back()->with('success', __('Дежурный изменён.'));
    }

    /**
     * Отметка выполнения дежурства.
     */
    public function completeDuty(Request $request, CleaningDuty $duty)
    {
        $user = Auth::user();
        $creatorId = $user->creatorId();

        // Проверяем доступ к филиалу
        $branch = Branch::where('id', $duty->branch_id)
            ->where('created_by', $creatorId)
            ->firstOrFail();

        // Проверяем что дежурство ещё не выполнено
        if ($duty->isCompleted()) {
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => __('Дежурство уже выполнено.'),
                ], 422);
            }
            return redirect()->back()->withErrors(['error' => __('Дежурство уже выполнено.')]);
        }

        $this->dutyService->completeDuty($duty);

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => __('Дежурство отмечено как выполненное.'),
                'duty' => $duty->fresh()->load(['user', 'cleaningStatuses']),
            ]);
        }

        return redirect()->back()->with('success', __('Дежурство отмечено как выполненное.'));
    }

    /**
     * Обновление статуса уборки.
     */
    public function updateCleaningStatus(Request $request, CleaningStatus $status)
    {
        $user = Auth::user();
        $creatorId = $user->creatorId();

        // Проверяем доступ через дежурство
        $duty = $status->cleaningDuty;
        $branch = Branch::where('id', $duty->branch_id)
            ->where('created_by', $creatorId)
            ->firstOrFail();

        $validated = $request->validate([
            'status' => 'required|in:clean,dirty,in_progress',
        ]);

        if ($validated['status'] === 'clean') {
            $status->markAsClean($user->id);
        } else {
            $status->update([
                'status' => $validated['status'],
                'cleaned_by' => $validated['status'] === 'in_progress' ? $user->id : null,
                'cleaned_at' => null,
            ]);
        }

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => __('Статус уборки обновлён.'),
                'status' => $status->fresh(),
            ]);
        }

        return redirect()->back()->with('success', __('Статус уборки обновлён.'));
    }

    /**
     * Назначить дежурства на период.
     */
    public function assignDuties(Request $request)
    {
        $user = Auth::user();
        $creatorId = $user->creatorId();

        $validated = $request->validate([
            'branch_id' => 'required|exists:branches,id',
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        // Проверяем доступ к филиалу
        $branch = Branch::where('id', $validated['branch_id'])
            ->where('created_by', $creatorId)
            ->firstOrFail();

        $startDate = Carbon::parse($validated['start_date']);
        $endDate = Carbon::parse($validated['end_date']);

        $duties = $this->dutyService->assignDutiesForPeriod($branch->id, $startDate, $endDate);

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => __('Назначено дежурств: :count', ['count' => $duties->count()]),
                'duties' => $duties->load('user'),
            ]);
        }

        return redirect()->back()->with('success', __('Назначено дежурств: :count', ['count' => $duties->count()]));
    }

    /**
     * Получить список сотрудников с баллами.
     */
    public function getEmployeesWithPoints(Request $request)
    {
        $user = Auth::user();
        $creatorId = $user->creatorId();

        $validated = $request->validate([
            'branch_id' => 'required|exists:branches,id',
        ]);

        // Проверяем доступ к филиалу
        $branch = Branch::where('id', $validated['branch_id'])
            ->where('created_by', $creatorId)
            ->firstOrFail();

        $employees = $this->dutyService->getEmployeesWithPoints($branch->id);

        return response()->json([
            'employees' => $employees,
        ]);
    }
}
