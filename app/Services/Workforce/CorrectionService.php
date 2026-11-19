<?php

namespace App\Services\Workforce;

use App\Models\AttendanceCorrection;
use App\Models\AttendancePunch;
use App\Models\AttendanceRecord;
use App\Models\AuditLog;
use App\Models\User;
use App\Notifications\WorkforceAlert;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Missing-punch and regularisation requests.
 *
 * Approving one never edits existing punches: it adds manual punches that
 * point back at the request, and the daily record is recalculated from the
 * full set. Every step is written to the audit log.
 */
class CorrectionService
{
    public const WINDOW_DAYS = 31;

    public function __construct(
        private readonly PunchService $punches,
        private readonly AttendanceCalculator $calculator,
    ) {
    }

    public function request(
        User $employee,
        CarbonInterface $date,
        ?CarbonInterface $in,
        ?CarbonInterface $out,
        string $reason,
        User $requestedBy,
    ): AttendanceCorrection {
        $date = Carbon::instance($date)->startOfDay();

        if ($date->gt(today())) {
            throw new InvalidArgumentException('You cannot correct a future date.');
        }

        if ($date->lt(today()->subDays(self::WINDOW_DAYS))) {
            throw new InvalidArgumentException('Corrections are only accepted for the last ' . self::WINDOW_DAYS . ' days.');
        }

        if (!$in && !$out) {
            throw new InvalidArgumentException('Enter the punch-in time, the punch-out time, or both.');
        }

        if ($in && $out && $out->lte($in)) {
            throw new InvalidArgumentException('Punch-out must be after punch-in.');
        }

        $record = AttendanceRecord::query()
            ->where('user_id', $employee->id)
            ->whereDate('attendance_date', $date->toDateString())
            ->first();

        if ($record?->isLocked()) {
            throw new InvalidArgumentException('This day is locked by an approved payroll run. Ask payroll to raise an adjustment instead.');
        }

        $duplicate = AttendanceCorrection::query()
            ->where('user_id', $employee->id)
            ->whereDate('attendance_date', $date->toDateString())
            ->where('status', AttendanceCorrection::PENDING)
            ->exists();

        if ($duplicate) {
            throw new InvalidArgumentException('There is already a pending request for this date.');
        }

        $correction = AttendanceCorrection::create([
            'user_id' => $employee->id,
            'client_id' => $employee->client_id,
            'attendance_date' => $date->toDateString(),
            'requested_in' => $in,
            'requested_out' => $out,
            'reason' => $reason,
            'original_snapshot' => $this->snapshot($employee, $date),
            'status' => AttendanceCorrection::PENDING,
            'requested_by' => $requestedBy->id,
        ]);

        AuditLog::record('attendance_correction.requested', $correction, [], $correction->only(['attendance_date', 'requested_in', 'requested_out', 'reason']), $requestedBy->id);

        $this->notifyApprovers($employee, $correction);

        return $correction;
    }

    public function approve(AttendanceCorrection $correction, User $by, ?string $note = null): AttendanceCorrection
    {
        $this->assertPending($correction);

        $employee = $correction->user;
        $date = Carbon::parse($correction->attendance_date)->startOfDay();

        $record = AttendanceRecord::query()
            ->where('user_id', $employee->id)
            ->whereDate('attendance_date', $date->toDateString())
            ->first();

        if ($record?->isLocked()) {
            throw new InvalidArgumentException('This day is now locked by an approved payroll run.');
        }

        DB::transaction(function () use ($correction, $by, $note, $employee, $date) {
            $reason = 'Correction #' . $correction->id . ': ' . $correction->reason;

            if ($correction->requested_in) {
                $this->punchFor($employee, 'in', $correction->requested_in, $date, $reason, $by, $correction->id);
            }

            if ($correction->requested_out) {
                $this->punchFor($employee, 'out', $correction->requested_out, $date, $reason, $by, $correction->id);
            }

            $correction->update([
                'status' => AttendanceCorrection::APPROVED,
                'decided_by' => $by->id,
                'decided_at' => now(),
                'decision_note' => $note,
            ]);

            $record = $this->calculator->calculate($employee, $date);

            // The approver has just looked at this day, so its exceptions count as reviewed.
            if ($record->review_status === 'needs_review') {
                $record->update([
                    'review_status' => 'reviewed',
                    'reviewed_by' => $by->id,
                    'reviewed_at' => now(),
                    'review_note' => 'Resolved by correction #' . $correction->id,
                ]);
            }

            AuditLog::record(
                'attendance_correction.approved',
                $correction,
                $correction->original_snapshot ?? [],
                $this->snapshot($employee, $date),
                $by->id
            );
        });

        $employee->notify(new WorkforceAlert(
            'attendance',
            'Your attendance correction for ' . $date->format('d M Y') . ' was approved.',
            route('employee.attendance')
        ));

        return $correction->refresh();
    }

