<?php

namespace App\Http\Controllers\Workforce;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Client;
use App\Models\PayrollAdjustment;
use App\Models\PayrollPeriod;
use App\Models\PayrollRun;
use App\Models\PayrollRunItem;
use App\Models\User;
use App\Services\Workforce\PayrollException;
use App\Services\Workforce\PayrollRunService;
use App\Services\Workforce\WorkforceScope;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PayrollRunController extends Controller
{
    public function __construct(private readonly PayrollRunService $runs)
    {
    }

    public function index(Request $request): Response
    {
        $runs = PayrollRun::query()
            ->with('client:id,name', 'period:id,name,start_date,end_date')
            ->when($request->query('client_id'), fn ($q, $c) => $q->where('client_id', $c))
            ->when($request->query('status'), fn ($q, $s) => $q->where('status', $s))
            ->latest('id')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (PayrollRun $r) => [
                'id' => $r->id,
                'run_number' => $r->run_number,
                'client' => $r->client->name,
                'period' => $r->period->name,
                'status' => $r->status,
                'status_label' => $r->label(),
                'employees' => $r->employee_count,
                'exceptions' => $r->exception_count,
                'net' => $r->total_net,
                'version' => $r->version,
            ]);

        $openPeriodIds = PayrollRun::query()->where('status', '!=', PayrollRun::CLOSED)->pluck('payroll_period_id');

        return Inertia::render('Workforce/Payroll/Index', [
            'runs' => $runs,
            'filters' => ['client_id' => $request->query('client_id'), 'status' => $request->query('status')],
            'clients' => Client::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'statuses' => PayrollRun::STATUSES,
            'availablePeriods' => PayrollPeriod::query()->with('client:id,name')
                ->where('status', 'open')->whereNotIn('id', $openPeriodIds)->orderByDesc('start_date')->get()
                ->map(fn ($p) => ['id' => $p->id, 'label' => $p->client->name . ' — ' . $p->name]),
            'canCreate' => $request->user()->can('payroll.generate'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate(['payroll_period_id' => ['required', 'exists:payroll_periods,id']]);

        try {
            $run = $this->runs->create(PayrollPeriod::with('client')->findOrFail($data['payroll_period_id']), $request->user());
        } catch (PayrollException $e) {
            return back()->withErrors(['run' => $e->getMessage()]);
        }

        return redirect()->route('workforce.payroll.runs.show', $run)->with('success', 'Payroll run created. Calculate it to see the results.');
    }

    public function show(Request $request, PayrollRun $run): Response
    {
        $viewer = $request->user();
        $run->load('client:id,name', 'period', 'creator:id,first_name,last_name');

        $visibleIds = WorkforceScope::seesEveryone($viewer, 'payroll')
            ? null
            : WorkforceScope::visibleIds($viewer, 'payroll');

        $items = $run->items()
            ->when($visibleIds !== null, fn ($q) => $q->whereIn('user_id', $visibleIds))
            ->with('user:id,first_name,last_name,employee_id,designation')
            ->get()
            ->sortBy(fn ($i) => $i->user->employee_id)
            ->values();

        $adjustments = PayrollAdjustment::query()->active()->where('payroll_run_id', $run->id)
            ->when($visibleIds !== null, fn ($q) => $q->whereIn('user_id', $visibleIds))
            ->with('user:id,first_name,last_name,employee_id', 'creator:id,first_name,last_name')
            ->latest()->get();

        $paid = $items->reject->isHeld();

        $actions = collect(PayrollRunService::ACTIONS)
            ->filter(fn ($a) => in_array($run->status, $a[0], true) && $viewer->can($a[2]))
            ->map(fn ($a, $key) => ['key' => $key, 'label' => $a[3], 'needs_note' => in_array($key, ['send_back', 'reopen'], true), 'danger' => in_array($key, ['reopen', 'send_back'], true)])
            ->values();

        return Inertia::render('Workforce/Payroll/Show', [
            'run' => [
                'id' => $run->id,
                'run_number' => $run->run_number,
                'client' => $run->client->name,
                'period' => $run->period->name,
                'period_range' => $run->period->start_date->format('d M') . ' – ' . $run->period->end_date->format('d M Y'),
                'status' => $run->status,
                'status_label' => $run->label(),
                'version' => $run->version,
                'editable' => $run->isEditable(),
                'calculated_at' => $run->calculated_at?->format('d M Y, h:i A'),
                'notes' => $run->notes,
                'created_by' => $run->creator?->full_name,
            ],
            'totals' => [
                'employees' => $paid->count(),
                'held' => $items->filter->isHeld()->count(),
                'blocking' => $items->reject->isHeld()->filter->hasBlockingExceptions()->count(),
                'gross' => round($paid->sum('gross_earnings'), 2),
                'deductions' => round($paid->sum('total_deductions'), 2),
                'net' => round($paid->sum('net_pay'), 2),
                'employer' => round($paid->sum('employer_contributions'), 2),
            ],
            'items' => $items->map(fn (PayrollRunItem $i) => [
                'id' => $i->id,
                'user_id' => $i->user_id,
                'employee_id' => $i->user->employee_id,
                'name' => $i->user->full_name,
                'designation' => $i->user->designation,
                'status' => $i->status,
                'hold_reason' => $i->hold_reason,
                'payable_days' => $i->payable_days,
                'calendar_days' => $i->calendar_days,
                'present_days' => $i->present_days,
                'absent_days' => $i->absent_days,
                'paid_leave_days' => $i->paid_leave_days,
                'unpaid_leave_days' => $i->unpaid_leave_days,
                'weekly_off_days' => $i->weekly_off_days,
                'holiday_days' => $i->holiday_days,
                'lop_days' => $i->lop_days,
                'late_count' => $i->late_count,
                'early_count' => $i->early_count,
                'worked_hours' => round($i->worked_minutes / 60, 1),
                'overtime_hours' => round($i->overtime_minutes / 60, 1),
                'gross' => $i->gross_earnings,
                'deductions_total' => $i->total_deductions,
                'net' => $i->net_pay,
                'earnings' => $i->earnings,
                'deductions' => $i->deductions,
                'employer' => $i->employer,
                'exceptions' => $i->exceptions ?? [],
                'blocking' => $i->hasBlockingExceptions(),
            ]),
            'adjustments' => $adjustments->map(fn ($a) => [
                'id' => $a->id,
                'employee' => $a->user->full_name,
                'employee_id' => $a->user->employee_id,
                'type' => PayrollAdjustment::TYPES[$a->type] ?? $a->type,
                'label' => $a->label,
                'amount' => $a->amount,
                'is_deduction' => $a->is_deduction,
                'reason' => $a->reason,
                'by' => $a->creator?->full_name,
            ]),
            'adjustmentTypes' => PayrollAdjustment::TYPES,
            'actions' => $actions,
            'can' => [
                'calculate' => $run->isEditable() && ($viewer->can('payroll.generate') || $viewer->can('payroll.recalculate')),
                'adjust' => $run->isEditable() && $viewer->can('payroll.adjust'),
                'hold' => $run->isEditable() && ($viewer->can('payroll.generate') || $viewer->can('payroll.recalculate')),
            ],
            'history' => AuditLog::query()
                ->where('model_type', $run->getMorphClass())->where('model_id', $run->id)
                ->with('user:id,first_name,last_name')->latest('created_at')->limit(30)->get()
                ->map(fn ($l) => [
                    'event' => str_replace(['payroll.', '_'], ['', ' '], $l->event),
                    'by' => $l->user?->full_name,
                    'at' => $l->created_at->format('d M Y, h:i A'),
                    'note' => $l->new_values['note'] ?? null,
                ]),
        ]);
    }

    public function calculate(Request $request, PayrollRun $run): RedirectResponse
    {
        try {
            $this->runs->calculate($run, $request->user());
        } catch (PayrollException $e) {
            return back()->withErrors(['run' => $e->getMessage()]);
        }

        return back()->with('success', 'Payroll calculated from approved attendance and leave.');
    }

    public function transition(Request $request, PayrollRun $run, string $action): RedirectResponse
    {
        $request->validate(['note' => ['nullable', 'string', 'max:500']]);

        try {
            $this->runs->transition($run, $action, $request->user(), $request->input('note'));
        } catch (PayrollException $e) {
            return back()->withErrors(['run' => $e->getMessage()]);
        }

        return back()->with('success', PayrollRunService::ACTIONS[$action][3] . ' done.');
    }

    public function hold(Request $request, PayrollRun $run, PayrollRunItem $item): RedirectResponse
    {
        abort_unless($item->payroll_run_id === $run->id, 404);
        abort_unless(WorkforceScope::canSee($request->user(), $item->user, 'payroll'), 403);

        $data = $request->validate(['hold' => ['required', 'boolean'], 'reason' => ['nullable', 'string', 'max:255']]);

        try {
            $this->runs->hold($item, (bool) $data['hold'], $data['reason'] ?? null, $request->user());
        } catch (PayrollException $e) {
            return back()->withErrors(['run' => $e->getMessage()]);
        }

        return back()->with('success', $data['hold'] ? 'Employee held out of this run.' : 'Employee released back into the run.');
    }

    public function storeAdjustment(Request $request, PayrollRun $run): RedirectResponse
    {
        $data = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'type' => ['required', 'in:' . implode(',', array_keys(PayrollAdjustment::TYPES))],
            'label' => ['required', 'string', 'max:120'],
            'amount' => ['required', 'numeric', 'gt:0', 'max:10000000'],
            'reason' => ['required', 'string', 'min:5', 'max:255'],
        ]);

        $employee = User::findOrFail($data['user_id']);
        abort_unless(WorkforceScope::canSee($request->user(), $employee, 'payroll'), 403);

        try {
            $this->runs->addAdjustment($run, $employee, $data, $request->user());
        } catch (PayrollException $e) {
            return back()->withErrors(['adjustment' => $e->getMessage()]);
        }

        return back()->with('success', 'Adjustment added and payroll for the employee recalculated.');
    }

    public function voidAdjustment(Request $request, PayrollRun $run, PayrollAdjustment $adjustment): RedirectResponse
    {
        abort_unless($adjustment->payroll_run_id === $run->id, 404);

        try {
            $this->runs->voidAdjustment($adjustment, $request->user());
        } catch (PayrollException $e) {
            return back()->withErrors(['adjustment' => $e->getMessage()]);
        }

        return back()->with('success', 'Adjustment removed.');
    }
}
