<?php

namespace App\Services;

use App\Models\Hotel;
use App\Models\Room;
use App\Models\RoomAssignment;
use App\Models\Worker;
use App\Models\WorkAssignment;
use App\Models\CashPeriod;
use App\Models\CashTransaction;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class JobsiDashboardService
{
    /**
     * Get hotel/accommodation statistics
     */
    public function getHotelStats(?int $hotelId = null): array
    {
        $companyId = Auth::user()->creatorId();
        
        $roomsQuery = Room::whereHas('hotel', function ($q) use ($companyId) {
            $q->where('created_by', $companyId);
        });
        
        if ($hotelId) {
            $roomsQuery->where('hotel_id', $hotelId);
        }
        
        $rooms = $roomsQuery->with('currentAssignments')->get();
        
        $totalCapacity = $rooms->sum('capacity');
        $occupiedSpots = 0;
        $paysSelf = 0;
        $paysFree = 0;
        
        foreach ($rooms as $room) {
            foreach ($room->currentAssignments as $assignment) {
                $occupiedSpots++;
                if ($assignment->payment_type === RoomAssignment::PAYMENT_WORKER) {
                    $paysSelf++;
                } else {
                    $paysFree++;
                }
            }
        }
        
        $freeSpots = $totalCapacity - $occupiedSpots;
        
        return [
            'total_spots' => $totalCapacity,
            'free_spots' => $freeSpots,
            'pays_self' => $paysSelf,
            'pays_free' => $paysFree,
        ];
    }
    
    /**
     * Get workplace statistics (hired/fired workers)
     */
    public function getWorkplaceStats(?int $workplaceId = null, ?string $month = null): array
    {
        $companyId = Auth::user()->creatorId();
        
        // Default to current month
        if (!$month) {
            $month = Carbon::now()->format('Y-m');
        }
        
        $startDate = Carbon::parse($month . '-01')->startOfMonth();
        $endDate = Carbon::parse($month . '-01')->endOfMonth();
        
        $query = WorkAssignment::where('created_by', $companyId);
        
        if ($workplaceId) {
            $query->where('work_place_id', $workplaceId);
        }
        
        // Hired this month (started_at in this month)
        $hired = (clone $query)
            ->whereBetween('started_at', [$startDate, $endDate])
            ->count();
        
        // Fired this month (ended_at in this month)
        $fired = (clone $query)
            ->whereBetween('ended_at', [$startDate, $endDate])
            ->count();
        
        // Calculate fluctuation: (fired / hired) * 100
        $fluctuation = $hired > 0 ? round(($fired / $hired) * 100, 1) : 0;
        
        return [
            'hired' => $hired,
            'fired' => $fired,
            'fluctuation' => $fluctuation,
        ];
    }
    
    /**
     * Get cashbox statistics for a month
     */
    public function getCashboxStats(?string $month = null): array
    {
        $companyId = Auth::user()->creatorId();
        
        if (!$month) {
            $year = (int) date('Y');
            $monthNum = (int) date('m');
        } else {
            $date = Carbon::parse($month . '-01');
            $year = $date->year;
            $monthNum = $date->month;
        }
        
        $period = CashPeriod::where('created_by', $companyId)
            ->where('year', $year)
            ->where('month', $monthNum)
            ->first();
        
        if (!$period) {
            return [
                'balance' => 0,
                'salaries' => 0,
                'transport' => 0,
                'other' => 0,
                'total_deposited' => 0,
            ];
        }
        
        // Get all transactions for this period
        $transactions = CashTransaction::where('cash_period_id', $period->id)->get();
        
        // Calculate total deposited from actual deposit transactions (more reliable than period field)
        $totalDeposited = $transactions
            ->filter(fn($t) => $t->type === CashTransaction::TYPE_DEPOSIT)
            ->sum('amount');
        
        // Calculate salaries (distribution_type = salary or self_salary)
        $salaries = $transactions
            ->filter(function ($t) {
                return $t->type === CashTransaction::TYPE_SELF_SALARY ||
                    ($t->type === CashTransaction::TYPE_DISTRIBUTION && 
                     $t->distribution_type === CashTransaction::DISTRIBUTION_TYPE_SALARY);
            })
            ->sum('amount');
        
        // Transport expenses (by task containing 'транспорт' or similar)
        $transport = $transactions
            ->filter(function ($t) {
                if ($t->type !== CashTransaction::TYPE_DISTRIBUTION) return false;
                $task = strtolower($t->task ?? '');
                $comment = strtolower($t->comment ?? '');
                return str_contains($task, 'транспорт') || 
                       str_contains($comment, 'транспорт') ||
                       str_contains($task, 'transport') ||
                       str_contains($comment, 'бензин') ||
                       str_contains($comment, 'топливо');
            })
            ->sum('amount');
        
        // Other expenses (distributions that are not salary and not transport)
        $otherDistributions = $transactions
            ->filter(function ($t) {
                return $t->type === CashTransaction::TYPE_DISTRIBUTION &&
                    $t->distribution_type !== CashTransaction::DISTRIBUTION_TYPE_SALARY;
            })
            ->sum('amount');
        
        $other = max(0, $otherDistributions - $transport);
        
        // Calculate current balance (cannot be negative)
        $totalSpent = $salaries + $transport + $other;
        $balance = max(0, $totalDeposited - $totalSpent);
        
        return [
            'balance' => $balance,
            'salaries' => $salaries,
            'transport' => $transport,
            'other' => $other,
            'total_deposited' => $totalDeposited,
        ];
    }
    
    /**
     * Get monthly chart data for cashbox (last 12 months)
     */
    public function getCashboxChartData(): array
    {
        $companyId = Auth::user()->creatorId();
        $data = [];
        
        for ($i = 11; $i >= 0; $i--) {
            $date = Carbon::now()->subMonths($i);
            $year = $date->year;
            $month = $date->month;
            
            $period = CashPeriod::where('created_by', $companyId)
                ->where('year', $year)
                ->where('month', $month)
                ->first();
            
            $totalTurnover = 0;
            $endBalance = 0;
            
            if ($period) {
                $totalTurnover = $period->total_deposited;
                
                // Calculate end balance
                $spent = CashTransaction::where('cash_period_id', $period->id)
                    ->whereIn('type', [
                        CashTransaction::TYPE_DISTRIBUTION,
                        CashTransaction::TYPE_SELF_SALARY,
                    ])
                    ->sum('amount');
                
                $endBalance = $totalTurnover - $spent;
            }
            
            $data[] = [
                'month' => $month,
                'label' => $date->format('M'),
                'turnover' => $totalTurnover,
                'balance' => max(0, $endBalance),
            ];
        }
        
        return $data;
    }
    
    /**
     * Get list of hotels for filter dropdown
     */
    public function getHotels(): \Illuminate\Database\Eloquent\Collection
    {
        return Hotel::where('created_by', Auth::user()->creatorId())
            ->orderBy('name')
            ->get();
    }
    
    /**
     * Get list of workplaces for filter dropdown
     */
    public function getWorkplaces(): \Illuminate\Database\Eloquent\Collection
    {
        return \App\Models\WorkPlace::where('created_by', Auth::user()->creatorId())
            ->orderBy('name')
            ->get();
    }
    
    /**
     * Get available months for filtering
     */
    public function getAvailableMonths(): array
    {
        $months = [];
        $now = Carbon::now();
        
        for ($i = 0; $i < 12; $i++) {
            $date = $now->copy()->subMonths($i);
            $months[] = [
                'value' => $date->format('Y-m'),
                'label' => $i === 0 ? __('Актуальный месяц') : 
                          ($i === 1 ? __('Предыдущий месяц') : __($date->format('F'))),
            ];
        }
        
        return $months;
    }
}
