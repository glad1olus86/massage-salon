<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\ShiftSchedule;
use App\Models\ShiftException;
use App\Models\ShiftTemplate;
use App\Models\Worker;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Builder;

class AttendanceService
{
    /**
     * Get schedule data for a week (for calendar view).
     */
    public function getScheduleForWeek(int $companyId, $startDate, ?int $workPlaceId = null, ?User $user = null): array
    {
        $startDate = Carbon::parse($startDate)->startOfWeek();
        $endDate = $startDate->copy()->endOfWeek();
        
        $result = [];
        
        // Get workers with their schedules
        $workersQuery = Worker::where('created_by', $companyId)
            ->with(['currentWorkAssignment.workPlace']);
        
        // Apply visibility filter
        if ($user) {
            $workersQuery = $this->applyVisibilityFilter($workersQuery, $user);
        }
        
        // Filter by workplace if specified
        if ($workPlaceId) {
            $workersQuery->whereHas('currentWorkAssignment', function ($q) use ($workPlaceId) {
                $q->where('work_place_id', $workPlaceId);
            });
        }
        
        $workers = $workersQuery->get();
        
        foreach ($workers as $worker) {
            $workerData = [
                'id' => $worker->id,
                'name' => $worker->first_name . ' ' . $worker->last_name,
                'work_place' => $worker->currentWorkAssignment?->workPlace?->name,
                'days' => [],
            ];
            
            // Get schedules for this worker
            $schedules = ShiftSchedule::where('worker_id', $worker->id)
                ->validOn($startDate)
                ->with('shiftTemplate')
                ->get();
            
            // Get exceptions for this week
            $exceptions = ShiftException::where('worker_id', $worker->id)
                ->whereBetween('date', [$startDate, $endDate])
                ->with('shiftTemplate')
                ->get()
                ->keyBy(fn($e) => $e->date->format('Y-m-d'));
            
            // Build days array
            for ($i = 0; $i < 7; $i++) {
                $date = $startDate->copy()->addDays($i);
                $dateKey = $date->format('Y-m-d');
                $dayOfWeek = $date->dayOfWeekIso;
                
                $dayData = [
                    'date' => $dateKey,
                    'shift' => null,
                ];
                
                // Check for exception first
                if (isset($exceptions[$dateKey])) {
                    $exception = $exceptions[$dateKey];
                    if ($exception->type === ShiftException::TYPE_REMOVE) {
                        $dayData['shift'] = null;
                    } else {
                        $dayData['shift'] = $this->formatShiftForCalendar($exception->shiftTemplate);
                    }
                } else {
                    // Check regular schedule
                    foreach ($schedules as $schedule) {
                        if ($schedule->shouldWorkOn($date)) {
                            $dayData['shift'] = $this->formatShiftForCalendar($schedule->shiftTemplate);
                            break;
                        }
                    }
                }
                
                $workerData['days'][] = $dayData;
            }
            
            $result[] = $workerData;
        }
        
        return $result;
    }

    /**
     * Get workers scheduled for a specific date.
     */
    public function getWorkersForDate($date, int $companyId, ?int $workPlaceId = null, ?int $shiftTemplateId = null, ?User $user = null): Collection
    {
        $date = Carbon::parse($date);
        $dayOfWeek = $date->dayOfWeekIso;
        
        // Get all workers
        $workersQuery = Worker::where('created_by', $companyId)
            ->with(['currentWorkAssignment.workPlace']);
        
        // Filter by workplace
        if ($workPlaceId) {
            $workersQuery->whereHas('currentWorkAssignment', function ($q) use ($workPlaceId) {
                $q->where('work_place_id', $workPlaceId);
            });
        }
        
        $workers = $workersQuery->get();
        
        $result = collect();
        
        foreach ($workers as $worker) {
            $shiftData = $this->getWorkerShiftForDate($worker, $date);
            
            if (!$shiftData) {
                continue;
            }
            
            // Filter by shift template if specified
            if ($shiftTemplateId && $shiftData['template']->id !== $shiftTemplateId) {
                continue;
            }
            
            // Get attendance record if exists
            $attendance = Attendance::where('worker_id', $worker->id)
                ->whereDate('date', $date)
                ->first();
            
            $result->push([
                'worker' => $worker,
                'shift_template' => $shiftData['template'],
                'shift_schedule' => $shiftData['schedule'],
                'attendance' => $attendance,
            ]);
        }
        
        return $result;
    }

