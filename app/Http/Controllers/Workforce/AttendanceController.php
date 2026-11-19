<?php

namespace App\Http\Controllers\Workforce;

use App\Http\Controllers\Controller;
use App\Models\AttendanceCorrection;
use App\Models\AttendanceImportBatch;
use App\Models\AttendancePunch;
use App\Models\AttendanceRecord;
use App\Models\AuditLog;
use App\Models\Client;
use App\Models\User;
use App\Services\Workforce\AttendanceCalculator;
use App\Services\Workforce\CorrectionService;
use App\Services\Workforce\MachinePunchImporter;
use App\Services\Workforce\PolicyResolver;
use App\Services\Workforce\PunchRejectedException;
use App\Services\Workforce\PunchService;
use App\Services\Workforce\WorkforceScope;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;
use Inertia\Inertia;
use Inertia\Response;

class AttendanceController extends Controller
{
    private const OVERRIDES = [
        'present' => 1.0,
        'half_day' => 0.5,
        'absent' => 0.0,
        'weekly_off' => 1.0,
        'holiday' => 1.0,
    ];

    public function __construct(
        private readonly AttendanceCalculator $calculator,
        private readonly PunchService $punches,
        private readonly CorrectionService $corrections,
        private readonly PolicyResolver $resolver,
    ) {
    }

    /* ───────────────────────── daily board ───────────────────────── */

    public function index(Request $request): Response
    {
        $viewer = $request->user();
        $date = $this->date($request->query('date'));
        $filters = [
            'date' => $date->toDateString(),
            'client_id' => $request->query('client_id'),
            'status' => $request->query('status'),
            'search' => trim((string) $request->query('search')),
        ];

        $employees = $this->employeesFor($viewer, $filters['client_id'], $filters['search']);

        // Calculate any day nobody has opened yet (bounded; the nightly job normally does this).
        if ($date->lte(today())) {
            $have = AttendanceRecord::query()->whereIn('user_id', $employees->pluck('id'))
                ->whereDate('attendance_date', $date->toDateString())->pluck('user_id')->all();

            foreach ($employees->reject(fn ($e) => in_array($e->id, $have, true))->take(300) as $employee) {
                $this->calculator->calculate($employee, $date->copy());
            }
        }

        $records = AttendanceRecord::query()
            ->whereIn('user_id', $employees->pluck('id'))
            ->whereDate('attendance_date', $date->toDateString())
            ->with('shift:id,name')
            ->get()
            ->keyBy('user_id');

        $rows = $employees->map(function (User $e) use ($records) {
            $r = $records->get($e->id);

            return [
                'user_id' => $e->id,
                'employee_id' => $e->employee_id,
                'name' => $e->full_name,
                'client' => $e->client?->name,
                'shift' => $r?->shift?->name,
                'status' => $r?->status ?? 'pending',
                'first_in' => $r?->first_in?->format('h:i A'),
                'last_out' => $r?->last_out?->format('h:i A'),
                'worked_minutes' => $r?->worked_minutes ?? 0,
                'late_minutes' => $r?->late_minutes ?? 0,
                'early_minutes' => $r?->early_minutes ?? 0,
                'flags' => $r?->flags ?? [],
                'review_status' => $r?->review_status ?? 'ok',
                'locked' => (bool) $r?->isLocked(),
            ];
        })->when($filters['status'], fn ($c, $s) => $c->where('status', $s))->values();

        $all = $records->values();

        return Inertia::render('Workforce/Attendance/Index', [
            'filters' => $filters,
            'clients' => $this->clients($viewer),
            'statuses' => AttendanceRecord::STATUSES,
            'rows' => $rows->forPage((int) $request->query('page', 1), 25)->values(),
            'pagination' => ['total' => $rows->count(), 'per_page' => 25, 'current_page' => (int) $request->query('page', 1)],
            'stats' => [
                'employees' => $employees->count(),
                'present' => $all->where('status', 'present')->count(),
                'half_day' => $all->where('status', 'half_day')->count(),
                'absent' => $all->where('status', 'absent')->count(),
                'leave' => $all->where('status', 'leave')->count(),
                'off' => $all->whereIn('status', ['weekly_off', 'holiday'])->count(),
                'late' => $all->where('is_late', true)->count(),
                'exceptions' => $all->where('review_status', 'needs_review')->count(),
            ],
        ]);
    }

