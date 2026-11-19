<?php

namespace App\Services\Workforce;

use App\Models\AttendancePunch;
use App\Models\AttendanceRecord;
use App\Models\AuditLog;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonInterface;

/**
 * Records punches. Timestamps always come from the server, the source of a
 * portal punch is fixed by the endpoint, and geofence checks run here rather
 * than in the browser.
 */
class PunchService
{
    /** A shift can run overnight, so an open "in" stays open this long. */
    public const OPEN_SESSION_HOURS = 20;

    private const TRANSITIONS = [
        AttendancePunch::TYPE_IN => [null, AttendancePunch::TYPE_OUT],
        AttendancePunch::TYPE_OUT => [AttendancePunch::TYPE_IN, AttendancePunch::TYPE_BREAK_END],
        AttendancePunch::TYPE_BREAK_START => [AttendancePunch::TYPE_IN, AttendancePunch::TYPE_BREAK_END],
        AttendancePunch::TYPE_BREAK_END => [AttendancePunch::TYPE_BREAK_START],
    ];

    public function __construct(
        private readonly GeofenceService $geofence,
        private readonly PolicyResolver $resolver,
        private readonly AttendanceCalculator $calculator,
    ) {
    }

    /**
     * Employee punch from the web portal.
     *
     * @param  array{latitude?:?float, longitude?:?float, accuracy?:?float, ip?:?string, notes?:?string}  $context
     */
    public function punch(User $user, string $type, array $context = [], ?CarbonInterface $at = null): AttendancePunch
    {
        $at = $at ? Carbon::instance($at) : now();

        if (!in_array($type, array_keys(self::TRANSITIONS), true)) {
            throw new PunchRejectedException('Unknown punch type.');
        }

        if (!$user->is_active || ($user->employment_status ?? 'active') !== 'active') {
            throw new PunchRejectedException('Your account is not active for attendance.');
        }

        $state = $this->currentState($user, $at);

        if (!in_array($state['last_type'], self::TRANSITIONS[$type], true)) {
            throw new PunchRejectedException($this->transitionMessage($type, $state['last_type']));
        }

        $policy = $this->resolver->attendancePolicy($user, $at);
        $geo = $this->geofence->evaluate(
            $user,
            $context['latitude'] ?? null,
            $context['longitude'] ?? null,
            $context['accuracy'] ?? null,
            $this->requiresGeofence($type, $policy?->geofence_required ?? false)
        );

        if (!$geo['allowed']) {
            throw new PunchRejectedException($geo['message']);
        }

        $date = $state['date'] ?? $at->copy()->startOfDay();

        $punch = AttendancePunch::create([
            'user_id' => $user->id,
            'client_id' => $user->client_id,
            'attendance_date' => $date->toDateString(),
            'punched_at' => $at,
            'type' => $type,
            'source' => AttendancePunch::SOURCE_PORTAL,
            'latitude' => $context['latitude'] ?? null,
            'longitude' => $context['longitude'] ?? null,
            'accuracy_meters' => $context['accuracy'] ?? null,
            'distance_meters' => $geo['distance'],
            'office_location_id' => $geo['location']?->id,
            'geo_status' => $geo['status'],
            'ip_address' => $context['ip'] ?? null,
            'notes' => $context['notes'] ?? null,
        ]);

        $this->calculator->calculate($user, $date, $at);

        return $punch;
    }

    /** Punch entered by HR on someone's behalf (or created from an approved correction). */
    public function manual(
        User $employee,
        string $type,
        CarbonInterface $at,
        string $reason,
        User $by,
        ?int $correctionId = null,
        bool $recalculate = true,
    ): AttendancePunch {
        $at = Carbon::instance($at);

        $punch = AttendancePunch::create([
            'user_id' => $employee->id,
            'client_id' => $employee->client_id,
            'attendance_date' => $this->attendanceDateFor($employee, $at, $type)->toDateString(),
            'punched_at' => $at,
            'type' => $type,
            'source' => AttendancePunch::SOURCE_MANUAL,
            'geo_status' => GeofenceService::NOT_REQUIRED,
            'notes' => $reason,
            'created_by' => $by->id,
            'correction_id' => $correctionId,
        ]);

        AuditLog::record('attendance.manual_punch', $punch, [], $punch->only(['user_id', 'type', 'punched_at', 'notes']), $by->id);

        if ($recalculate) {
            $this->calculator->calculate($employee, $punch->attendance_date, now());
        }

        return $punch;
    }

    /** Voids a punch instead of deleting it. */
    public function void(AttendancePunch $punch, User $by, string $reason): AttendancePunch
    {
        $record = AttendanceRecord::query()
            ->where('user_id', $punch->user_id)
            ->whereDate('attendance_date', $punch->attendance_date)
            ->first();

        if ($record?->isLocked()) {
            throw new PunchRejectedException('This day is locked by an approved payroll run.');
        }

        $punch->update(['voided_at' => now(), 'voided_by' => $by->id, 'void_reason' => $reason]);

        AuditLog::record('attendance.punch_voided', $punch, [], ['reason' => $reason], $by->id);

        $this->calculator->calculate($punch->user, $punch->attendance_date, now());

        return $punch;
    }

    /**
     * The shift-day a punch belongs to. An "out" (or break) that follows an
     * open "in" belongs to that "in"'s day, which is how overnight shifts work.
     */
    public function attendanceDateFor(User $user, CarbonInterface $at, string $type): Carbon
    {
        if ($type !== AttendancePunch::TYPE_IN) {
            $state = $this->currentState($user, $at);

            if ($state['date']) {
                return $state['date'];
            }
        }

        return Carbon::instance($at)->startOfDay();
    }

    /**
     * @return array{last_type:?string, date:?Carbon}
     */
    public function currentState(User $user, CarbonInterface $at): array
    {
        $last = AttendancePunch::query()
            ->where('user_id', $user->id)
            ->valid()
            ->whereIn('type', array_keys(self::TRANSITIONS))
            ->where('punched_at', '<=', $at)
            ->orderByDesc('punched_at')
            ->orderByDesc('id')
            ->first();

        if (!$last) {
            return ['last_type' => null, 'date' => null];
        }

        $open = $last->type !== AttendancePunch::TYPE_OUT
            && $last->punched_at->gte(Carbon::instance($at)->subHours(self::OPEN_SESSION_HOURS));

        if ($open) {
            return ['last_type' => $last->type, 'date' => Carbon::parse($last->attendance_date)->startOfDay()];
        }

        // Punched out earlier today: a new "in" is allowed, an "out" is not.
        return [
            'last_type' => $last->type === AttendancePunch::TYPE_OUT ? AttendancePunch::TYPE_OUT : null,
            'date' => null,
        ];
    }

    private function requiresGeofence(string $type, bool $policyRequires): bool
    {
        return $policyRequires && in_array($type, [AttendancePunch::TYPE_IN, AttendancePunch::TYPE_OUT], true);
    }

    private function transitionMessage(string $type, ?string $last): string
    {
        return match ($type) {
            AttendancePunch::TYPE_IN => 'You are already punched in. Punch out before punching in again.',
            AttendancePunch::TYPE_OUT => $last === AttendancePunch::TYPE_BREAK_START
                ? 'End your break before punching out.'
                : 'You are not punched in.',
            AttendancePunch::TYPE_BREAK_START => $last === AttendancePunch::TYPE_BREAK_START
                ? 'You are already on a break.'
                : 'Punch in before starting a break.',
            default => 'You are not on a break.',
        };
    }
}
