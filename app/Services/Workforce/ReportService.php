<?php

namespace App\Services\Workforce;

use App\Models\AttendanceCorrection;
use App\Models\AttendanceRecord;
use App\Models\AuditLog;
use App\Models\LeaveApplication;
use App\Models\LeaveLedgerEntry;
use App\Models\LeaveType;
use App\Models\PayrollRun;
use App\Models\PayrollRunItem;
use App\Models\Payslip;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * The reports listed in the BRD. Each one returns plain columns and rows so
 * the same data feeds the on-screen table and the CSV export. Every query is
 * narrowed to the employees the viewer is allowed to see.
 */
class ReportService
{
    public const GROUPS = ['attendance' => 'Attendance', 'leave' => 'Leave', 'payroll' => 'Payroll'];

    /** key => [title, group, description] */
    public const REPORTS = [
        'daily_attendance' => ['Daily attendance', 'attendance', 'Every employee\'s status, punches and hours for a day or range.'],
        'monthly_summary' => ['Monthly attendance summary', 'attendance', 'Present, absent, leave, late and hours per employee.'],
        'client_employees' => ['Client-wise employees', 'attendance', 'Employees by client with shift, department and manager.'],
        'working_hours' => ['Employee-wise working hours', 'attendance', 'Scheduled, worked, break and session hours per employee.'],
        'sessions_breaks' => ['System session & break report', 'attendance', 'Session, active, idle and break time per day.'],
        'late_early' => ['Late arrival & early departure', 'attendance', 'Days with a late arrival or an early exit.'],
        'exceptions' => ['Missing punch & exception report', 'attendance', 'Days that need review, with the reason.'],
        'overtime' => ['Overtime report', 'attendance', 'Overtime minutes and whether they are approved.'],
        'corrections_audit' => ['Attendance correction audit', 'attendance', 'Every correction request, who decided it and why.'],
        'leave_balances' => ['Leave balances', 'leave', 'Current balance and pending days per employee and type.'],
        'leave_utilization' => ['Leave utilisation', 'leave', 'Leave requests and days taken in a period.'],
        'payroll_summary' => ['Client-wise payroll summary', 'payroll', 'Totals for each payroll run.'],
        'payroll_statement' => ['Employee-wise payroll statement', 'payroll', 'Days, earnings, deductions and net pay per employee.'],
        'earnings_deductions' => ['Earnings & deductions', 'payroll', 'Every pay component per employee.'],
        'payslip_status' => ['Payslip publication status', 'payroll', 'Which payslips exist and which employees can see them.'],
        'approval_history' => ['Payroll approval history', 'payroll', 'Who calculated, reviewed, approved and published each run.'],
        'payroll_cost' => ['Monthly payroll cost by client', 'payroll', 'Gross, net and employer cost per client and month.'],
    ];

    public function __construct(private readonly AttendanceCalculator $calculator)
    {
    }

    public function permissionFor(string $key): array
    {
        return match (self::REPORTS[$key][1] ?? null) {
            'attendance' => ['attendance.export'],
            'leave' => ['leaves.view-team', 'leaves.view-branch', 'leaves.view-all-branches'],
            'payroll' => ['payroll.export'],
            default => [],
        };
    }

    public function available(User $viewer): array
    {
        $out = [];

        foreach (self::REPORTS as $key => [$title, $group, $description]) {
            if ($viewer->hasRole('admin') || $viewer->hasAnyPermission($this->permissionFor($key))) {
                $out[$group][] = ['key' => $key, 'title' => $title, 'description' => $description];
            }
        }

        return $out;
    }

    /** @return array{columns: array<int,string>, rows: array<int, array<int, mixed>>} */
    public function run(string $key, array $filters, User $viewer): array
    {
        $method = 'report' . str_replace(' ', '', ucwords(str_replace('_', ' ', $key)));
        $filters = $this->normalise($filters);

        if ((self::REPORTS[$key][1] ?? null) === 'attendance') {
            $this->ensureRecords($filters, $viewer);
        }

        return $this->{$method}($filters, $viewer);
    }

    /* ───────────────────────── attendance ───────────────────────── */