    public function exceptions(Request $request): Response
    {
        $viewer = $request->user();
        $from = $this->date($request->query('from', today()->subDays(30)->toDateString()));
        $to = $this->date($request->query('to', today()->toDateString()));

        $ids = WorkforceScope::employees($viewer, 'attendance')->pluck('users.id');

        $records = AttendanceRecord::query()
            ->whereIn('user_id', $ids)
            ->where('review_status', 'needs_review')
            ->whereDate('attendance_date', '>=', $from->toDateString())
            ->whereDate('attendance_date', '<=', $to->toDateString())
            ->when($request->query('client_id'), fn ($q, $c) => $q->where('client_id', $c))
            ->with('user:id,first_name,last_name,employee_id', 'client:id,name')
            ->orderByDesc('attendance_date')
            ->paginate(25)
            ->withQueryString()
            ->through(fn (AttendanceRecord $r) => [
                'id' => $r->id,
                'user_id' => $r->user_id,
                'date' => $r->attendance_date->toDateString(),
                'date_label' => $r->attendance_date->format('D, d M Y'),
                'employee' => $r->user->full_name,
                'employee_id' => $r->user->employee_id,
                'client' => $r->client?->name,
                'status' => $r->status,
                'first_in' => $r->first_in?->format('h:i A'),
                'last_out' => $r->last_out?->format('h:i A'),
                'flags' => $r->flags ?? [],
                'locked' => $r->isLocked(),
            ]);

        return Inertia::render('Workforce/Attendance/Exceptions', [
            'records' => $records,
            'filters' => ['from' => $from->toDateString(), 'to' => $to->toDateString(), 'client_id' => $request->query('client_id')],
            'clients' => $this->clients($viewer),
        ]);
    }

    /* ───────────────────────── corrections ───────────────────────── */

    public function corrections(Request $request): Response
    {
        $ids = WorkforceScope::employees($request->user(), 'attendance')->pluck('users.id');

        $map = fn (AttendanceCorrection $c) => [
            'id' => $c->id,
            'employee' => $c->user->full_name,
            'employee_id' => $c->user->employee_id,
            'user_id' => $c->user_id,
            'date' => $c->attendance_date->format('d M Y'),
            'requested_in' => $c->requested_in?->format('h:i A'),
            'requested_out' => $c->requested_out?->format('h:i A'),
            'reason' => $c->reason,
            'status' => $c->status,
            'snapshot' => $c->original_snapshot['punches'] ?? [],
            'decided_by' => $c->decider?->full_name,
            'decision_note' => $c->decision_note,
            'can_decide' => $c->isPending() && $c->user_id !== $request->user()->id,
        ];

        $base = AttendanceCorrection::query()->whereIn('user_id', $ids)->with('user:id,first_name,last_name,employee_id', 'decider:id,first_name,last_name');

        return Inertia::render('Workforce/Attendance/Corrections', [
            'pending' => (clone $base)->where('status', 'pending')->orderBy('attendance_date')->get()->map($map),
            'recent' => (clone $base)->where('status', '!=', 'pending')->latest('decided_at')->limit(25)->get()->map($map),
        ]);
    }

    public function approveCorrection(Request $request, AttendanceCorrection $correction): RedirectResponse
    {
        $this->authorizeEmployee($request, $correction->user);
        abort_if($correction->user_id === $request->user()->id, 403, 'You cannot approve your own request.');

        try {
            $this->corrections->approve($correction, $request->user(), $request->input('note'));
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['correction' => $e->getMessage()]);
        }

