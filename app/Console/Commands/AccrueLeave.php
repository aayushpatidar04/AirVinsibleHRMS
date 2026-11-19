<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\Workforce\LeaveService;
use Carbon\Carbon;
use Illuminate\Console\Command;

/**
 * Credits leave that has fallen due and, in January, applies the year-end
 * carry-forward rule. Every credit carries a period key, so running this
 * twice (or catching up after downtime) never double-credits anyone.
 *
 *   php artisan leave:accrue
 *   php artisan leave:accrue --date=2026-12-31 --rollover=2026
 */
class AccrueLeave extends Command
{
    protected $signature = 'leave:accrue {--date= : Accrue up to this date (default: today)} {--rollover= : Apply year-end rules for this year}';

    protected $description = 'Post due leave accruals and year-end carry-forward';

    public function handle(LeaveService $leave): int
    {
        $date = $this->option('date') ? Carbon::parse($this->option('date')) : today();
        $rollover = $this->option('rollover') ? (int) $this->option('rollover') : ($date->month === 1 ? $date->year - 1 : null);

        $credited = $lapsed = 0;

        User::query()->whereNotNull('employee_id')->where('employment_status', 'active')->whereNotNull('date_of_joining')
            ->chunkById(200, function ($employees) use ($leave, $date, $rollover, &$credited, &$lapsed) {
                foreach ($employees as $employee) {
                    $credited += $leave->accrue($employee, $date);

                    if ($rollover) {
                        $lapsed += $leave->rollOver($employee, $rollover);
                    }
                }
            });

        $this->info("Posted {$credited} accrual(s)" . ($rollover ? " and {$lapsed} year-end lapse(s) for {$rollover}" : '') . '.');

        return self::SUCCESS;
    }
}
