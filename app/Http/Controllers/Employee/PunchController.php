<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\AttendancePunch;
use App\Models\AttendanceRecord;
use App\Models\OfficeLocation;
use App\Services\Workforce\PolicyResolver;
use App\Services\Workforce\PunchRejectedException;
use App\Services\Workforce\PunchService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PunchController extends Controller
{
    public function __construct(
        private readonly PunchService $punches,
        private readonly PolicyResolver $resolver,
    ) {
    }

    public function index(Request $request): Response
    {
        $user = $request->user();
        $now = now();

        $state = $this->punches->currentState($user, $now);
        $date = $state['date'] ?? $now->copy()->startOfDay();

        $punches = AttendancePunch::query()
            ->where('user_id', $user->id)
            ->whereDate('attendance_date', $date->toDateString())
            ->valid()
            ->orderBy('punched_at')
            ->get()
            ->map(fn (AttendancePunch $p) => [
                'id' => $p->id,
                'type' => $p->type,
                'source' => $p->source,
                'time' => $p->punched_at->format('h:i A'),
                'geo_status' => $p->geo_status,
            ]);

        $record = AttendanceRecord::query()
            ->where('user_id', $user->id)
            ->whereDate('attendance_date', $date->toDateString())
            ->first();

        $shift = $this->resolver->shift($user, $date);
        $policy = $this->resolver->attendancePolicy($user, $date);

        return Inertia::render('Employee/Punch', [
            'serverTime' => $now->toIso8601String(),
            'date' => $date->format('l, d F Y'),
            'actions' => $this->actionsFor($state['last_type']),
            'lastType' => $state['last_type'],
            'punches' => $punches,
            'record' => $record ? [
                'status' => $record->status,
                'first_in' => $record->first_in?->format('h:i A'),
                'last_out' => $record->last_out?->format('h:i A'),
                'worked_minutes' => $record->worked_minutes,
                'break_minutes' => $record->break_minutes,
                'is_late' => $record->is_late,
                'late_minutes' => $record->late_minutes,
            ] : null,
            'shift' => $shift ? [
                'name' => $shift->name,
                'start' => $shift->start_time->format('h:i A'),
                'end' => $shift->end_time->format('h:i A'),
            ] : null,
            'geofenceRequired' => (bool) $policy?->geofence_required,
            'hasPolicy' => (bool) $policy,
            'locations' => OfficeLocation::query()->availableTo($user)->get(['id', 'name', 'radius_meters'])
                ->map(fn ($l) => ['id' => $l->id, 'name' => $l->name, 'radius' => $l->radius_meters]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'type' => ['required', 'in:in,out,break_start,break_end'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'accuracy' => ['nullable', 'numeric', 'min:0', 'max:100000'],
        ]);

        try {
            $punch = $this->punches->punch($request->user(), $data['type'], [
                'latitude' => isset($data['latitude']) ? (float) $data['latitude'] : null,
                'longitude' => isset($data['longitude']) ? (float) $data['longitude'] : null,
                'accuracy' => isset($data['accuracy']) ? (float) $data['accuracy'] : null,
                'ip' => $request->ip(),
            ]);
        } catch (PunchRejectedException $e) {
            return back()->withErrors(['punch' => $e->getMessage()]);
        }

        $labels = ['in' => 'Punched in', 'out' => 'Punched out', 'break_start' => 'Break started', 'break_end' => 'Break ended'];

        return back()->with('success', $labels[$punch->type] . ' at ' . $punch->punched_at->format('h:i A') . '.');
    }

    /** What the employee can do next, given their last punch. */
    private function actionsFor(?string $last): array
    {
        return match ($last) {
            'in', 'break_end' => ['break_start', 'out'],
            'break_start' => ['break_end'],
            default => ['in'],
        };
    }
}