        return back()->with('success', 'Correction approved and attendance recalculated.');
    }

    public function rejectCorrection(Request $request, AttendanceCorrection $correction): RedirectResponse
    {
        $this->authorizeEmployee($request, $correction->user);
        abort_if($correction->user_id === $request->user()->id, 403, 'You cannot reject your own request.');

        $data = $request->validate(['note' => ['required', 'string', 'max:500']]);

        try {
            $this->corrections->reject($correction, $request->user(), $data['note']);
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['correction' => $e->getMessage()]);
        }

        return back()->with('success', 'Correction rejected.');
    }

    /* ───────────────────────── machine import ───────────────────────── */

    public function importForm(): Response
    {
        return Inertia::render('Workforce/Attendance/Import', [
            'batches' => AttendanceImportBatch::query()->with('uploader:id,first_name,last_name')->latest()->limit(20)->get()
                ->map(fn ($b) => [
                    'id' => $b->id,
                    'filename' => $b->filename,
                    'uploaded_by' => $b->uploader?->full_name,
                    'at' => $b->created_at->format('d M Y, h:i A'),
                    'total' => $b->total_rows,
                    'imported' => $b->imported_rows,
                    'duplicates' => $b->duplicate_rows,
                    'unmatched' => $b->unmatched_rows,
                    'errors' => $b->error_rows,
                    'details' => $b->details,
                ]),
        ]);
    }

    public function import(Request $request, MachinePunchImporter $importer): RedirectResponse
    {
        $request->validate(['file' => ['required', 'file', 'mimes:csv,txt', 'max:5120']]);

        $file = $request->file('file');
        $batch = $importer->import($file->getRealPath(), $file->getClientOriginalName(), $request->user());

        return back()->with('success', sprintf(
            'Imported %d punch(es); %d duplicate, %d unmatched employee ID, %d unreadable row(s).',
            $batch->imported_rows, $batch->duplicate_rows, $batch->unmatched_rows, $batch->error_rows
        ));
    }

    /* ───────────────────────── one employee ───────────────────────── */

    public function employee(Request $request, User $employee): Response
    {
        $this->authorizeEmployee($request, $employee);

        $month = $this->month($request->query('month'));
        $from = $month->copy()->startOfMonth();
        $to = $month->copy()->endOfMonth()->startOfDay();
        $until = $to->gt(today()) ? today() : $to;

        for ($d = $from->copy(); $d->lte($until); $d->addDay()) {
            $this->calculator->calculate($employee, $d->copy());
        }

        $records = AttendanceRecord::query()->where('user_id', $employee->id)
            ->whereDate('attendance_date', '>=', $from->toDateString())
            ->whereDate('attendance_date', '<=', $to->toDateString())
            ->with('leaveApplication.leaveType')->get()
            ->keyBy(fn ($r) => $r->attendance_date->toDateString());

        $punches = AttendancePunch::query()->where('user_id', $employee->id)
            ->whereDate('attendance_date', '>=', $from->toDateString())
            ->whereDate('attendance_date', '<=', $to->toDateString())
            ->with('creator:id,first_name,last_name')
            ->orderBy('punched_at')->get()
            ->groupBy(fn ($p) => $p->attendance_date->toDateString());

        $holidays = $this->resolver->holidaysBetween($employee, $from, $to);

        $days = [];
        for ($d = $from->copy(); $d->lte($to); $d->addDay()) {
            $key = $d->toDateString();
            $r = $records->get($key);

            $days[] = [
                'date' => $key,
                'label' => $d->format('D, d M'),
                'record_id' => $r?->id,
                'status' => $r?->status ?? ($holidays->has($key) ? 'holiday' : 'pending'),
                'first_in' => $r?->first_in?->format('h:i A'),
                'last_out' => $r?->last_out?->format('h:i A'),
                'worked_minutes' => $r?->worked_minutes ?? 0,
                'break_minutes' => $r?->break_minutes ?? 0,
                'late_minutes' => $r?->late_minutes ?? 0,
                'early_minutes' => $r?->early_minutes ?? 0,
                'overtime_minutes' => $r?->overtime_minutes ?? 0,
                'overtime_approved' => (bool) $r?->overtime_approved,
                'session_minutes' => $r?->session_minutes ?? 0,
                'flags' => $r?->flags ?? [],
                'review_status' => $r?->review_status ?? 'ok',
                'override' => $r?->status_override,
                'override_reason' => $r?->override_reason,
                'locked' => (bool) $r?->isLocked(),
                'note' => $holidays->get($key)?->name ?? $r?->leaveApplication?->leaveType?->name,
                'punches' => ($punches->get($key) ?? collect())->map(fn ($p) => [
                    'id' => $p->id,
                    'type' => $p->type,
                    'source' => $p->source,
                    'time' => $p->punched_at->format('h:i A'),
                    'geo' => $p->geo_status,
                    'distance' => $p->distance_meters !== null ? round($p->distance_meters) : null,
                    'by' => $p->creator?->full_name,
                    'notes' => $p->notes,
                    'voided' => $p->voided_at !== null,
                    'void_reason' => $p->void_reason,
                ])->values()->all(),
            ];
        }

        return Inertia::render('Workforce/Attendance/Employee', [
            'employee' => [
                'id' => $employee->id,
                'name' => $employee->full_name,
                'employee_id' => $employee->employee_id,
                'client' => $employee->client?->name,
                'designation' => $employee->designation,
            ],
            'month' => $month->format('Y-m'),
            'monthLabel' => $month->format('F Y'),
            'prevMonth' => $month->copy()->subMonth()->format('Y-m'),
            'nextMonth' => $month->copy()->addMonth()->lte(today()->endOfMonth()) ? $month->copy()->addMonth()->format('Y-m') : null,
            'days' => $days,
            'statuses' => array_keys(self::OVERRIDES),
            'canEdit' => $request->user()->can('attendance.update'),
        ]);
    }

    public function manualPunch(Request $request, User $employee): RedirectResponse
    {
        $this->authorizeEmployee($request, $employee);

        $data = $request->validate([
            'type' => ['required', 'in:in,out,break_start,break_end'],
            'date' => ['required', 'date'],
            'time' => ['required', 'date_format:H:i'],
            'reason' => ['required', 'string', 'min:5', 'max:500'],
        ]);

        $at = Carbon::parse($data['date'] . ' ' . $data['time']);

        if ($at->isFuture()) {
            return back()->withErrors(['punch' => 'A punch cannot be in the future.']);
        }

        $record = AttendanceRecord::query()->where('user_id', $employee->id)->whereDate('attendance_date', $data['date'])->first();

        if ($record?->isLocked()) {
            return back()->withErrors(['punch' => 'This day is locked by an approved payroll run.']);
        }

        $punch = $this->punches->manual($employee, $data['type'], $at, $data['reason'], $request->user(), null, false);

        // Keep the punch on the day it was entered for, even past midnight.
        if ($punch->attendance_date->toDateString() !== $data['date']) {
            $punch->update(['attendance_date' => $data['date']]);
        }

        $this->calculator->calculate($employee, Carbon::parse($data['date']));

        return back()->with('success', 'Manual punch added and the day recalculated.');
    }

    public function voidPunch(Request $request, AttendancePunch $punch): RedirectResponse
    {
        $this->authorizeEmployee($request, $punch->user);
        $data = $request->validate(['reason' => ['required', 'string', 'min:3', 'max:255']]);

        try {
            $this->punches->void($punch, $request->user(), $data['reason']);
        } catch (PunchRejectedException $e) {
            return back()->withErrors(['punch' => $e->getMessage()]);
        }

        return back()->with('success', 'Punch voided. The original record is kept in the audit trail.');
    }

    public function recalculate(Request $request, User $employee): RedirectResponse
    {
        $this->authorizeEmployee($request, $employee);
        $month = $this->month($request->input('month'));

        $this->calculator->calculateRange($employee, $month->copy()->startOfMonth(), $month->copy()->endOfMonth()->min(today()));

        return back()->with('success', 'Attendance recalculated (days locked by payroll are left as they are).');
    }

    public function review(Request $request, AttendanceRecord $record): RedirectResponse
    {
        $this->authorizeEmployee($request, $record->user);
        abort_if($record->isLocked(), 422, 'This day is locked by payroll.');

        $data = $request->validate(['note' => ['nullable', 'string', 'max:500']]);

        $record->update([
            'review_status' => 'reviewed',
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
            'review_note' => $data['note'] ?? null,
        ]);

        AuditLog::record('attendance.reviewed', $record, ['review_status' => 'needs_review'], ['note' => $data['note'] ?? null], $request->user()->id);

        return back()->with('success', 'Marked as reviewed.');
    }

    public function override(Request $request, AttendanceRecord $record): RedirectResponse
    {
        $this->authorizeEmployee($request, $record->user);

        if ($record->isLocked()) {
            return back()->withErrors(['override' => 'This day is locked by an approved payroll run.']);
        }

        $data = $request->validate([
            'status' => ['nullable', 'in:' . implode(',', array_keys(self::OVERRIDES))],
            'reason' => ['required', 'string', 'min:5', 'max:255'],
        ]);

        $old = $record->only(['status', 'payable_fraction', 'status_override']);

        if (empty($data['status'])) {
            $record->update(['status_override' => null, 'override_fraction' => null, 'override_reason' => null, 'override_by' => null]);
        } else {
            $record->update([
                'status_override' => $data['status'],
                'override_fraction' => self::OVERRIDES[$data['status']],
                'override_reason' => $data['reason'],
                'override_by' => $request->user()->id,
                'review_status' => 'reviewed',
                'reviewed_by' => $request->user()->id,
                'reviewed_at' => now(),
            ]);
        }

        $fresh = $this->calculator->calculate($record->user, $record->attendance_date);

        AuditLog::record('attendance.status_override', $record, $old, $fresh->only(['status', 'payable_fraction', 'status_override', 'override_reason']), $request->user()->id);

        return back()->with('success', 'Attendance status updated with your reason on record.');
    }

    public function approveOvertime(Request $request, AttendanceRecord $record): RedirectResponse
    {
        $this->authorizeEmployee($request, $record->user);
        abort_if($record->isLocked(), 422, 'This day is locked by payroll.');

        $approve = $request->boolean('approve', true);
        $record->update(['overtime_approved' => $approve]);

        AuditLog::record('attendance.overtime_' . ($approve ? 'approved' : 'withdrawn'), $record, [], ['minutes' => $record->overtime_minutes], $request->user()->id);

        return back()->with('success', $approve ? 'Overtime approved.' : 'Overtime approval withdrawn.');
    }

    /* ───────────────────────── helpers ───────────────────────── */

    private function authorizeEmployee(Request $request, User $employee): void
    {
        abort_unless(WorkforceScope::canSee($request->user(), $employee, 'attendance'), 403);
    }

    private function employeesFor(User $viewer, $clientId, string $search)
    {
        return WorkforceScope::employees($viewer, 'attendance')
            ->whereNotNull('employee_id')
            ->where('employment_status', 'active')
            ->when($clientId, fn ($q, $c) => $q->where('client_id', $c))
            ->when($search !== '', fn ($q) => $q->where(fn ($w) => $w
                ->where('first_name', 'like', "%{$search}%")
                ->orWhere('last_name', 'like', "%{$search}%")
                ->orWhere('employee_id', 'like', "%{$search}%")))
            ->with('client:id,name')
            ->orderBy('employee_id')
            ->get();
    }

    private function clients(User $viewer)
    {
        return Client::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
    }

    private function date(?string $value): Carbon
    {
        try {
            return $value ? Carbon::parse($value)->startOfDay() : today();
        } catch (\Throwable) {
            return today();
        }
    }

    private function month(?string $value): Carbon
    {
        try {
            return $value ? Carbon::createFromFormat('Y-m', $value)->startOfMonth() : today()->startOfMonth();
        } catch (\Throwable) {
            return today()->startOfMonth();
        }
    }
}
