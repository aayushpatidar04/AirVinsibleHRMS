<?php

namespace App\Services\Workforce;

use App\Models\AttendanceCorrection;
use App\Models\AttendancePolicy;
use App\Models\AttendanceRecord;
use App\Models\EmployeeSalaryStructure;
use App\Models\LeaveApplication;
use App\Models\PayrollAdjustment;
use App\Models\PayrollPeriod;
use App\Models\PayrollSetting;
use App\Models\SalaryStructureItem;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Calculates one employee's pay for a payroll period.
 *
 * The engine only reads approved facts (attendance records, leave, salary
 * structure, adjustments) and returns plain arrays; saving, locking and
 * workflow belong to PayrollRunService. Nothing here depends on the current
 * time except to know whether the period has ended.
 *
 * Method (monthly fixed salary):
 *   lop days     = absent + unpaid leave + days before joining + late/early penalties
 *   earned item  = monthly amount - monthly amount / divisor * lop days   (items marked "prorate")
 *   overtime     = base / (divisor * full-day hours) * overtime hours * multiplier
 *
 * "divisor" is the number of calendar days in the period or a fixed number
 * (for example 30), as configured per client in PayrollSetting.
 *
 * Statutory items keep the rate or amount they were offered with; the
 * settings only add ceilings (PF wage ceiling, ESIC eligibility ceiling).
 * The figures must be validated by payroll/legal before first production use.
 */
class PayrollEngine
{
    public function __construct(
        private readonly PolicyResolver $resolver,
        private readonly AttendanceCalculator $calculator,
    ) {
    }

