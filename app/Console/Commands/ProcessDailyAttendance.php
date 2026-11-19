<?php

namespace App\Console\Commands;

use App\Models\AttendanceRecord;
use App\Models\User;
use App\Notifications\WorkforceAlert;
use App\Services\Workforce\AttendanceCalculator;
use Carbon\Carbon;
use Illuminate\Console\Command;

/**
 * Closes the books on a day: recalculates every active employee so absences,
 * incomplete punches and late arrivals are on record, and tells people about
 * missing punches while they can still ask for a correction.
 *
 *   php artisan attendance:process-daily            (yesterday)
 *   php artisan attendance:process-daily --date=2026-03-03
 */
class ProcessDailyAttendance extends Command
{
    protected $signature = 'attendance:process-daily {--date= : Day to process (default: yesterday)}';

    protected $description = 'Calculate attendance for the previous day and alert on missing punches';

    public function handle(AttendanceCalculator $calculator): int
    {
        $date = $this->option('date') ? Carbon::parse($this->option('date'))->startOfDay() : today()->subDay();
        $processed = $flagged = 0;

        User::query()
            ->whereNotNull('employee_id')->whereNotNull('client_id')
            ->where('employment_status', 'active')
            ->where(fn ($q) => $q->whereNull('date_of_joining')->orWhereDate('date_of_joining', '<=', $date->toDateString()))
            ->with('manager')
            ->chunkById(200, function ($employees) use ($calculator, $date, &$processed, &$flagged) {
                foreach ($employees as $employee) {
                    $before = AttendanceRecord::query()->where('user_id', $employee->id)
                        ->whereDate('attendance_date', $date->toDateString())->value('review_status');

                    $record = $calculator->calculate($employee, $date->copy());
                    $processed++;

                    // Alert once, the first time a day turns into an exception.
                    if ($record->review_status === 'needs_review' && $before !== 'needs_review' && $record->status === 'incomplete') {
                        $flagged++;
                        $this->notifyMissingPunch($employee, $record);
                    }
                }
            });

        $this->info("Processed {$processed} employee(s) for {$date->toDateString()}; {$flagged} missing-punch alert(s).");

        return self::SUCCESS;
    }

    private function notifyMissingPunch(User $employee, AttendanceRecord $record): void
    {
        $day = $record->attendance_date->format('d M');

        $employee->notify(new WorkforceAlert(
            'attendance',
            "Your attendance for {$day} is incomplete. Please request a correction.",
            route('employee.attendance', ['month' => $record->attendance_date->format('Y-m')])
        ));

        $employee->manager?->notify(new WorkforceAlert(
            'attendance',
            "{$employee->full_name} has a missing punch on {$day}.",
            route('workforce.attendance.exceptions')
        ));
    }
}
