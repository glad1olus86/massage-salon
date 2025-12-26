<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\ShiftTemplate;
use Carbon\Carbon;

class ShiftCalculationService
{
    /**
     * Calculate shift duration in hours (accounting for overnight shifts).
     */
    public function calculateShiftDuration(ShiftTemplate $template): float
    {
        $start = Carbon::parse($template->start_time);
        $end = Carbon::parse($template->end_time);

        // If end time is before start time, it's an overnight shift
        if ($end->lt($start)) {
            $end->addDay();
        }

        $totalMinutes = $start->diffInMinutes($end);
        $workMinutes = $totalMinutes - $template->break_minutes;

        return round($workMinutes / 60, 2);
    }

    /**
     * Calculate pay for an attendance record.
     */
    public function calculatePay(Attendance $attendance, ?ShiftTemplate $template = null): float
    {
        $template = $template ?? $attendance->shiftTemplate;
        
        if (!$template || !$template->pay_rate) {
            return 0;
        }

        $basePay = 0;

        if ($template->pay_type === ShiftTemplate::PAY_TYPE_PER_SHIFT) {
            // Fixed rate per shift
            $basePay = (float) $template->pay_rate;
        } else {
            // Hourly rate
            $hours = $attendance->worked_hours ?? $this->calculateShiftDuration($template);
            $basePay = $hours * (float) $template->pay_rate;
        }

        // Apply night bonus if enabled
        if ($template->night_bonus_enabled && $this->isNightShift($template)) {
            $basePay = $this->applyNightBonus($basePay, (float) $template->night_bonus_percent);
        }

        return round($basePay, 2);
    }

    /**
     * Check if a shift is considered a night shift.
     * Night shift: starts after 20:00 or ends before 06:00
     */
    public function isNightShift(ShiftTemplate $template): bool
    {
        $start = Carbon::parse($template->start_time);
        $end = Carbon::parse($template->end_time);

        // Night shift if starts after 20:00
        if ($start->hour >= 20) {
            return true;
        }

        // Night shift if ends before 06:00 (and it's an overnight shift)
        if ($end->hour < 6 && $end->lt(Carbon::parse($template->start_time))) {
            return true;
        }

        // Night shift if any part is between 22:00 and 06:00
        if ($start->hour >= 22 || $end->hour <= 6) {
            return true;
        }

        return false;
    }

    /**
     * Apply night bonus to base pay.
     */
    public function applyNightBonus(float $basePay, float $bonusPercent): float
    {
        return $basePay * (1 + $bonusPercent / 100);
    }

    /**
     * Calculate total pay for a worker in a period.
     */
    public function calculateTotalPay(int $workerId, $startDate, $endDate): array
    {
        $attendances = Attendance::where('worker_id', $workerId)
            ->whereBetween('date', [$startDate, $endDate])
            ->whereIn('status', [Attendance::STATUS_PRESENT, Attendance::STATUS_LATE])
            ->with('shiftTemplate')
            ->get();

        $totalPay = 0;
        $totalHours = 0;
        $totalShifts = 0;

        foreach ($attendances as $attendance) {
            $totalPay += $this->calculatePay($attendance);
            $totalHours += $attendance->worked_hours ?? 0;
            $totalShifts++;
        }

        return [
            'total_pay' => round($totalPay, 2),
            'total_hours' => round($totalHours, 2),
            'total_shifts' => $totalShifts,
        ];
    }

    /**
     * Calculate overtime hours.
     */
    public function calculateOvertime(Attendance $attendance, ?ShiftTemplate $template = null): float
    {
        $template = $template ?? $attendance->shiftTemplate;
        
        if (!$template || !$attendance->worked_hours) {
            return 0;
        }

        $scheduledHours = $this->calculateShiftDuration($template);
        $overtime = $attendance->worked_hours - $scheduledHours;

        return max(0, round($overtime, 2));
    }
}
