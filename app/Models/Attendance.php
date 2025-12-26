<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Attendance extends Model
{
    use HasFactory;

    const STATUS_PRESENT = 'present';
    const STATUS_LATE = 'late';
    const STATUS_ABSENT = 'absent';
    const STATUS_SICK = 'sick';
    const STATUS_VACATION = 'vacation';

    protected $fillable = [
        'worker_id',
        'shift_schedule_id',
        'shift_template_id',
        'date',
        'check_in',
        'check_out',
        'status',
        'worked_hours',
        'overtime_hours',
        'notes',
        'marked_by',
        'created_by',
    ];

    protected $casts = [
        'date' => 'date',
        'worked_hours' => 'decimal:2',
        'overtime_hours' => 'decimal:2',
    ];

    /**
     * Get the worker this attendance belongs to.
     */
    public function worker()
    {
        return $this->belongsTo(Worker::class);
    }

    /**
     * Get the shift schedule.
     */
    public function shiftSchedule()
    {
        return $this->belongsTo(ShiftSchedule::class);
    }

    /**
     * Get the shift template.
     */
    public function shiftTemplate()
    {
        return $this->belongsTo(ShiftTemplate::class);
    }

    /**
     * Get the user who marked this attendance.
     */
    public function markedByUser()
    {
        return $this->belongsTo(User::class, 'marked_by');
    }

    /**
     * Get the user who created this record.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Calculate worked hours from check_in and check_out.
     */
    public function calculateWorkedHours(): ?float
    {
        if (!$this->check_in || !$this->check_out) {
            return null;
        }

        $checkIn = Carbon::parse($this->check_in);
        $checkOut = Carbon::parse($this->check_out);

        // Handle overnight shifts
        if ($checkOut->lt($checkIn)) {
            $checkOut->addDay();
        }

        $totalMinutes = $checkIn->diffInMinutes($checkOut);

        // Subtract break if shift template is available
        if ($this->shiftTemplate) {
            $totalMinutes -= $this->shiftTemplate->break_minutes;
        }

        return round($totalMinutes / 60, 2);
    }

    /**
     * Determine status based on check_in time and scheduled start.
     */
    public function determineStatus(): string
    {
        if (!$this->check_in) {
            return self::STATUS_ABSENT;
        }

        if (!$this->shiftTemplate) {
            return self::STATUS_PRESENT;
        }

        $checkIn = Carbon::parse($this->check_in);
        $scheduledStart = Carbon::parse($this->shiftTemplate->start_time);

        // Late if more than 15 minutes after scheduled start
        $lateThreshold = 15;
        if ($checkIn->diffInMinutes($scheduledStart, false) > $lateThreshold) {
            return self::STATUS_LATE;
        }

        return self::STATUS_PRESENT;
    }

    /**
     * Get status label for display.
     */
    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_PRESENT => __('Present'),
            self::STATUS_LATE => __('Late'),
            self::STATUS_ABSENT => __('Absent'),
            self::STATUS_SICK => __('Sick'),
            self::STATUS_VACATION => __('Vacation'),
            default => $this->status,
        };
    }

    /**
     * Get status badge class for UI.
     */
    public function getStatusBadgeAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_PRESENT => 'bg-success',
            self::STATUS_LATE => 'bg-warning',
            self::STATUS_ABSENT => 'bg-danger',
            self::STATUS_SICK => 'bg-info',
            self::STATUS_VACATION => 'bg-primary',
            default => 'bg-secondary',
        };
    }

    /**
     * Scope for a specific date.
     */
    public function scopeForDate($query, $date)
    {
        return $query->whereDate('date', $date);
    }

    /**
     * Scope for a date range.
     */
    public function scopeForDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('date', [$startDate, $endDate]);
    }

    /**
     * Scope for current user's company.
     */
    public function scopeForCompany($query, $companyId)
    {
        return $query->where('created_by', $companyId);
    }

    /**
     * Scope for a specific status.
     */
    public function scopeWithStatus($query, $status)
    {
        return $query->where('status', $status);
    }
}
