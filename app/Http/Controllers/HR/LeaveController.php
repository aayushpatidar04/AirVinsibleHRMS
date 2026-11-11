<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Models\LeaveApplication;
use App\Models\LeaveBalance;
use App\Models\LeavePolicy;
use App\Models\LeaveType;
use App\Models\Client;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class LeaveController extends Controller
{
    public function index(Request $request)
    {
        $query = LeaveApplication::query()
            ->with(['user', 'client', 'leaveType', 'approvedBy']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->filled('client_id')) {
            $query->where('client_id', $request->client_id);
        }

        if ($request->filled('leave_type_id')) {
            $query->where('leave_type_id', $request->leave_type_id);
        }

        $applications = $query->orderByDesc('created_at')
            ->paginate(15)
            ->withQueryString();

        $clients = Client::active()->orderBy('name')->get();
        $users = User::active()->orderBy('first_name')->get();
        $leaveTypes = LeaveType::active()->orderBy('name')->get();

        return Inertia::render('HR/Leave/Index', [
            'applications' => $applications,
            'clients' => $clients,
            'users' => $users,
            'leaveTypes' => $leaveTypes,
            'filters' => $request->only(['status', 'user_id', 'client_id', 'leave_type_id']),
        ]);
    }

    public function types(Request $request)
    {
        $query = LeaveType::query()->with('client');

        if ($request->filled('client_id')) {
            $query->where('client_id', $request->client_id);
        }

        $types = $query->orderBy('name')->paginate(15)->withQueryString();
        $clients = Client::active()->orderBy('name')->get();

        return Inertia::render('HR/Leave/Types', [
            'types' => $types,
            'clients' => $clients,
            'filters' => $request->only(['client_id']),
        ]);
    }

    public function storeType(Request $request)
    {
        $data = $request->validate([
            'client_id' => 'required|exists:clients,id',
            'name' => 'required|string|max:100',
            'code' => 'required|string|max:10|unique:leave_types,code',
            'description' => 'nullable|string|max:500',
            'is_paid' => 'boolean',
            'allow_half_day' => 'boolean',
            'allow_during_probation' => 'boolean',
            'annual_entitlement' => 'nullable|integer|min:0',
            'monthly_entitlement' => 'nullable|integer|min:0',
            'accrual_frequency' => 'nullable|string|max:50',
            'max_consecutive_days' => 'nullable|integer|min:0',
            'notice_period_days' => 'nullable|integer|min:0',
            'allow_carry_forward' => 'boolean',
            'max_carry_forward_days' => 'nullable|integer|min:0',
            'allow_encashment' => 'boolean',
            'allow_negative_balance' => 'boolean',
            'is_active' => 'boolean',
        ]);

        LeaveType::create($data);

        return back()->with('success', 'Leave type created successfully.');
    }

    public function balances(Request $request)
    {
        $query = LeaveBalance::query()
            ->with(['user', 'client', 'leaveType']);

        if ($request->filled('client_id')) {
            $query->where('client_id', $request->client_id);
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->filled('year')) {
            $query->where('year', $request->year);
        }

        $balances = $query->orderBy('user_id')
            ->paginate(15)
            ->withQueryString();

        $clients = Client::active()->orderBy('name')->get();
        $users = User::active()->orderBy('first_name')->get();

        return Inertia::render('HR/Leave/Balances', [
            'balances' => $balances,
            'clients' => $clients,
            'users' => $users,
            'filters' => $request->only(['client_id', 'user_id', 'year']),
        ]);
    }

    public function apply(Request $request)
    {
        $data = $request->validate([
            'leave_type_id' => 'required|exists:leave_types,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'reason' => 'required|string|max:1000',
            'is_half_day' => 'boolean',
            'half_day_session' => 'nullable|in:morning,afternoon',
        ]);

        $leaveType = LeaveType::findOrFail($data['leave_type_id']);
        $user = auth()->user();

        // Calculate total days
        $totalDays = $data['start_date']->diffInDays($data['end_date']) + 1;
        if ($data['is_half_day']) {
            $totalDays = 0.5;
        }

        LeaveApplication::create([
            'user_id' => $user->id,
            'client_id' => $user->branch_id, // Using branch as client reference
            'leave_type_id' => $data['leave_type_id'],
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'],
            'total_days' => $totalDays,
            'is_half_day' => $data['is_half_day'] ?? false,
            'half_day_session' => $data['half_day_session'] ?? null,
            'reason' => $data['reason'],
            'status' => LeaveApplication::STATUS_PENDING,
            'applied_on' => now(),
        ]);

        return back()->with('success', 'Leave application submitted successfully.');
    }

    public function approve(Request $request, LeaveApplication $application)
    {
        $data = $request->validate([
            'approved' => 'required|boolean',
            'rejection_reason' => 'nullable|string|max:1000',
            'admin_notes' => 'nullable|string|max:1000',
        ]);

        if ($data['approved']) {
            $application->update([
                'status' => LeaveApplication::STATUS_APPROVED,
                'approved_by' => auth()->id(),
                'approved_at' => now(),
                'admin_notes' => $data['admin_notes'] ?? null,
            ]);
        } else {
            $application->update([
                'status' => LeaveApplication::STATUS_REJECTED,
                'approved_by' => auth()->id(),
                'approved_at' => now(),
                'rejection_reason' => $data['rejection_reason'],
                'admin_notes' => $data['admin_notes'] ?? null,
            ]);
        }

        return back()->with('success', 'Leave application ' . ($data['approved'] ? 'approved' : 'rejected') . '.');
    }
}