    public function reject(AttendanceCorrection $correction, User $by, string $note): AttendanceCorrection
    {
        $this->assertPending($correction);

        $correction->update([
            'status' => AttendanceCorrection::REJECTED,
            'decided_by' => $by->id,
            'decided_at' => now(),
            'decision_note' => $note,
        ]);

        AuditLog::record('attendance_correction.rejected', $correction, [], ['note' => $note], $by->id);

        $correction->user->notify(new WorkforceAlert(
            'attendance',
            'Your attendance correction for ' . $correction->attendance_date->format('d M Y') . ' was rejected: ' . $note,
            route('employee.attendance')
        ));

        return $correction->refresh();
    }

    public function cancel(AttendanceCorrection $correction, User $by): AttendanceCorrection
    {
        $this->assertPending($correction);

        $correction->update(['status' => AttendanceCorrection::CANCELLED, 'decided_by' => $by->id, 'decided_at' => now()]);

        AuditLog::record('attendance_correction.cancelled', $correction, [], [], $by->id);

        return $correction->refresh();
    }

    private function punchFor(User $employee, string $type, CarbonInterface $at, CarbonInterface $date, string $reason, User $by, int $correctionId): void
    {
        $punch = $this->punches->manual($employee, $type, $at, $reason, $by, $correctionId, recalculate: false);

        // Keep the punch on the day being corrected even for overnight times.
        if ($punch->attendance_date->toDateString() !== $date->toDateString()) {
            $punch->update(['attendance_date' => $date->toDateString()]);
        }
    }

    private function assertPending(AttendanceCorrection $correction): void
    {
        if (!$correction->isPending()) {
            throw new InvalidArgumentException('This request has already been ' . $correction->status . '.');
        }
    }

    private function snapshot(User $employee, CarbonInterface $date): array
    {
        $record = AttendanceRecord::query()
            ->where('user_id', $employee->id)
            ->whereDate('attendance_date', $date->toDateString())
            ->first();

        return [
            'record' => $record?->only(['status', 'first_in', 'last_out', 'worked_minutes', 'payable_fraction', 'flags']),
            'punches' => AttendancePunch::query()
                ->where('user_id', $employee->id)
                ->whereDate('attendance_date', $date->toDateString())
                ->valid()
                ->orderBy('punched_at')
                ->get(['type', 'source', 'punched_at'])
                ->map(fn ($p) => $p->type . '@' . $p->punched_at->format('H:i') . ' (' . $p->source . ')')
                ->all(),
        ];
    }

    /** Reporting manager first; HR with correction rights when there is no manager. */
    private function notifyApprovers(User $employee, AttendanceCorrection $correction): void
    {
        $approvers = collect();

        if ($employee->reporting_to && $employee->manager) {
            $approvers->push($employee->manager);
        }

        if ($approvers->isEmpty()) {
            $approvers = User::permission('attendance.approve-regularization')
                ->where('id', '!=', $employee->id)
                ->when($employee->branch_id, fn ($q) => $q->where(fn ($w) => $w->where('branch_id', $employee->branch_id)->orWhereNull('branch_id')))
                ->get();
        }

        foreach ($approvers->unique('id') as $approver) {
            $approver->notify(new WorkforceAlert(
                'attendance',
                $employee->full_name . ' requested an attendance correction for ' . $correction->attendance_date->format('d M Y') . '.',
                route('workforce.attendance.corrections')
            ));
        }
    }
}