    /**
     * @return array<string, mixed> attributes for a PayrollRunItem
     */
    public function compute(
        User $user,
        PayrollPeriod $period,
        PayrollSetting $settings,
        ?Collection $adjustments = null,
        ?Carbon $now = null,
    ): array {
        $now = $now ?? now();
        $adjustments = $adjustments ?? collect();
        $exceptions = [];

        $start = Carbon::instance($period->start_date)->startOfDay();
        $end = Carbon::instance($period->end_date)->startOfDay();
        $calendarDays = $period->days();

        // Make sure every day up to today is calculated (locked days are returned as they are).
        $upTo = $end->lt($now->copy()->startOfDay()) ? $end : $now->copy()->startOfDay();
        if ($upTo->gte($start)) {
            $this->calculator->calculateRange($user, $start, $upTo);
        }

        $records = AttendanceRecord::query()
            ->where('user_id', $user->id)
            ->whereDate('attendance_date', '>=', $start->toDateString())->whereDate('attendance_date', '<=', $end->toDateString())
            ->with('leaveApplication')
            ->get()
            ->keyBy(fn (AttendanceRecord $r) => $r->attendance_date->toDateString());

        $joined = $user->date_of_joining ? Carbon::instance($user->date_of_joining)->startOfDay() : null;
        $summary = $this->summariseAttendance($start, $end, $joined, $records);
        $policy = $this->policyFor($user, $end, $records);

        $penaltyDays = $this->penaltyDays($policy, $summary);
        $lop = round($summary['lop_days'] + $summary['not_employed_days'] + $penaltyDays['total'], 2);
        $divisor = $settings->divisor_mode === 'fixed' ? max(1, (int) $settings->fixed_divisor) : $calendarDays;

        $exceptions = array_merge($exceptions, $this->exceptionsFor($user, $period, $settings, $records, $summary, $policy, $joined, $now));

        $structure = $this->structureFor($user, $period);
        $earnings = $deductions = $employer = [];

        if (!$structure) {
            $exceptions[] = $this->exception('no_salary_structure', 'No salary structure is effective for this period.', true);
        } else {
            $monthly = $this->weightedMonthlyItems($user, $period, $joined);
            $earnings = $this->earnedLines($monthly, $settings, $policy, $lop, $divisor, $summary, $structure);
        }

        $base = $this->monthlyBases($structure ? $this->weightedMonthlyItems($user, $period, $joined) : []);
        $earnedBasic = collect($earnings)->where('is_basic', true)->sum('amount');
        $earnedGross = collect($earnings)->sum('amount');
        $earnedRatio = $base['gross'] > 0 ? $earnedGross / $base['gross'] : 0.0;

        // Overtime
        $overtimeAmount = 0.0;
        if ($policy?->overtime_eligible && $summary['overtime_minutes'] > 0 && $base['gross'] > 0) {
            $otBase = $settings->overtime_base === 'basic' ? $base['basic'] : $base['gross'];
            $fullDayHours = max(1, ($policy->full_day_minutes ?: 480) / 60);
            $hourly = $otBase / ($divisor * $fullDayHours);
            $overtimeAmount = $hourly * ($summary['overtime_minutes'] / 60) * (float) $policy->overtime_multiplier;

            $earnings[] = $this->line('OT', 'Overtime', $overtimeAmount, sprintf('%.1f h × %sx', $summary['overtime_minutes'] / 60, $policy->overtime_multiplier));
        }

        // Approved adjustments
        foreach ($adjustments as $adjustment) {
            $line = $this->line('ADJ' . $adjustment->id, $adjustment->label, (float) $adjustment->amount, $adjustment->reason);

            if ($adjustment->is_deduction) {
                $deductions[] = $line;
            } else {
                $earnings[] = $line;
            }
        }

        if ($structure) {
            $monthly = $this->weightedMonthlyItems($user, $period, $joined);
            $deductions = array_merge(
                $this->contributionLines($monthly['deduction'] ?? [], $settings, $base, $earnedBasic, $earnedGross, $earnedRatio),
                $deductions
            );
            $employer = $this->contributionLines($monthly['employer_contribution'] ?? [], $settings, $base, $earnedBasic, $earnedGross, $earnedRatio);
        }

        // Hourly clients are paid for hours, not days.
        if ($settings->calculation_method === 'hourly' && $structure) {
            $earnings = $this->hourlyEarnings($structure, $policy, $summary, $earnings);
        }

        [$earnings, $deductions, $employer] = [
            $this->roundLines($earnings, $settings),
            $this->roundLines($deductions, $settings),
            $this->roundLines($employer, $settings),
        ];

        $gross = $this->round(collect($earnings)->sum('amount'), $settings);
        $totalDeductions = $this->round(collect($deductions)->sum('amount'), $settings);
        $net = round($gross - $totalDeductions, max(0, (int) $settings->precision));

        if ($net < 0) {
            $exceptions[] = $this->exception('negative_net', 'Deductions are higher than earnings (net pay ' . number_format($net, 2) . ').', true);
        }

        $payable = max(0, round($calendarDays - $lop, 2));

        return [
            'user_id' => $user->id,
            'structure_id' => $structure?->id,
            'calendar_days' => $calendarDays,
            'payable_days' => $payable,
            'lop_days' => $lop,
            'present_days' => $summary['present_days'],
            'absent_days' => $summary['absent_days'],
            'paid_leave_days' => $summary['paid_leave_days'],
            'unpaid_leave_days' => $summary['unpaid_leave_days'],
            'weekly_off_days' => $summary['weekly_off_days'],
            'holiday_days' => $summary['holiday_days'],
            'late_count' => $summary['late_count'],
            'early_count' => $summary['early_count'],
            'worked_minutes' => $summary['worked_minutes'],
            'overtime_minutes' => $summary['overtime_minutes'],
            'gross_earnings' => $gross,
            'total_deductions' => $totalDeductions,
            'net_pay' => $net,
            'employer_contributions' => $this->round(collect($employer)->sum('amount'), $settings),
            'earnings' => $earnings,
            'deductions' => $deductions,
            'employer' => $employer,
            'attendance_summary' => $summary + [
                'late_penalty_days' => $penaltyDays['late'],
                'early_penalty_days' => $penaltyDays['early'],
                'divisor' => $divisor,
                'policy_id' => $policy?->id,
            ],
            'exceptions' => $exceptions,
        ];
    }

    /* ───────────────────────── attendance ───────────────────────── */