    /**
     * Get worker's shift for a specific date (considering exceptions).
     */
    public function getWorkerShiftForDate(Worker $worker, $date): ?array
    {
        $date = Carbon::parse($date);
        
        // Check for exception first
        $exception = ShiftException::where('worker_id', $worker->id)
            ->whereDate('date', $date)
            ->with('shiftTemplate')
            ->first();
        
        if ($exception) {
            if ($exception->type === ShiftException::TYPE_REMOVE) {
                return null;
            }
            return [
                'template' => $exception->shiftTemplate,
                'schedule' => null,
                'is_exception' => true,
            ];
        }
        
        // Check regular schedule
        $dayOfWeek = $date->dayOfWeekIso;
        
        $schedule = ShiftSchedule::where('worker_id', $worker->id)
            ->validOn($date)
            ->where(function ($q) use ($dayOfWeek) {
                // Check both integer and string versions for compatibility
                $q->whereJsonContains('work_days', $dayOfWeek)
                    ->orWhereJsonContains('work_days', (string) $dayOfWeek);
            })
            ->with('shiftTemplate')
            ->first();
        
        if ($schedule) {
            return [
                'template' => $schedule->shiftTemplate,
                'schedule' => $schedule,
                'is_exception' => false,
            ];
        }
        
        return null;
    }

    /**
     * Mark attendance for a worker.
     */
    public function markAttendance(
        int $workerId,
        $date,
        array $data,
        User $markedBy
    ): Attendance {
        $date = Carbon::parse($date);
        $worker = Worker::findOrFail($workerId);
        
        // Get shift info
        $shiftData = $this->getWorkerShiftForDate($worker, $date);
        
        $attendanceData = [
            'worker_id' => $workerId,
            'date' => $date,
            'shift_schedule_id' => $shiftData['schedule']?->id ?? null,
            'shift_template_id' => $shiftData['template']?->id ?? null,
            'check_in' => $data['check_in'] ?? null,
            'check_out' => $data['check_out'] ?? null,
            'status' => $data['status'] ?? Attendance::STATUS_ABSENT,
            'notes' => $data['notes'] ?? null,
            'marked_by' => $markedBy->id,
            'created_by' => $markedBy->creatorId(),
        ];
        
        // For sick/vacation/absent status - set hours to 0 and clear times
        if (in_array($data['status'] ?? '', [Attendance::STATUS_SICK, Attendance::STATUS_VACATION, Attendance::STATUS_ABSENT])) {
            $attendanceData['check_in'] = null;
            $attendanceData['check_out'] = null;
            $attendanceData['worked_hours'] = 0;
        }
        // Calculate worked hours if check_in and check_out provided
        elseif (!empty($data['check_in']) && !empty($data['check_out'])) {
            $checkIn = Carbon::parse($data['check_in']);
            $checkOut = Carbon::parse($data['check_out']);
            
            if ($checkOut->lt($checkIn)) {
                $checkOut->addDay();
            }
            
            $totalMinutes = $checkIn->diffInMinutes($checkOut);
            $breakMinutes = $shiftData['template']?->break_minutes ?? 0;
            $attendanceData['worked_hours'] = round(($totalMinutes - $breakMinutes) / 60, 2);
        }
        
        // Auto-determine status if not explicitly set
        if (!isset($data['status']) && !empty($data['check_in']) && $shiftData['template']) {
            $checkIn = Carbon::parse($data['check_in']);
            $scheduledStart = Carbon::parse($shiftData['template']->start_time);
            
            if ($checkIn->diffInMinutes($scheduledStart, false) > 15) {
                $attendanceData['status'] = Attendance::STATUS_LATE;
            } else {
                $attendanceData['status'] = Attendance::STATUS_PRESENT;
            }
        }
        
        return Attendance::updateOrCreate(
            ['worker_id' => $workerId, 'date' => $date],
            $attendanceData
        );
    }

