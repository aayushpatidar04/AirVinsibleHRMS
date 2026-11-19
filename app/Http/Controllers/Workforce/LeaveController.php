<?php

namespace App\Http\Controllers\Workforce;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\LeaveApplication;
use App\Models\LeaveLedgerEntry;
use App\Models\LeaveType;
use App\Models\User;
use App\Services\Workforce\LeaveException;
use App\Services\Workforce\LeaveService;
use App\Services\Workforce\WorkforceScope;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LeaveController extends Controller
{
    public function __construct(private readonly LeaveService $leave)
    {
    }

    public function index(Request $request): Response
    {
        $viewer = $request->user();
        $ids = WorkforceScope::employees($viewer, 'leaves')->pluck('users.id');
        $status = $request->query('status', 'pending');

        $applications = LeaveApplication::query()
            ->whereIn('user_id', $ids)
            ->when($status !== 'all', fn ($q) => $q->where('status', $status))
            ->when($request->query('client_id'), fn ($q, $c) => $q->where('client_id', $c))
            ->with(['user:id,first_name,last_name,employee_id,reporting_to', 'leaveType:id,name', 'approvals.actor:id,first_name,last_name'])
            ->orderBy('start_date')
            ->paginate(20)
            ->withQueryString()
            ->through(fn (LeaveApplication $a) => [
                'id' => $a->id,
                'employee' => $a->user->full_name,
                'employee_id' => $a->user->employee_id,
                'type' => $a->leaveType?->name,
                'start_date' => $a->start_date->format('d M Y'),
                'end_date' => $a->end_date->format('d M Y'),
                'days' => $a->total_days,
                'half_day' => $a->is_half_day ? str_replace('_', ' ', (string) $a->half_day_session) : null,
                'reason' => $a->reason,
                'status' => $a->status,
                'balance' => $this->leave->balance($a->user, $a->leave_type_id),
                'approvals' => $a->approvals->map(fn ($ap) => [
                    'role' => str_replace('_', ' ', $ap->approver_role),
                    'status' => $ap->status,
                    'by' => $ap->actor?->full_name,
                    'note' => $ap->note,
                ]),
                'can_decide' => $this->leave->canDecide($viewer, $a),
                'can_cancel' => in_array($a->status, ['pending', 'approved'], true),
            ]);

        return Inertia::render('Workforce/Leave/Index', [
            'applications' => $applications,
            'filters' => ['status' => $status, 'client_id' => $request->query('client_id')],
            'clients' => Client::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'pendingCount' => LeaveApplication::query()->whereIn('user_id', $ids)->where('status', 'pending')->count(),
        ]);
    }

    public function approve(Request $request, LeaveApplication $application): RedirectResponse
    {
        return $this->decide($request, $application, fn () => $this->leave->approve($application, $request->user(), $request->input('note')), 'Leave approved.');
    }

    public function reject(Request $request, LeaveApplication $application): RedirectResponse
    {
        $data = $request->validate(['note' => ['required', 'string', 'max:500']]);

        return $this->decide($request, $application, fn () => $this->leave->reject($application, $request->user(), $data['note']), 'Leave rejected.');
    }

    public function cancel(Request $request, LeaveApplication $application): RedirectResponse
    {
        return $this->decide($request, $application, fn () => $this->leave->cancel($application, $request->user()), 'Leave cancelled.');
    }

    public function balances(Request $request): Response
    {
        $viewer = $request->user();
        $search = trim((string) $request->query('search'));

        $employees = WorkforceScope::employees($viewer, 'leaves')
            ->whereNotNull('employee_id')
            ->where('employment_status', 'active')
            ->when($request->query('client_id'), fn ($q, $c) => $q->where('client_id', $c))
            ->when($search !== '', fn ($q) => $q->where(fn ($w) => $w
                ->where('first_name', 'like', "%{$search}%")->orWhere('last_name', 'like', "%{$search}%")->orWhere('employee_id', 'like', "%{$search}%")))
            ->orderBy('employee_id')
            ->paginate(20)
            ->withQueryString()
            ->through(fn (User $u) => [
                'id' => $u->id,
                'name' => $u->full_name,
                'employee_id' => $u->employee_id,
                'balances' => $this->leave->balances($u)->map(fn ($b) => [
                    'leave_type_id' => $b['leave_type_id'], 'code' => $b['code'], 'balance' => $b['balance'], 'pending' => $b['pending'],
                ]),
            ]);

        $selected = $request->query('employee') ? User::find($request->query('employee')) : null;
        $ledger = null;

        if ($selected && WorkforceScope::canSee($viewer, $selected, 'leaves')) {
            $ledger = LeaveLedgerEntry::query()->where('user_id', $selected->id)->with('leaveType:id,code', 'creator:id,first_name,last_name')
                ->latest('effective_date')->latest('id')->limit(60)->get()->map(fn ($e) => [
                    'date' => $e->effective_date->format('d M Y'),
                    'type' => $e->leaveType?->code,
                    'entry' => $e->entry_type,
                    'days' => $e->days,
                    'remarks' => $e->remarks,
                    'by' => $e->creator?->full_name,
                ]);
        }

        return Inertia::render('Workforce/Leave/Balances', [
            'employees' => $employees,
            'filters' => ['search' => $search, 'client_id' => $request->query('client_id'), 'employee' => $request->query('employee')],
            'clients' => Client::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'leaveTypes' => LeaveType::query()->active()->orderBy('name')->get(['id', 'name', 'code']),
            'selected' => $selected && $ledger !== null ? ['id' => $selected->id, 'name' => $selected->full_name] : null,
            'ledger' => $ledger,
            'canAdjust' => $viewer->can('leaves.adjust-balance'),
        ]);
    }

    public function adjust(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'leave_type_id' => ['required', 'exists:leave_types,id'],
            'days' => ['required', 'numeric', 'between:-365,365'],
            'reason' => ['required', 'string', 'min:5', 'max:255'],
        ]);

        $employee = User::findOrFail($data['user_id']);
        abort_unless(WorkforceScope::canSee($request->user(), $employee, 'leaves'), 403);

        try {
            $this->leave->adjust($employee, (int) $data['leave_type_id'], (float) $data['days'], $data['reason'], $request->user());
        } catch (LeaveException $e) {
            return back()->withErrors(['adjust' => $e->getMessage()]);
        }

        return back()->with('success', 'Balance adjusted and recorded in the ledger.');
    }

    private function decide(Request $request, LeaveApplication $application, \Closure $action, string $message): RedirectResponse
    {
        abort_unless(WorkforceScope::canSee($request->user(), $application->user, 'leaves') || $application->user->reporting_to === $request->user()->id, 403);

        try {
            $action();
        } catch (LeaveException $e) {
            return back()->withErrors(['leave' => $e->getMessage()]);
        }

        return back()->with('success', $message);
    }
}
