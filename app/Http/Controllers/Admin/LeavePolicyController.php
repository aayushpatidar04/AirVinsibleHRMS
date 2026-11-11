<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\LeavePolicy;
use App\Models\LeaveType;
use Illuminate\Http\Request;
use Inertia\Inertia;

class LeavePolicyController extends Controller
{
    public function index(Request $request)
    {
        $query = LeavePolicy::query()
            ->with(['client', 'leaveType']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->whereHas('client', fn($c) => $c->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('leaveType', fn($lt) => $lt->where('name', 'like', "%{$search}%"));
            });
        }

        if ($request->filled('client_id')) {
            $query->where('client_id', $request->client_id);
        }

        if ($request->filled('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $policies = $query->orderBy('id', 'desc')
            ->paginate(15)
            ->withQueryString();

        $clients = Client::active()->orderBy('name')->get();
        $leaveTypes = LeaveType::active()->orderBy('name')->get();

        return Inertia::render('Admin/LeavePolicies/Index', [
            'policies' => $policies,
            'clients' => $clients,
            'leaveTypes' => $leaveTypes,
            'filters' => $request->only(['search', 'client_id', 'is_active']),
        ]);
    }

    public function create()
    {
        $clients = Client::active()->orderBy('name')->get();
        $leaveTypes = LeaveType::active()->orderBy('name')->get();

        return Inertia::render('Admin/LeavePolicies/Create', [
            'clients' => $clients,
            'leaveTypes' => $leaveTypes,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'client_id' => 'required|exists:clients,id',
            'leave_type_id' => 'required|exists:leave_types,id',
            'employee_category' => 'required|string|max:100',
            'annual_entitlement' => 'required|integer|min:0',
            'monthly_entitlement' => 'nullable|integer|min:0',
            'accrual_frequency' => 'nullable|string|max:50',
            'credit_date' => 'nullable|integer|min:1|max:31',
            'eligibility_service_days' => 'nullable|integer|min:0',
            'allow_carry_forward' => 'boolean',
            'max_carry_forward_days' => 'nullable|integer|min:0',
            'allow_encashment' => 'boolean',
            'max_consecutive_days' => 'nullable|integer|min:0',
            'notice_period_days' => 'nullable|integer|min:0',
            'allow_half_day' => 'boolean',
            'allow_during_probation' => 'boolean',
            'allow_negative_balance' => 'boolean',
            'expiry_days' => 'nullable|integer|min:0',
            'approval_hierarchy' => 'nullable|string|max:255',
            'count_weekly_offs' => 'boolean',
            'count_public_holidays' => 'boolean',
            'effective_from' => 'required|date',
            'effective_to' => 'nullable|date|after:effective_from',
            'is_active' => 'boolean',
        ]);

        $data['allow_carry_forward'] = $request->boolean('allow_carry_forward');
        $data['allow_encashment'] = $request->boolean('allow_encashment');
        $data['allow_half_day'] = $request->boolean('allow_half_day');
        $data['allow_during_probation'] = $request->boolean('allow_during_probation');
        $data['allow_negative_balance'] = $request->boolean('allow_negative_balance');
        $data['count_weekly_offs'] = $request->boolean('count_weekly_offs');
        $data['count_public_holidays'] = $request->boolean('count_public_holidays');
        $data['is_active'] = $request->boolean('is_active');

        $policy = LeavePolicy::create($data);

        return redirect()
            ->route('admin.leave-policies.show', $policy)
            ->with('success', 'Leave policy created successfully.');
    }

    public function show(LeavePolicy $policy)
    {
        $policy->load(['client', 'leaveType']);

        return Inertia::render('Admin/LeavePolicies/Show', [
            'policy' => $policy,
        ]);
    }

    public function edit(LeavePolicy $policy)
    {
        $clients = Client::active()->orderBy('name')->get();
        $leaveTypes = LeaveType::active()->orderBy('name')->get();

        return Inertia::render('Admin/LeavePolicies/Edit', [
            'policy' => $policy,
            'clients' => $clients,
            'leaveTypes' => $leaveTypes,
        ]);
    }

    public function update(Request $request, LeavePolicy $policy)
    {
        $data = $request->validate([
            'client_id' => 'required|exists:clients,id',
            'leave_type_id' => 'required|exists:leave_types,id',
            'employee_category' => 'required|string|max:100',
            'annual_entitlement' => 'required|integer|min:0',
            'monthly_entitlement' => 'nullable|integer|min:0',
            'accrual_frequency' => 'nullable|string|max:50',
            'credit_date' => 'nullable|integer|min:1|max:31',
            'eligibility_service_days' => 'nullable|integer|min:0',
            'allow_carry_forward' => 'boolean',
            'max_carry_forward_days' => 'nullable|integer|min:0',
            'allow_encashment' => 'boolean',
            'max_consecutive_days' => 'nullable|integer|min:0',
            'notice_period_days' => 'nullable|integer|min:0',
            'allow_half_day' => 'boolean',
            'allow_during_probation' => 'boolean',
            'allow_negative_balance' => 'boolean',
            'expiry_days' => 'nullable|integer|min:0',
            'approval_hierarchy' => 'nullable|string|max:255',
            'count_weekly_offs' => 'boolean',
            'count_public_holidays' => 'boolean',
            'effective_from' => 'required|date',
            'effective_to' => 'nullable|date|after:effective_from',
            'is_active' => 'boolean',
        ]);

        $data['allow_carry_forward'] = $request->boolean('allow_carry_forward');
        $data['allow_encashment'] = $request->boolean('allow_encashment');
        $data['allow_half_day'] = $request->boolean('allow_half_day');
        $data['allow_during_probation'] = $request->boolean('allow_during_probation');
        $data['allow_negative_balance'] = $request->boolean('allow_negative_balance');
        $data['count_weekly_offs'] = $request->boolean('count_weekly_offs');
        $data['count_public_holidays'] = $request->boolean('count_public_holidays');
        $data['is_active'] = $request->boolean('is_active');

        $policy->update($data);

        return redirect()
            ->route('admin.leave-policies.show', $policy)
            ->with('success', 'Leave policy updated successfully.');
    }

    public function destroy(LeavePolicy $policy)
    {
        $policy->delete();

        return redirect()
            ->route('admin.leave-policies.index')
            ->with('success', 'Leave policy deleted successfully.');
    }
}
