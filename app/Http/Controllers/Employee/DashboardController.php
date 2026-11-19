<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\AttendanceCorrection;
use App\Models\AttendanceRecord;
use App\Models\LeaveApplication;
use App\Models\Payslip;
use App\Services\Workforce\AttendanceCalculator;
use App\Services\Workforce\LeaveService;
use App\Services\Workforce\PunchService;
use App\Services\Workforce\WorkforceScope;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(
        Request $request,
        PunchService $punches,
        LeaveService $leave,
        AttendanceCalculator $calculator,
    ): Response {
        $user = $request->user();
        $from = today()->startOfMonth();

        // Make sure this month is calculated up to today before summarising it.
        $have = AttendanceRecord::query()->where('user_id', $user->id)
            ->whereDate('attendance_date', '>=', $from->toDateString())
            ->pluck('attendance_date')->map(fn ($d) => $d->toDateString())->all();

        for ($d = $from->copy(); $d->lte(today()); $d->addDay()) {
            if (!in_array($d->toDateString(), $have, true)) {
                $calculator->calculate($user, $d->copy());
            }
        }

        $records = AttendanceRecord::query()->where('user_id', $user->id)
            ->whereDate('attendance_date', '>=', $from->toDateString())
            ->whereDate('attendance_date', '<=', today()->toDateString())
            ->get();

        $state = $punches->currentState($user, now());
        $today = $records->first(fn ($r) => $r->attendance_date->isToday());

        $slip = Payslip::query()->visibleToEmployee()->where('user_id', $user->id)->orderByDesc('period_start')->first();

        $team = null;
        if ($user->can('leaves.approve') || $user->can('attendance.approve-regularization')) {
            $teamIds = WorkforceScope::reporteeIds($user->id);
            $team = [
                'size' => count($teamIds),
                'pending_leave' => LeaveApplication::query()->whereIn('user_id', $teamIds)->where('status', 'pending')->count(),
                'pending_corrections' => AttendanceCorrection::query()->whereIn('user_id', $teamIds)->where('status', 'pending')->count(),
            ];
        }

        return Inertia::render('Employee/Dashboard', [
            'punchState' => $state['last_type'],
            'today' => $today ? [
                'status' => $today->status,
                'first_in' => $today->first_in?->format('h:i A'),
                'worked_minutes' => $today->worked_minutes,
                'is_late' => $today->is_late,
            ] : null,
            'month' => [
                'label' => today()->format('F Y'),
                'present' => $records->where('status', 'present')->count() + 0.5 * $records->where('status', 'half_day')->count(),
                'absent' => $records->where('status', 'absent')->count(),
                'leave' => $records->where('status', 'leave')->count(),
                'late' => $records->where('is_late', true)->count(),
                'exceptions' => $records->where('review_status', 'needs_review')->count(),
                'worked_minutes' => $records->sum('worked_minutes'),
            ],
            'balances' => $leave->balances($user),
            'pending' => [
                'leave' => LeaveApplication::query()->where('user_id', $user->id)->where('status', 'pending')->count(),
                'corrections' => AttendanceCorrection::query()->where('user_id', $user->id)->where('status', 'pending')->count(),
            ],
            'latestPayslip' => $slip ? [
                'id' => $slip->id,
                'period' => $slip->snapshot['period']['name'] ?? $slip->period_start->format('F Y'),
                'net' => $slip->net_pay,
            ] : null,
            'team' => $team,
            'hasClient' => (bool) $user->client_id,
        ]);
    }
}