    private function reportDailyAttendance(array $f, User $v): array
    {
        $rows = $this->records($f, $v)->with('user:id,first_name,last_name,employee_id', 'client:id,name', 'shift:id,name')
            ->orderBy('attendance_date')->get()->sortBy(fn ($r) => $r->attendance_date->toDateString() . $r->user->employee_id)
            ->map(fn (AttendanceRecord $r) => [
                $r->attendance_date->format('d M Y'), $r->user->employee_id, $r->user->full_name, $r->client?->name, $r->shift?->name,
                AttendanceRecord::STATUSES[$r->status] ?? $r->status, $r->first_in?->format('h:i A'), $r->last_out?->format('h:i A'),
                $this->hours($r->worked_minutes), $r->late_minutes, $r->early_minutes, implode(', ', $r->flags ?? []),
            ])->values()->all();

        return ['columns' => ['Date', 'Employee ID', 'Name', 'Client', 'Shift', 'Status', 'In', 'Out', 'Worked (h)', 'Late (min)', 'Early (min)', 'Flags'], 'rows' => $rows];
    }

    private function reportMonthlySummary(array $f, User $v): array
    {
        $rows = $this->records($f, $v)->with('user:id,first_name,last_name,employee_id,department', 'client:id,name')->get()
            ->groupBy('user_id')->map(function (Collection $days) {
                $u = $days->first()->user;
                $count = fn ($s) => $days->where('status', $s)->count();

                return [
                    $u->employee_id, $u->full_name, $days->first()->client?->name, $count('present'), $count('half_day'), $count('absent'),
                    $count('leave'), $count('weekly_off'), $count('holiday'), $count('incomplete'), $days->where('is_late', true)->count(),
                    $days->where('is_early', true)->count(), $this->hours($days->sum('worked_minutes')),
                    $this->hours($days->where('overtime_approved', true)->sum('overtime_minutes')),
                ];
            })->sortBy(0)->values()->all();

        return ['columns' => ['Employee ID', 'Name', 'Client', 'Present', 'Half day', 'Absent', 'Leave', 'Weekly off', 'Holiday', 'Incomplete', 'Late', 'Early', 'Worked (h)', 'Approved OT (h)'], 'rows' => $rows];
    }

    private function reportClientEmployees(array $f, User $v): array
    {
        $rows = $this->employees($f, $v)->with('client:id,name', 'branch:id,name', 'manager:id,first_name,last_name')->get()
            ->sortBy(fn ($u) => ($u->client?->name ?? '~') . $u->employee_id)
            ->map(fn (User $u) => [
                $u->client?->name ?? '—', $u->employee_id, $u->full_name, $u->designation, $u->department, $u->branch?->name,
                $u->date_of_joining?->format('d M Y'), $u->employment_status, $u->manager?->full_name,
            ])->values()->all();

        return ['columns' => ['Client', 'Employee ID', 'Name', 'Designation', 'Department', 'Branch', 'Joined', 'Status', 'Reporting to'], 'rows' => $rows];
    }

    private function reportWorkingHours(array $f, User $v): array
    {
        $rows = $this->records($f, $v)->with('user:id,first_name,last_name,employee_id')->get()->groupBy('user_id')->map(function ($days) {
            $u = $days->first()->user;
            $worked = $days->sum('worked_minutes');
            $scheduled = $days->whereIn('status', ['present', 'half_day', 'absent', 'incomplete'])->sum('scheduled_minutes');

            return [$u->employee_id, $u->full_name, $this->hours($scheduled), $this->hours($worked), $this->hours($worked - $scheduled),
                $this->hours($days->sum('break_minutes')), $this->hours($days->sum('session_minutes')), $this->hours($days->sum('active_minutes')), $this->hours($days->sum('idle_minutes'))];
        })->sortBy(0)->values()->all();

        return ['columns' => ['Employee ID', 'Name', 'Scheduled (h)', 'Worked (h)', 'Variance (h)', 'Break (h)', 'Session (h)', 'Active (h)', 'Idle (h)'], 'rows' => $rows];
    }