    /** @return array<string, float|int> */
    private function summariseAttendance(Carbon $start, Carbon $end, ?Carbon $joined, Collection $records): array
    {
        $s = [
            'present_days' => 0.0, 'absent_days' => 0.0, 'paid_leave_days' => 0.0, 'unpaid_leave_days' => 0.0,
            'weekly_off_days' => 0.0, 'holiday_days' => 0.0, 'lop_days' => 0.0, 'not_employed_days' => 0.0,
            'late_count' => 0, 'early_count' => 0, 'worked_minutes' => 0, 'overtime_minutes' => 0,
            'unresolved_days' => 0, 'review_days' => 0,
        ];

        $day = $start->copy();

        while ($day->lte($end)) {
            $key = $day->toDateString();

            if ($joined && $day->lt($joined)) {
                $s['not_employed_days']++;
                $day->addDay();
                continue;
            }

            $r = $records->get($key);

            if (!$r) {
                $s['unresolved_days']++;
                $s['lop_days']++;
                $day->addDay();
                continue;
            }

            $fraction = (float) $r->payable_fraction;

            match ($r->status) {
                AttendanceRecord::STATUS_PRESENT => $s['present_days'] += 1,
                AttendanceRecord::STATUS_HALF_DAY => $s['present_days'] += min(0.5, $fraction),
                AttendanceRecord::STATUS_WEEKLY_OFF => $s['weekly_off_days']++,
                AttendanceRecord::STATUS_HOLIDAY => $s['holiday_days']++,
                default => null,
            };

            if ($r->status === AttendanceRecord::STATUS_LEAVE
                || ($r->status === AttendanceRecord::STATUS_HALF_DAY && $r->leave_application_id)) {
                $leaveDays = $r->leaveApplication?->is_half_day ? 0.5 : 1.0;
                $r->leave_is_paid ? $s['paid_leave_days'] += $leaveDays : $s['unpaid_leave_days'] += $leaveDays;
            }

            if (in_array($r->status, [AttendanceRecord::STATUS_PENDING, AttendanceRecord::STATUS_INCOMPLETE], true)) {
                $s['unresolved_days']++;
            }

            if ($r->review_status === 'needs_review') {
                $s['review_days']++;
            }

            if ($r->status === AttendanceRecord::STATUS_ABSENT
                || ($r->status === AttendanceRecord::STATUS_INCOMPLETE && $fraction < 1)) {
                $s['absent_days'] += 1 - $fraction;
            }

            $s['lop_days'] += max(0.0, 1 - $fraction);
            $s['late_count'] += $r->is_late ? 1 : 0;
            $s['early_count'] += $r->is_early ? 1 : 0;
            $s['worked_minutes'] += $r->worked_minutes;
            $s['overtime_minutes'] += $r->overtime_approved ? $r->overtime_minutes : 0;

            $day->addDay();
        }

        // Half-day absences inside half-day leave are already counted in lop; keep tidy numbers.
        foreach (['present_days', 'absent_days', 'paid_leave_days', 'unpaid_leave_days', 'weekly_off_days', 'holiday_days', 'lop_days', 'not_employed_days'] as $k) {
            $s[$k] = round($s[$k], 2);
        }

        return $s;
    }

    private function policyFor(User $user, Carbon $end, Collection $records): ?AttendancePolicy
    {
        $policyId = $records->filter(fn ($r) => $r->policy_id)->sortByDesc('attendance_date')->first()?->policy_id;

        return $policyId ? AttendancePolicy::withTrashed()->find($policyId) : $this->resolver->attendancePolicy($user, $end);
    }

    /** Late and early-departure penalties expressed in days of pay. */
    private function penaltyDays(?AttendancePolicy $policy, array $summary): array
    {
        $result = ['late' => 0.0, 'early' => 0.0, 'total' => 0.0];

        if (!$policy || $policy->lates_per_half_day < 1) {
            return $result;
        }

        if ($policy->deduct_late) {
            $chargeable = max(0, $summary['late_count'] - $policy->max_late_per_month);
            $result['late'] = intdiv($chargeable, $policy->lates_per_half_day) * 0.5;
        }

        if ($policy->deduct_early) {
            $result['early'] = intdiv($summary['early_count'], $policy->lates_per_half_day) * 0.5;
        }

        $result['total'] = $result['late'] + $result['early'];

        return $result;
    }

