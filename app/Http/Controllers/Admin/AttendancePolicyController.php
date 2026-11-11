<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AttendancePolicy;
use App\Models\Client;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AttendancePolicyController extends Controller
{
    public function index(Request $request)
    {
        $query = AttendancePolicy::query()
            ->with('client');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($request->filled('client_id')) {
            $query->where('client_id', $request->client_id);
        }

        if ($request->filled('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $policies = $query->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        $clients = Client::active()->orderBy('name')->get();

        return Inertia::render('Admin/AttendancePolicies/Index', [
            'policies' => $policies,
            'clients' => $clients,
            'filters' => $request->only(['search', 'client_id', 'is_active']),
        ]);
    }

    public function create()
    {
        $clients = Client::active()->orderBy('name')->get();

        return Inertia::render('Admin/AttendancePolicies/Create', [
            'clients' => $clients,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'client_id' => 'required|exists:clients,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'shift_start_time' => 'required|date_format:H:i',
            'shift_end_time' => 'required|date_format:H:i|after:shift_start_time',
            'grace_period_start' => 'nullable|date_format:H:i',
            'grace_period_end' => 'nullable|date_format:H:i',
            'grace_period_minutes' => 'nullable|integer|min:0',
            'max_late_arrivals' => 'nullable|integer|min:0',
            'allow_early_departure' => 'boolean',
            'min_required_hours' => 'nullable|integer|min:0',
            'min_session_duration_minutes' => 'nullable|integer|min:0',
            'mandatory_portal_punch' => 'boolean',
            'mandatory_machine_punch' => 'boolean',
            'break_duration_minutes' => 'nullable|integer|min:0',
            'max_break_minutes' => 'nullable|integer|min:0',
            'track_idle_time' => 'boolean',
            'overtime_eligible' => 'boolean',
            'overtime_multiplier' => 'nullable|numeric|min:0',
            'weekly_offs' => 'nullable|array',
            'public_holidays' => 'nullable|array',
            'half_day_threshold_hours' => 'nullable|numeric|min:0',
            'full_day_threshold_hours' => 'nullable|numeric|min:0',
            'allow_negative_leave_balance' => 'boolean',
            'deduct_late_arrival' => 'boolean',
            'deduct_early_departure' => 'boolean',
            'effective_from' => 'required|date',
            'effective_to' => 'nullable|date|after:effective_from',
            'is_active' => 'boolean',
        ]);

        $data['weekly_offs'] = $data['weekly_offs'] ?? [];
        $data['public_holidays'] = $data['public_holidays'] ?? [];
        $data['allow_early_departure'] = $request->boolean('allow_early_departure');
        $data['mandatory_portal_punch'] = $request->boolean('mandatory_portal_punch');
        $data['mandatory_machine_punch'] = $request->boolean('mandatory_machine_punch');
        $data['track_idle_time'] = $request->boolean('track_idle_time');
        $data['overtime_eligible'] = $request->boolean('overtime_eligible');
        $data['allow_negative_leave_balance'] = $request->boolean('allow_negative_leave_balance');
        $data['deduct_late_arrival'] = $request->boolean('deduct_late_arrival');
        $data['deduct_early_departure'] = $request->boolean('deduct_early_departure');
        $data['is_active'] = $request->boolean('is_active');

        $policy = AttendancePolicy::create($data);

        return redirect()
            ->route('admin.attendance-policies.show', $policy)
            ->with('success', 'Attendance policy created successfully.');
    }

    public function show(AttendancePolicy $policy)
    {
        $policy->load('client');

        return Inertia::render('Admin/AttendancePolicies/Show', [
            'policy' => $policy,
        ]);
    }

    public function edit(AttendancePolicy $policy)
    {
        $clients = Client::active()->orderBy('name')->get();

        return Inertia::render('Admin/AttendancePolicies/Edit', [
            'policy' => $policy,
            'clients' => $clients,
        ]);
    }

    public function update(Request $request, AttendancePolicy $policy)
    {
        $data = $request->validate([
            'client_id' => 'required|exists:clients,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'shift_start_time' => 'required|date_format:H:i',
            'shift_end_time' => 'required|date_format:H:i|after:shift_start_time',
            'grace_period_start' => 'nullable|date_format:H:i',
            'grace_period_end' => 'nullable|date_format:H:i',
            'grace_period_minutes' => 'nullable|integer|min:0',
            'max_late_arrivals' => 'nullable|integer|min:0',
            'allow_early_departure' => 'boolean',
            'min_required_hours' => 'nullable|integer|min:0',
            'min_session_duration_minutes' => 'nullable|integer|min:0',
            'mandatory_portal_punch' => 'boolean',
            'mandatory_machine_punch' => 'boolean',
            'break_duration_minutes' => 'nullable|integer|min:0',
            'max_break_minutes' => 'nullable|integer|min:0',
            'track_idle_time' => 'boolean',
            'overtime_eligible' => 'boolean',
            'overtime_multiplier' => 'nullable|numeric|min:0',
            'weekly_offs' => 'nullable|array',
            'public_holidays' => 'nullable|array',
            'half_day_threshold_hours' => 'nullable|numeric|min:0',
            'full_day_threshold_hours' => 'nullable|numeric|min:0',
            'allow_negative_leave_balance' => 'boolean',
            'deduct_late_arrival' => 'boolean',
            'deduct_early_departure' => 'boolean',
            'effective_from' => 'required|date',
            'effective_to' => 'nullable|date|after:effective_from',
            'is_active' => 'boolean',
        ]);

        $data['weekly_offs'] = $data['weekly_offs'] ?? [];
        $data['public_holidays'] = $data['public_holidays'] ?? [];
        $data['allow_early_departure'] = $request->boolean('allow_early_departure');
        $data['mandatory_portal_punch'] = $request->boolean('mandatory_portal_punch');
        $data['mandatory_machine_punch'] = $request->boolean('mandatory_machine_punch');
        $data['track_idle_time'] = $request->boolean('track_idle_time');
        $data['overtime_eligible'] = $request->boolean('overtime_eligible');
        $data['allow_negative_leave_balance'] = $request->boolean('allow_negative_leave_balance');
        $data['deduct_late_arrival'] = $request->boolean('deduct_late_arrival');
        $data['deduct_early_departure'] = $request->boolean('deduct_early_departure');
        $data['is_active'] = $request->boolean('is_active');

        $policy->update($data);

        return redirect()
            ->route('admin.attendance-policies.show', $policy)
            ->with('success', 'Attendance policy updated successfully.');
    }

    public function destroy(AttendancePolicy $policy)
    {
        $policy->delete();

        return redirect()
            ->route('admin.attendance-policies.index')
            ->with('success', 'Attendance policy deleted successfully.');
    }
}
