<?php

namespace App\Console\Commands;

use App\Models\CashPeriod;
use Carbon\Carbon;
use Illuminate\Console\Command;

class FreezeCashPeriods extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cashbox:freeze-periods';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Freeze cash periods from past months';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $now = Carbon::now();
        $currentYear = $now->year;
        $currentMonth = $now->month;

        // Find all unfrozen periods that are not the current month
        $periodsToFreeze = CashPeriod::where('is_frozen', false)
            ->where(function ($query) use ($currentYear, $currentMonth) {
                // Periods from previous years
                $query->where('year', '<', $currentYear)
                    // Or periods from current year but previous months
                    ->orWhere(function ($q) use ($currentYear, $currentMonth) {
                        $q->where('year', '=', $currentYear)
                          ->where('month', '<', $currentMonth);
                    });
            })
            ->get();

        $frozenCount = 0;

        foreach ($periodsToFreeze as $period) {
            $period->update(['is_frozen' => true]);
            $frozenCount++;
            
            $this->info("Frozen period: {$period->name} (Company ID: {$period->created_by})");
        }

        if ($frozenCount === 0) {
            $this->info('No periods to freeze.');
        } else {
            $this->info("Successfully frozen {$frozenCount} period(s).");
        }

        return Command::SUCCESS;
    }
}