    /** @return array<int, array<string, mixed>> */
    private function exceptionsFor(
        User $user, PayrollPeriod $period, PayrollSetting $settings, Collection $records, array $summary,
        ?AttendancePolicy $policy, ?Carbon $joined, Carbon $now
    ): array {
        $out = [];
        $blockAttendance = (bool) $settings->block_on_pending_attendance;

        if ($period->end_date->gt($now->copy()->startOfDay())) {
            $out[] = $this->exception('period_not_ended', 'The payroll period has not ended yet.', true);
        }

        if (!$policy) {
            $out[] = $this->exception('no_attendance_policy', 'No attendance policy applies to this employee; attendance was judged without one.', false);
        }

        if ($summary['review_days'] > 0) {
            $out[] = $this->exception('attendance_review', $summary['review_days'] . ' day(s) have attendance exceptions that are not reviewed.', $blockAttendance);
        }

        if ($summary['unresolved_days'] > 0 && $period->end_date->lte($now->copy()->startOfDay())) {
            $out[] = $this->exception('attendance_unresolved', $summary['unresolved_days'] . ' day(s) have incomplete or missing attendance.', $blockAttendance);
        }

        $corrections = AttendanceCorrection::query()
            ->where('user_id', $user->id)
            ->where('status', AttendanceCorrection::PENDING)
            ->whereDate('attendance_date', '>=', $period->start_date->toDateString())->whereDate('attendance_date', '<=', $period->end_date->toDateString())
            ->count();

        if ($corrections > 0) {
            $out[] = $this->exception('pending_corrections', $corrections . ' attendance correction request(s) are still pending.', $blockAttendance);
        }

        $pendingLeave = LeaveApplication::query()
            ->where('user_id', $user->id)
            ->where('status', LeaveApplication::PENDING)
            ->overlapping($period->start_date->toDateString(), $period->end_date->toDateString())
            ->count();

        if ($pendingLeave > 0) {
            $out[] = $this->exception('pending_leave', $pendingLeave . ' leave request(s) in this period are still pending.', (bool) $settings->block_on_pending_leave);
        }

        if ($joined && $joined->gt($period->start_date)) {
            $out[] = $this->exception('joined_mid_period', 'Joined on ' . $joined->format('d M Y') . '; salary is prorated.', false);
        }

        return $out;
    }

    /* ───────────────────────── salary ───────────────────────── */

    private function structureFor(User $user, PayrollPeriod $period): ?EmployeeSalaryStructure
    {
        return EmployeeSalaryStructure::query()
            ->where('user_id', $user->id)
            ->whereDate('effective_from', '<=', $period->end_date->toDateString())
            ->where(fn ($q) => $q->whereNull('effective_to')->orWhereDate('effective_to', '>=', $period->start_date->toDateString()))
            ->orderByDesc('effective_from')
            ->first();
    }

    /**
     * Monthly amount of every item, weighted by how many days of the period
     * each salary structure was in force (so a mid-month revision is paid pro rata).
     *
     * @return array<string, array<int, array<string, mixed>>> grouped by item type
     */
    private function weightedMonthlyItems(User $user, PayrollPeriod $period, ?Carbon $joined): array
    {
        $structures = EmployeeSalaryStructure::query()
            ->where('user_id', $user->id)
            ->whereDate('effective_from', '<=', $period->end_date->toDateString())
            ->where(fn ($q) => $q->whereNull('effective_to')->orWhereDate('effective_to', '>=', $period->start_date->toDateString()))
            ->with('items')
            ->orderBy('effective_from')
            ->get();

        $from = $joined && $joined->gt($period->start_date) ? $joined : Carbon::instance($period->start_date);
        $totalDays = max(1, $from->diffInDays($period->end_date) + 1);
        $weights = [];

        $day = $from->copy();
        while ($day->lte($period->end_date)) {
            $match = $structures->filter(fn ($s) => $s->effective_from->lte($day) && (!$s->effective_to || $s->effective_to->gte($day)))->last();
            if ($match) {
                $weights[$match->id] = ($weights[$match->id] ?? 0) + 1;
            }
            $day->addDay();
        }

        $grouped = [];

        foreach ($structures as $structure) {
            $weight = ($weights[$structure->id] ?? 0) / $totalDays;

            if ($weight <= 0) {
                continue;
            }

            $basicM = (float) $structure->items->where('is_basic', true)->where('type', 'earning')->sum('amount');
            $fixedGrossM = (float) $structure->items->where('type', 'earning')->where('calc_type', 'fixed')->sum('amount');

            foreach ($structure->items as $item) {
                $amount = match ($item->calc_type) {
                    'percent_of_basic' => $basicM * ($item->percentage ?? 0) / 100,
                    'percent_of_gross' => $fixedGrossM * ($item->percentage ?? 0) / 100,
                    default => (float) $item->amount,
                };

                $key = $item->type . '|' . $item->code;
                $grouped[$item->type][$key] ??= [
                    'code' => $item->code, 'name' => $item->name, 'type' => $item->type,
                    'calc_type' => $item->calc_type, 'percentage' => $item->percentage,
                    'statutory_code' => $item->statutory_code, 'prorate' => $item->prorate,
                    'is_basic' => $item->is_basic, 'sort_order' => $item->sort_order, 'monthly' => 0.0,
                ];
                $grouped[$item->type][$key]['monthly'] += $amount * $weight;
            }
        }

        return array_map(fn ($items) => array_values($items), $grouped);
    }

