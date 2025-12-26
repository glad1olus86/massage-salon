<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class ShiftSchedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'worker_id',
        'shift_template_id',
        'work_days',
        'valid_from',
        'valid_until',
        'created_by',
    ];

    protected $casts = [
        'work_days' => 'array',
        'valid_from' => 'date',
        'valid_until' => 'date',
    ];

    /**
     * Get the worker this schedule belongs to.
     */
    public function worker()
    {
        return $this->belongsTo(Worker::class);
    }

    /**
     * Get the shift template for this schedule.
     */
    public function shiftTemplate()
    {
        return $this->belongsTo(ShiftTemplate::class);
    }

    /**
     * Get the user who created this schedule.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Scope for active schedules (valid for current date).
     */
    public function scopeActive($query, $date = null)
    {
        $date = $date ? Carbon::parse($date) : Carbon::today();

        return $query->where('valid_from', '<=', $date)
            ->where(function ($q) use ($date) {
                $q->whereNull('valid_until')
                    ->orWhere('valid_until', '>=', $date);
            });
    }

    /**
     * Scope for schedules valid on a specific date.
     */
    public function scopeValidOn($query, $date)
    {
        $date = Carbon::parse($date);

        return $query->where('valid_from', '<=', $date)
            ->where(function ($q) use ($date) {
                $q->whereNull('valid_until')
                    ->orWhere('valid_until', '>=', $date);
            });
    }

    /**
     * Check if this schedule is active on a given date.
     */
    public function isActiveOn($date): bool
    {
        $date = Carbon::parse($date);

        if ($date->lt($this->valid_from)) {
            return false;
        }

        if ($this->valid_until && $date->gt($this->valid_until)) {
            return false;
        }

        return true;
    }

    /**
     * Check if worker should work on a given date according to this schedule.
     */
    public function shouldWorkOn($date): bool
    {
        if (!$this->isActiveOn($date)) {
            return false;
        }

        $date = Carbon::parse($date);
        $dayOfWeek = $date->dayOfWeekIso; // 1 = Monday, 7 = Sunday

        return in_array($dayOfWeek, $this->work_days ?? []);
    }

    /**
     * Get formatted work days (e.g., "Пн, Вт, Ср, Чт, Пт").
     */
    public function getWorkDaysFormattedAttribute(): string
    {
        $dayNames = [
            1 => __('Mon'),
            2 => __('Tue'),
            3 => __('Wed'),
            4 => __('Thu'),
            5 => __('Fri'),
            6 => __('Sat'),
            7 => __('Sun'),
        ];

        $days = array_map(function ($day) use ($dayNames) {
            return $dayNames[$day] ?? $day;
        }, $this->work_days ?? []);

        return implode(', ', $days);
    }

    /**
     * Scope for current user's company.
     */
    public function scopeForCompany($query, $companyId)
    {
        return $query->where('created_by', $companyId);
    }
}