    private function reportSessionsBreaks(array $f, User $v): array
    {
        $rows = $this->records($f, $v)->where(fn ($q) => $q->where('session_minutes', '>', 0)->orWhere('break_minutes', '>', 0))
            ->with('user:id,first_name,last_name,employee_id')->orderBy('attendance_date')->get()
            ->map(fn ($r) => [$r->attendance_date->format('d M Y'), $r->user->employee_id, $r->user->full_name, $r->session_minutes, $r->active_minutes, $r->idle_minutes, $r->break_minutes, $r->hasFlag('break_exceeded') ? 'Yes' : 'No', $r->hasFlag('short_session') ? 'Yes' : 'No'])
            ->values()->all();

        return ['columns' => ['Date', 'Employee ID', 'Name', 'Session (min)', 'Active (min)', 'Idle (min)', 'Break (min)', 'Break exceeded', 'Short session'], 'rows' => $rows];
    }

    private function reportLateEarly(array $f, User $v): array
    {
        $rows = $this->records($f, $v)->where(fn ($q) => $q->where('is_late', true)->orWhere('is_early', true))
            ->with('user:id,first_name,last_name,employee_id', 'shift:id,name')->orderBy('attendance_date')->get()
            ->map(fn ($r) => [$r->attendance_date->format('d M Y'), $r->user->employee_id, $r->user->full_name, $r->shift?->name, $r->first_in?->format('h:i A'), $r->late_minutes, $r->last_out?->format('h:i A'), $r->early_minutes])
            ->values()->all();

        return ['columns' => ['Date', 'Employee ID', 'Name', 'Shift', 'In', 'Late (min)', 'Out', 'Early (min)'], 'rows' => $rows];
    }

    private function reportExceptions(array $f, User $v): array
    {
        $rows = $this->records($f, $v)->whereIn('review_status', ['needs_review', 'reviewed'])
            ->with('user:id,first_name,last_name,employee_id', 'client:id,name')->orderBy('attendance_date')->get()
            ->filter(fn ($r) => array_intersect($r->flags ?? [], AttendanceCalculator::BLOCKING_FLAGS))
            ->map(fn ($r) => [$r->attendance_date->format('d M Y'), $r->user->employee_id, $r->user->full_name, $r->client?->name, $r->first_in?->format('h:i A'), $r->last_out?->format('h:i A'), implode(', ', $r->flags ?? []), $r->review_status === 'needs_review' ? 'Needs review' : 'Reviewed'])
            ->values()->all();

        return ['columns' => ['Date', 'Employee ID', 'Name', 'Client', 'In', 'Out', 'Exception', 'Review'], 'rows' => $rows];
    }

    private function reportOvertime(array $f, User $v): array
    {
        $rows = $this->records($f, $v)->where('overtime_minutes', '>', 0)->with('user:id,first_name,last_name,employee_id')->orderBy('attendance_date')->get()
            ->map(fn ($r) => [$r->attendance_date->format('d M Y'), $r->user->employee_id, $r->user->full_name, $r->overtime_minutes, $this->hours($r->overtime_minutes), $r->overtime_approved ? 'Approved' : 'Pending', $r->isLocked() ? 'Locked by payroll' : ''])
            ->values()->all();

        return ['columns' => ['Date', 'Employee ID', 'Name', 'OT (min)', 'OT (h)', 'Approval', 'Payroll'], 'rows' => $rows];
    }

    private function reportCorrectionsAudit(array $f, User $v): array
    {
        $ids = WorkforceScope::employees($v, 'attendance')->pluck('users.id');

        $rows = AttendanceCorrection::query()->whereIn('user_id', $ids)
            ->whereDate('attendance_date', '>=', $f['from'])->whereDate('attendance_date', '<=', $f['to'])
            ->when($f['client_id'], fn ($q, $c) => $q->where('client_id', $c))
            ->with('user:id,first_name,last_name,employee_id', 'requester:id,first_name,last_name', 'decider:id,first_name,last_name')
            ->orderBy('attendance_date')->get()
            ->map(fn ($c) => [$c->attendance_date->format('d M Y'), $c->user->employee_id, $c->user->full_name, $c->requested_in?->format('h:i A'), $c->requested_out?->format('h:i A'), $c->reason,
                ucfirst($c->status), $c->requester?->full_name, $c->decider?->full_name, $c->decided_at?->format('d M Y h:i A'), $c->decision_note, implode(' | ', $c->original_snapshot['punches'] ?? [])])
            ->all();

        return ['columns' => ['Date', 'Employee ID', 'Name', 'Requested in', 'Requested out', 'Reason', 'Status', 'Requested by', 'Decided by', 'Decided at', 'Note', 'Original punches'], 'rows' => $rows];
    }

