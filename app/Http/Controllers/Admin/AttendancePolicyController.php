<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AttendancePolicy;
use App\Models\AuditLog;
use App\Models\Client;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AttendancePolicyController extends Controller
{
    public function index(Request $request): Response
    {
        $policies = AttendancePolicy::query()
            ->with('client:id,name')
            ->when($request->query('client_id'), fn ($q, $c) => $q->where('client_id', $c))
            ->when($request->filled('is_active'), fn ($q) => $q->where('is_active', $request->boolean('is_active')))
            ->when($request->query('search'), fn ($q, $s) => $q->where('name', 'like', "%{$s}%"))
            ->orderBy('client_id')->orderByDesc('effective_from')
            ->paginate(15)->withQueryString()
            ->through(fn (AttendancePolicy $p) => [
                'id' => $p->id,
                'name' => $p->name,
                'client' => $p->client?->name,
                'effective_from' => $p->effective_from->format('d M Y'),
                'effective_to' => $p->effective_to?->format('d M Y'),
                'is_active' => $p->is_active,
                'current' => $p->is_active && $p->effective_from->lte(today()) && (!$p->effective_to || $p->effective_to->gte(today())),
                'summary' => sprintf('%dh full day · grace %d min%s', $p->full_day_minutes / 60, $p->grace_minutes, $p->geofence_required ? ' · geofence' : ''),
            ]);

        return Inertia::render('Admin/AttendancePolicies/Index', [
            'policies' => $policies,
            'clients' => Client::active()->orderBy('name')->get(['id', 'name']),
            'filters' => $request->only(['search', 'client_id', 'is_active']),
        ]);
    }

    public function create(Request $request): Response
    {
        return Inertia::render('Admin/AttendancePolicies/Form', [
            'policy' => null,
            'clients' => Client::active()->orderBy('name')->get(['id', 'name']),
            'defaults' => ['client_id' => $request->query('client_id'), 'effective_from' => today()->toDateString()],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        if ($clash = $this->overlap($data)) {
            return back()->withErrors(['effective_from' => $clash])->withInput();
        }

        $policy = AttendancePolicy::create($data);
        AuditLog::record('attendance_policy.created', $policy, [], $policy->snapshot(), $request->user()->id);

        return redirect()->route('admin.attendance-policies.index')->with('success', 'Attendance policy created.');
    }

    public function edit(AttendancePolicy $attendancePolicy): Response
    {
        return Inertia::render('Admin/AttendancePolicies/Form', [
            'policy' => $attendancePolicy->only([
                'id', 'client_id', 'name', 'description', 'is_active', 'weekly_offs', 'full_day_minutes', 'half_day_minutes',
                'min_session_minutes', 'grace_minutes', 'early_departure_minutes', 'max_late_per_month', 'lates_per_half_day',
                'deduct_late', 'deduct_early', 'require_portal_punch', 'require_machine_punch', 'source_priority',
                'source_tolerance_minutes', 'geofence_required', 'paid_break_minutes', 'max_break_minutes', 'deduct_excess_break',
                'track_idle_time', 'overtime_eligible', 'overtime_min_minutes', 'overtime_multiplier', 'overtime_requires_approval',
                'missing_punch_treatment',
            ]) + [
                'effective_from' => $attendancePolicy->effective_from->toDateString(),
                'effective_to' => $attendancePolicy->effective_to?->toDateString(),
            ],
            'clients' => Client::active()->orderBy('name')->get(['id', 'name']),
            'defaults' => [],
            'usedDays' => \App\Models\AttendanceRecord::query()->where('policy_id', $attendancePolicy->id)->count(),
        ]);
    }

    public function update(Request $request, AttendancePolicy $attendancePolicy): RedirectResponse
    {
        $data = $this->validated($request);

        if ($clash = $this->overlap($data, $attendancePolicy->id)) {
            return back()->withErrors(['effective_from' => $clash])->withInput();
        }

        $old = $attendancePolicy->snapshot();
        $attendancePolicy->update($data);
        AuditLog::record('attendance_policy.updated', $attendancePolicy, $old, $attendancePolicy->fresh()->snapshot(), $request->user()->id);

        return redirect()->route('admin.attendance-policies.index')
            ->with('success', 'Policy saved. Days already approved in payroll keep the rules they were calculated with.');
    }

    public function destroy(Request $request, AttendancePolicy $attendancePolicy): RedirectResponse
    {
        AuditLog::record('attendance_policy.deleted', $attendancePolicy, $attendancePolicy->snapshot(), [], $request->user()->id);
        $attendancePolicy->delete();

        return back()->with('success', 'Policy removed. Past attendance still shows the rules that were applied.');
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'client_id' => ['required', 'exists:clients,id'],
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:500'],
            'effective_from' => ['required', 'date'],
            'effective_to' => ['nullable', 'date', 'after_or_equal:effective_from'],
            'is_active' => ['boolean'],
            'weekly_offs' => ['nullable', 'array'],
            'weekly_offs.*' => ['integer', 'between:1,7'],
            'full_day_minutes' => ['required', 'integer', 'between:30,1440'],
            'half_day_minutes' => ['required', 'integer', 'between:15,1440', 'lte:full_day_minutes'],
            'min_session_minutes' => ['nullable', 'integer', 'between:0,1440'],
            'grace_minutes' => ['nullable', 'integer', 'between:0,240'],
            'early_departure_minutes' => ['nullable', 'integer', 'between:0,240'],
            'max_late_per_month' => ['nullable', 'integer', 'between:0,31'],
            'lates_per_half_day' => ['nullable', 'integer', 'between:0,31'],
            'deduct_late' => ['boolean'],
            'deduct_early' => ['boolean'],
            'require_portal_punch' => ['boolean'],
            'require_machine_punch' => ['boolean'],
            'source_priority' => ['nullable', 'array'],
            'source_priority.*' => ['in:' . implode(',', AttendancePolicy::SOURCES)],
            'source_tolerance_minutes' => ['nullable', 'integer', 'between:0,240'],
            'geofence_required' => ['boolean'],
            'paid_break_minutes' => ['nullable', 'integer', 'between:0,480'],
            'max_break_minutes' => ['nullable', 'integer', 'between:0,480'],
            'deduct_excess_break' => ['boolean'],
            'track_idle_time' => ['boolean'],
            'overtime_eligible' => ['boolean'],
            'overtime_min_minutes' => ['nullable', 'integer', 'between:0,480'],
            'overtime_multiplier' => ['nullable', 'numeric', 'between:1,5'],
            'overtime_requires_approval' => ['boolean'],
            'missing_punch_treatment' => ['required', 'in:review,half_day,absent'],
        ]);

        foreach (['min_session_minutes', 'grace_minutes', 'early_departure_minutes', 'max_late_per_month', 'lates_per_half_day', 'source_tolerance_minutes', 'paid_break_minutes', 'max_break_minutes', 'overtime_min_minutes'] as $field) {
            $data[$field] = $data[$field] ?? 0;
        }

        $data['overtime_multiplier'] = $data['overtime_multiplier'] ?? 1.5;
        $data['weekly_offs'] = array_values(array_unique($data['weekly_offs'] ?? []));
        $data['source_priority'] = array_values($data['source_priority'] ?? ['machine', 'portal']);
        $data['is_active'] = $data['is_active'] ?? true;

        return $data;
    }

    /** Two active policies for one client must not cover the same day. */
    private function overlap(array $data, ?int $ignoreId = null): ?string
    {
        if (!($data['is_active'] ?? true)) {
            return null;
        }

        $clash = AttendancePolicy::query()
            ->where('client_id', $data['client_id'])->where('is_active', true)
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->whereDate('effective_from', '<=', $data['effective_to'] ?? '9999-12-31')
            ->where(fn ($q) => $q->whereNull('effective_to')->orWhereDate('effective_to', '>=', $data['effective_from']))
            ->first();

        return $clash
            ? 'This overlaps "' . $clash->name . '" (' . $clash->effective_from->format('d M Y') . ' onwards). End that policy the day before, then try again.'
            : null;
    }
}
