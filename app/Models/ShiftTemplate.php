<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class ShiftTemplate extends Model
{
    use HasFactory;

    const PAY_TYPE_PER_SHIFT = 'per_shift';
    const PAY_TYPE_HOURLY = 'hourly';

    protected $fillable = [
        'work_place_id',
        'name',
        'start_time',
        'end_time',
        'break_minutes',
        'color',
        'pay_type',
        'pay_rate',
        'night_bonus_enabled',
        'night_bonus_percent',
        'created_by',
    ];

    protected $casts = [
        'break_minutes' => 'integer',
        'pay_rate' => 'decimal:2',
        'night_bonus_enabled' => 'boolean',
        'night_bonus_percent' => 'decimal:2',
    ];

    /**
     * Get the work place this shift template belongs to.
     */
    public function workPlace()
    {
        return $this->belongsTo(WorkPlace::class);
    }

    /**
     * Get all schedules using this template.
     */
    public function schedules()
    {
        return $this->hasMany(ShiftSchedule::class);
    }

    /**
     * Get all attendances for this template.
     */
    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }

    /**
     * Get the user who created this template.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Calculate shift duration in hours (accounting for overnight shifts).
     */
    public function getDurationInHours(): float
    {
        $start = Carbon::parse($this->start_time);
        $end = Carbon::parse($this->end_time);

        // If end time is before start time, it's an overnight shift
        if ($end->lt($start)) {
            $end->addDay();
        }

        $totalMinutes = $start->diffInMinutes($end);
        $workMinutes = $totalMinutes - $this->break_minutes;

        return round($workMinutes / 60, 2);
    }

    /**
     * Check if this is a night shift (starts after 20:00 or ends before 06:00).
     */
    public function isNightShift(): bool
    {
        $start = Carbon::parse($this->start_time);
        $end = Carbon::parse($this->end_time);

        // Night shift if starts after 20:00 or ends before 06:00
        return $start->hour >= 20 || $end->hour < 6;
    }

    /**
     * Get formatted time range.
     */
    public function getTimeRangeAttribute(): string
    {
        return Carbon::parse($this->start_time)->format('H:i') . ' - ' . Carbon::parse($this->end_time)->format('H:i');
    }

    /**
     * Scope for current user's company.
     */
    public function scopeForCompany($query, $companyId)
    {
        return $query->where('created_by', $companyId);
    }
}