    /* ───────────────────────── leave ───────────────────────── */

    private function reportLeaveBalances(array $f, User $v): array
    {
        $types = LeaveType::query()->active()->orderBy('name')->get();
        $ids = WorkforceScope::employees($v, 'leaves')->pluck('users.id');

        $employees = User::query()->whereIn('id', $ids)->whereNotNull('employee_id')->where('employment_status', 'active')
            ->when($f['client_id'], fn ($q, $c) => $q->where('client_id', $c))->orderBy('employee_id')->get();

        $ledger = LeaveLedgerEntry::query()->whereIn('user_id', $employees->pluck('id'))->get()->groupBy(['user_id', 'leave_type_id']);
        $pending = LeaveApplication::query()->whereIn('user_id', $employees->pluck('id'))->where('status', 'pending')->get()->groupBy(['user_id', 'leave_type_id']);

        $rows = [];
        foreach ($employees as $e) {
            foreach ($types as $t) {
                if ($t->client_id && $t->client_id !== $e->client_id) {
                    continue;
                }
                $entries = $ledger->get($e->id)?->get($t->id) ?? collect();
                if ($entries->isEmpty()) {
                    continue;
                }
                $rows[] = [$e->employee_id, $e->full_name, $t->name, round($entries->where('days', '>', 0)->sum('days'), 2), round(abs($entries->where('days', '<', 0)->sum('days')), 2), round($entries->sum('days'), 2), round(($pending->get($e->id)?->get($t->id) ?? collect())->sum('total_days'), 2)];
            }
        }

        return ['columns' => ['Employee ID', 'Name', 'Leave type', 'Credited', 'Used / lapsed', 'Balance', 'Pending'], 'rows' => $rows];
    }

    private function reportLeaveUtilization(array $f, User $v): array
    {
        $ids = WorkforceScope::employees($v, 'leaves')->pluck('users.id');

        $rows = LeaveApplication::query()->whereIn('user_id', $ids)
            ->whereDate('start_date', '<=', $f['to'])->whereDate('end_date', '>=', $f['from'])
            ->when($f['client_id'], fn ($q, $c) => $q->where('client_id', $c))
            ->with('user:id,first_name,last_name,employee_id', 'leaveType:id,name')->orderBy('start_date')->get()
            ->map(fn ($a) => [$a->user->employee_id, $a->user->full_name, $a->leaveType?->name, $a->start_date->format('d M Y'), $a->end_date->format('d M Y'), $a->total_days, ucfirst($a->status), $a->reason])
            ->all();

        return ['columns' => ['Employee ID', 'Name', 'Leave type', 'From', 'To', 'Days', 'Status', 'Reason'], 'rows' => $rows];
    }

    /* ───────────────────────── payroll ───────────────────────── */

    private function runs(array $f, User $v)
    {
        return PayrollRun::query()
            ->when($f['client_id'], fn ($q, $c) => $q->where('client_id', $c))
            ->when($f['run_id'], fn ($q, $r) => $q->where('id', $r))
            ->whereHas('period', fn ($p) => $p->whereDate('start_date', '<=', $f['to'])->whereDate('end_date', '>=', $f['from']));
    }

    private function items(array $f, User $v)
    {
        $ids = WorkforceScope::employees($v, 'payroll')->pluck('users.id');

        return PayrollRunItem::query()->whereIn('user_id', $ids)->whereIn('payroll_run_id', $this->runs($f, $v)->select('id'))
            ->when($f['search'], fn ($q, $s) => $q->whereHas('user', fn ($u) => $u->where('first_name', 'like', "%{$s}%")->orWhere('last_name', 'like', "%{$s}%")->orWhere('employee_id', 'like', "%{$s}%")))
            ->with('user:id,first_name,last_name,employee_id', 'run.period:id,name', 'run.client:id,name');
    }

