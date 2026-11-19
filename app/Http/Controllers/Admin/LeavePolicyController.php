<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Client;
use App\Models\LeavePolicy;
use App\Models\LeaveType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LeavePolicyController extends Controller
{
    public function index(Request $request): Response
    {
        $policies = LeavePolicy::query()
            ->with('client:id,name', 'leaveType:id,name,code,is_paid')
            ->when($request->query('client_id'), fn ($q, $c) => $q->where('client_id', $c))
            ->when($request->query('leave_type_id'), fn ($q, $t) => $q->where('leave_type_id', $t))
            ->orderByDesc('effective_from')->paginate(15)->withQueryString()
            ->through(fn (LeavePolicy $p) => [
                'id' => $p->id,
                'client' => $p->client?->name ?? 'All clients',
                'leave_type' => $p->leaveType?->name,
                'is_paid' => (bool) $p->leaveType?->is_paid,
                'employment_type' => $p->employment_type ? str_replace('_', ' ', $p->employment_type) : 'Everyone',
                'effective_from' => $p->effective_from->format('d M Y'),
                'effective_to' => $p->effective_to?->format('d M Y'),
                'is_active' => $p->is_active,
                'accrual' => $p->accrual_frequency === 'none' ? 'No accrual' : rtrim(rtrim(number_format($p->accrual_amount, 2), '0'), '.') . ' day(s) ' . $p->accrual_frequency,
                'levels' => implode(' → ', array_map(fn ($l) => str_replace('_', ' ', $l), $p->levels())),
            ]);

        return Inertia::render('Admin/LeavePolicies/Index', [
            'policies' => $policies,
            'clients' => Client::active()->orderBy('name')->get(['id', 'name']),
            'leaveTypes' => LeaveType::active()->orderBy('name')->get(['id', 'name']),
            'filters' => $request->only(['client_id', 'leave_type_id']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/LeavePolicies/Form', $this->formProps(null));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        if ($clash = $this->overlap($data)) {
            return back()->withErrors(['effective_from' => $clash])->withInput();
        }

        $policy = LeavePolicy::create($data);
        AuditLog::record('leave_policy.created', $policy, [], $data, $request->user()->id);

        return redirect()->route('admin.leave-policies.index')->with('success', 'Leave policy created.');
    }

    public function edit(LeavePolicy $leavePolicy): Response
    {
        return Inertia::render('Admin/LeavePolicies/Form', $this->formProps($leavePolicy));
    }

    public function update(Request $request, LeavePolicy $leavePolicy): RedirectResponse
    {
        $data = $this->validated($request);

        if ($clash = $this->overlap($data, $leavePolicy->id)) {
            return back()->withErrors(['effective_from' => $clash])->withInput();
        }

        $old = $leavePolicy->getAttributes();
        $leavePolicy->update($data);
        AuditLog::record('leave_policy.updated', $leavePolicy, $old, $data, $request->user()->id);

        return redirect()->route('admin.leave-policies.index')->with('success', 'Leave policy saved.');
    }

    public function destroy(Request $request, LeavePolicy $leavePolicy): RedirectResponse
    {
        AuditLog::record('leave_policy.deleted', $leavePolicy, $leavePolicy->getAttributes(), [], $request->user()->id);
        $leavePolicy->delete();

        return back()->with('success', 'Leave policy removed.');
    }

    private function formProps(?LeavePolicy $policy): array
    {
        return [
            'policy' => $policy ? $policy->only([
                'id', 'client_id', 'leave_type_id', 'employment_type', 'is_active', 'accrual_frequency', 'accrual_amount', 'annual_cap',
                'credit_day', 'eligible_after_days', 'probation_days', 'allow_during_probation', 'allow_half_day', 'max_consecutive_days',
                'min_notice_days', 'allow_negative', 'max_negative_days', 'carry_forward_mode', 'carry_forward_cap', 'expiry_months',
                'allow_encashment', 'count_weekly_offs', 'count_holidays', 'approval_levels',
            ]) + ['effective_from' => $policy->effective_from->toDateString(), 'effective_to' => $policy->effective_to?->toDateString()] : null,
            'clients' => Client::active()->orderBy('name')->get(['id', 'name']),
            'leaveTypes' => LeaveType::active()->orderBy('name')->get(['id', 'name', 'client_id']),
            'employmentTypes' => ['full_time', 'part_time', 'contract', 'internship', 'consultant'],
        ];
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'client_id' => ['nullable', 'exists:clients,id'],
            'leave_type_id' => ['required', 'exists:leave_types,id'],
            'employment_type' => ['nullable', 'string', 'max:30'],
            'effective_from' => ['required', 'date'],
            'effective_to' => ['nullable', 'date', 'after_or_equal:effective_from'],
            'is_active' => ['boolean'],
            'accrual_frequency' => ['required', 'in:monthly,yearly,upfront,none'],
            'accrual_amount' => ['nullable', 'numeric', 'between:0,365'],
            'annual_cap' => ['nullable', 'numeric', 'between:0,365'],
            'credit_day' => ['nullable', 'integer', 'between:1,28'],
            'eligible_after_days' => ['nullable', 'integer', 'between:0,730'],
            'probation_days' => ['nullable', 'integer', 'between:0,730'],
            'allow_during_probation' => ['boolean'],
            'allow_half_day' => ['boolean'],
            'max_consecutive_days' => ['nullable', 'numeric', 'between:0.5,365'],
            'min_notice_days' => ['nullable', 'integer', 'between:0,90'],
            'allow_negative' => ['boolean'],
            'max_negative_days' => ['nullable', 'numeric', 'between:0,30'],
            'carry_forward_mode' => ['required', 'in:none,capped,all'],
            'carry_forward_cap' => ['nullable', 'numeric', 'between:0,365'],
            'expiry_months' => ['nullable', 'integer', 'between:1,36'],
            'allow_encashment' => ['boolean'],
            'count_weekly_offs' => ['boolean'],
            'count_holidays' => ['boolean'],
            'approval_levels' => ['nullable', 'array'],
            'approval_levels.*' => ['in:reporting_manager,hr'],
        ]);

        foreach (['accrual_amount', 'credit_day', 'eligible_after_days', 'probation_days', 'min_notice_days', 'max_negative_days'] as $f) {
            $data[$f] = $data[$f] ?? ($f === 'credit_day' ? 1 : 0);
        }

        $data['approval_levels'] = array_values($data['approval_levels'] ?? ['reporting_manager']) ?: ['reporting_manager'];
        $data['is_active'] = $data['is_active'] ?? true;

        return $data;
    }

    private function overlap(array $data, ?int $ignoreId = null): ?string
    {
        if (!($data['is_active'] ?? true)) {
            return null;
        }

        $clash = LeavePolicy::query()
            ->where('leave_type_id', $data['leave_type_id'])->where('is_active', true)
            ->where('client_id', $data['client_id'] ?? null)
            ->where('employment_type', $data['employment_type'] ?? null)
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->whereDate('effective_from', '<=', $data['effective_to'] ?? '9999-12-31')
            ->where(fn ($q) => $q->whereNull('effective_to')->orWhereDate('effective_to', '>=', $data['effective_from']))
            ->exists();

        return $clash ? 'Another active policy already covers this leave type, client and employment type in those dates.' : null;
    }
}