    /**
     * Mark all workers as present for a date.
     */
    public function markAllPresent($date, array $workerIds, User $markedBy): int
    {
        $date = Carbon::parse($date);
        $count = 0;
        
        foreach ($workerIds as $workerId) {
            $worker = Worker::find($workerId);
            if (!$worker) continue;
            
            $shiftData = $this->getWorkerShiftForDate($worker, $date);
            if (!$shiftData) continue;
            
            // Skip if already marked
            $existing = Attendance::where('worker_id', $workerId)
                ->whereDate('date', $date)
                ->first();
            
            if ($existing && $existing->status !== Attendance::STATUS_ABSENT) {
                continue;
            }
            
            $template = $shiftData['template'];
            
            $this->markAttendance($workerId, $date, [
                'check_in' => $template->start_time,
                'check_out' => $template->end_time,
                'status' => Attendance::STATUS_PRESENT,
            ], $markedBy);
            
            $count++;
        }
        
        return $count;
    }

    /**
     * Apply visibility filter based on user hierarchy.
     * - Director (company type): sees all workers
     * - Other users: see workers they are responsible for or all if no responsible_id
     */
    public function applyVisibilityFilter(Builder $query, User $user): Builder
    {
        // Director (company type) sees everything
        if ($user->type === 'company') {
            return $query;
        }
        
        // Super admin sees everything
        if ($user->type === 'super admin') {
            return $query;
        }
        
        // Other users see workers they are responsible for, or workers without responsible
        return $query->where(function ($q) use ($user) {
            $q->where('responsible_id', $user->id)
                ->orWhereNull('responsible_id');
        });
    }

    /**
     * Format shift template for calendar display.
     */
    private function formatShiftForCalendar(?ShiftTemplate $template): ?array
    {
        if (!$template) {
            return null;
        }
        
        return [
            'id' => $template->id,
            'name' => $template->name,
            'time_range' => $template->time_range,
            'color' => $template->color,
        ];
    }

    /**
     * Get attendance statistics for a period.
     */
    public function getStatistics(int $companyId, $startDate, $endDate, ?User $user = null): array
    {
        $query = Attendance::where('created_by', $companyId)
            ->whereBetween('date', [$startDate, $endDate]);
        
        if ($user) {
            $query->whereHas('worker', function ($q) use ($user) {
                $this->applyVisibilityFilter($q, $user);
            });
        }
        
        return [
            'total' => $query->count(),
            'present' => (clone $query)->where('status', Attendance::STATUS_PRESENT)->count(),
            'late' => (clone $query)->where('status', Attendance::STATUS_LATE)->count(),
            'absent' => (clone $query)->where('status', Attendance::STATUS_ABSENT)->count(),
            'sick' => (clone $query)->where('status', Attendance::STATUS_SICK)->count(),
            'vacation' => (clone $query)->where('status', Attendance::STATUS_VACATION)->count(),
        ];
    }

    /**
     * Search workers for bulk assignment.
     */
    public function searchWorkers(string $search, ?int $workPlaceId, User $user): Collection
    {
        $query = Worker::where('created_by', $user->creatorId())
            ->with(['currentWorkAssignment.workPlace']);
        
        // Filter by workplace if specified
        if ($workPlaceId) {
            $query->whereHas('currentWorkAssignment', function ($q) use ($workPlaceId) {
                $q->where('work_place_id', $workPlaceId);
            });
        }
        
        // Search by name
        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%");
            });
        }
        
        return $query->limit(50)->get();
    }
}