    private function reportPayrollSummary(array $f, User $v): array
    {
        $rows = $this->runs($f, $v)->with('client:id,name', 'period:id,name')->latest('id')->get()
            ->map(fn ($r) => [$r->run_number, $r->client->name, $r->period->name, $r->label(), $r->employee_count, $r->total_gross, $r->total_deductions, $r->total_net, $r->total_employer_cost])->all();

        return ['columns' => ['Run', 'Client', 'Period', 'Status', 'Employees', 'Gross', 'Deductions', 'Net pay', 'Employer cost'], 'rows' => $rows];
    }

    private function reportPayrollStatement(array $f, User $v): array
    {
        $rows = $this->items($f, $v)->get()->sortBy(fn ($i) => $i->run->run_number . $i->user->employee_id)
            ->map(fn ($i) => [$i->run->run_number, $i->run->period->name, $i->user->employee_id, $i->user->full_name, $i->run->client->name, $i->payable_days, $i->lop_days, $i->paid_leave_days, $i->unpaid_leave_days, $i->overtime_minutes / 60, $i->gross_earnings, $i->total_deductions, $i->net_pay, $i->isHeld() ? 'Held: ' . $i->hold_reason : ''])
            ->values()->all();

        return ['columns' => ['Run', 'Period', 'Employee ID', 'Name', 'Client', 'Paid days', 'LOP days', 'Paid leave', 'Unpaid leave', 'OT (h)', 'Gross', 'Deductions', 'Net pay', 'Note'], 'rows' => $rows];
    }

    private function reportEarningsDeductions(array $f, User $v): array
    {
        $rows = [];

        foreach ($this->items($f, $v)->get() as $i) {
            foreach (['earnings' => 'Earning', 'deductions' => 'Deduction', 'employer' => 'Employer contribution'] as $field => $label) {
                foreach ($i->{$field} ?? [] as $line) {
                    $rows[] = [$i->run->run_number, $i->run->period->name, $i->user->employee_id, $i->user->full_name, $label, $line['name'], $line['amount'], $line['note'] ?? ''];
                }
            }
        }

        return ['columns' => ['Run', 'Period', 'Employee ID', 'Name', 'Type', 'Component', 'Amount', 'Note'], 'rows' => $rows];
    }

    private function reportPayslipStatus(array $f, User $v): array
    {
        $ids = WorkforceScope::employees($v, 'payroll')->pluck('users.id');

        $rows = Payslip::query()->whereIn('user_id', $ids)->whereIn('payroll_run_id', $this->runs($f, $v)->select('id'))
            ->with('user:id,first_name,last_name,employee_id', 'run:id,run_number', 'publisher:id,first_name,last_name')->latest('id')->get()
            ->map(fn ($p) => [$p->payslip_number, $p->user->employee_id, $p->user->full_name, $p->snapshot['period']['name'] ?? '', $p->net_pay, $p->generated_at->format('d M Y'), $p->voided_at ? 'Voided' : ($p->published_at ? 'Published' : 'Hidden from employee'), $p->published_at?->format('d M Y h:i A'), $p->publisher?->full_name])
            ->all();

        return ['columns' => ['Payslip', 'Employee ID', 'Name', 'Period', 'Net pay', 'Generated', 'Status', 'Published at', 'Published by'], 'rows' => $rows];
    }

    private function reportApprovalHistory(array $f, User $v): array
    {
        $runs = $this->runs($f, $v)->with('client:id,name', 'period:id,name')->get()->keyBy('id');

        $rows = AuditLog::query()->where('model_type', (new PayrollRun)->getMorphClass())->whereIn('model_id', $runs->keys())
            ->where('event', 'like', 'payroll.%')->with('user:id,first_name,last_name')->orderBy('created_at')->get()
            ->map(function ($l) use ($runs) {
                $run = $runs->get($l->model_id);

                return [$run->run_number, $run->client->name, $run->period->name, str_replace(['payroll.', '_'], ['', ' '], $l->event), $l->user?->full_name, $l->created_at->format('d M Y h:i A'), $l->new_values['note'] ?? ''];
            })->all();

        return ['columns' => ['Run', 'Client', 'Period', 'Event', 'By', 'At', 'Note'], 'rows' => $rows];
    }