    private function monthlyBases(array $monthly): array
    {
        $earnings = collect($monthly['earning'] ?? []);

        return [
            'basic' => (float) $earnings->where('is_basic', true)->sum('monthly'),
            'gross' => (float) $earnings->sum('monthly'),
        ];
    }

    private function earnedLines(array $monthly, PayrollSetting $settings, ?AttendancePolicy $policy, float $lop, int $divisor, array $summary, EmployeeSalaryStructure $structure): array
    {
        $lines = [];

        foreach ($monthly['earning'] ?? [] as $item) {
            $earned = $item['prorate']
                ? max(0.0, $item['monthly'] - ($item['monthly'] / $divisor) * $lop)
                : $item['monthly'];

            $lines[] = $this->line($item['code'], $item['name'], $earned, $item['prorate'] && $lop > 0 ? 'Monthly ' . number_format($item['monthly'], 2) : null) + ['is_basic' => (bool) $item['is_basic'], 'monthly' => round($item['monthly'], 2)];
        }

        return $lines;
    }

    /** Deductions and employer contributions follow what the employee actually earned. */
    private function contributionLines(array $items, PayrollSetting $settings, array $base, float $earnedBasic, float $earnedGross, float $earnedRatio): array
    {
        $lines = [];

        foreach ($items as $item) {
            $amount = match ($item['calc_type']) {
                'percent_of_basic' => $earnedBasic * ($item['percentage'] ?? 0) / 100,
                'percent_of_gross' => $earnedGross * ($item['percentage'] ?? 0) / 100,
                default => $item['prorate'] ? $item['monthly'] * $earnedRatio : $item['monthly'],
            };

            $statutory = $item['statutory_code'] ?? null;

            if (str_starts_with((string) $statutory, 'pf_')) {
                if (!$settings->pf_enabled) {
                    continue;
                }

                if ($settings->pf_wage_ceiling && $item['calc_type'] === 'percent_of_basic') {
                    $amount = min($earnedBasic, (float) $settings->pf_wage_ceiling) * ($item['percentage'] ?? 0) / 100;
                }
            }

            if (str_starts_with((string) $statutory, 'esic_')) {
                // ESIC applies only while the contractual gross is within the ceiling.
                if (!$settings->esic_enabled || ($settings->esic_gross_ceiling && $base['gross'] > $settings->esic_gross_ceiling)) {
                    continue;
                }
            }

            $lines[] = $this->line($item['code'], $item['name'], $amount);
        }

        return $lines;
    }

    private function hourlyEarnings(EmployeeSalaryStructure $structure, ?AttendancePolicy $policy, array $summary, array $earnings): array
    {
        $rate = (float) $structure->hourly_rate;
        $fullDayHours = ($policy?->full_day_minutes ?: 480) / 60;
        $hours = $summary['worked_minutes'] / 60 + ($summary['paid_leave_days'] + $summary['holiday_days']) * $fullDayHours;

        // Replace salary lines with a single hourly line; keep overtime.
        $kept = array_values(array_filter($earnings, fn ($l) => $l['code'] === 'OT' || str_starts_with($l['code'], 'ADJ')));

        return array_merge([$this->line('HOURLY', 'Hourly pay', $rate * $hours, sprintf('%.2f h × %s', $hours, number_format($rate, 2)))], $kept);
    }

    /* ───────────────────────── helpers ───────────────────────── */

    private function line(string $code, string $name, float $amount, ?string $note = null): array
    {
        return ['code' => $code, 'name' => $name, 'amount' => $amount, 'note' => $note];
    }

    private function exception(string $code, string $message, bool $blocking): array
    {
        return ['code' => $code, 'message' => $message, 'blocking' => $blocking];
    }

    private function roundLines(array $lines, PayrollSetting $settings): array
    {
        return array_map(function (array $line) use ($settings) {
            $line['amount'] = $this->round($line['amount'], $settings);

            return $line;
        }, $lines);
    }

    private function round(float $value, PayrollSetting $settings): float
    {
        $precision = max(0, (int) $settings->precision);
        $factor = 10 ** $precision;

        return match ($settings->rounding) {
            'up' => ceil($value * $factor - 1e-9) / $factor,
            'down' => floor($value * $factor + 1e-9) / $factor,
            default => round($value, $precision),
        };
    }
}