    private function reportPayrollCost(array $f, User $v): array
    {
        $rows = $this->runs($f, $v)->whereIn('status', ['approved', 'payslips_published', 'closed'])->with('client:id,name', 'period:id,name,start_date')->get()
            ->groupBy(fn ($r) => $r->client->name . '|' . $r->period->start_date->format('Y-m'))
            ->map(function ($runs, $key) {
                [$client, $month] = explode('|', $key);

                return [$client, Carbon::createFromFormat('Y-m', $month)->format('F Y'), $runs->sum('employee_count'), round($runs->sum('total_gross'), 2), round($runs->sum('total_net'), 2), round($runs->sum('total_employer_cost'), 2)];
            })->values()->all();

        return ['columns' => ['Client', 'Month', 'Employees', 'Gross', 'Net pay', 'Total employer cost'], 'rows' => $rows];
    }

    /* ───────────────────────── helpers ───────────────────────── */

    private function normalise(array $f): array
    {
        $from = !empty($f['from']) ? Carbon::parse($f['from']) : today()->startOfMonth();
        $to = !empty($f['to']) ? Carbon::parse($f['to']) : today();

        return [
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
            'client_id' => $f['client_id'] ?? null,
            'department' => $f['department'] ?? null,
            'search' => trim((string) ($f['search'] ?? '')) ?: null,
            'run_id' => $f['run_id'] ?? null,
        ];
    }

    private function employees(array $f, User $v)
    {
        return WorkforceScope::employees($v, 'attendance')->whereNotNull('employee_id')
            ->when($f['client_id'], fn ($q, $c) => $q->where('client_id', $c))
            ->when($f['department'], fn ($q, $d) => $q->where('department', $d))
            ->when($f['search'], fn ($q, $s) => $q->where(fn ($w) => $w->where('first_name', 'like', "%{$s}%")->orWhere('last_name', 'like', "%{$s}%")->orWhere('employee_id', 'like', "%{$s}%")));
    }

    private function records(array $f, User $v)
    {
        return AttendanceRecord::query()
            ->whereIn('user_id', $this->employees($f, $v)->select('users.id'))
            ->whereDate('attendance_date', '>=', $f['from'])->whereDate('attendance_date', '<=', $f['to']);
    }

    /**
     * Attendance records are derived, so a day nobody has opened yet may not exist.
     * Fill those in (past days only, bounded) so a report never silently omits people.
     */
    private function ensureRecords(array $f, User $v): void
    {
        $from = Carbon::parse($f['from'])->startOfDay();
        $to = Carbon::parse($f['to'])->startOfDay();
        $to = $to->gt(today()) ? today() : $to;

        if ($from->gt($to) || $from->diffInDays($to) > 92) {
            return;
        }

        $employees = $this->employees($f, $v)->where('employment_status', 'active')->whereNotNull('client_id')->limit(500)->get();

        $existing = AttendanceRecord::query()
            ->whereIn('user_id', $employees->pluck('id'))
            ->whereDate('attendance_date', '>=', $from->toDateString())->whereDate('attendance_date', '<=', $to->toDateString())
            ->get(['user_id', 'attendance_date'])
            ->groupBy('user_id')
            ->map(fn ($rows) => $rows->map(fn ($r) => $r->attendance_date->toDateString())->all());

        foreach ($employees as $employee) {
            $have = $existing->get($employee->id, []);

            for ($day = $from->copy(); $day->lte($to); $day->addDay()) {
                if ((!$employee->date_of_joining || $employee->date_of_joining->lte($day)) && !in_array($day->toDateString(), $have, true)) {
                    $this->calculator->calculate($employee, $day->copy());
                }
            }
        }
    }

    private function hours(int|float $minutes): float
    {
        return round($minutes / 60, 2);
    }
}
