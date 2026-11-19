<?php

namespace Tests\Feature\Workforce;

use App\Models\AttendancePolicy;
use App\Models\Client;
use App\Models\EmployeeShift;
use App\Models\OfficeLocation;
use App\Models\Shift;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;

/**
 * Shared fixtures for the attendance / leave / payroll tests.
 */
trait BuildsWorkforce
{
    public function seedRoles(): void
    {
        $this->seed(RolePermissionSeeder::class);
    }

    public function client(string $code = 'SWG'): Client
    {
        return Client::query()->create(['name' => 'Client ' . $code, 'code' => $code, 'is_active' => true]);
    }

    public function shift(Client $client, string $start = '09:00', string $end = '18:00', bool $overnight = false): Shift
    {
        return Shift::query()->create([
            'client_id' => $client->id,
            'name' => 'Shift ' . $start,
            'code' => 'S' . str_replace(':', '', $start),
            'start_time' => $start,
            'end_time' => $end,
            'is_overnight' => $overnight,
            'grace_period_minutes' => 0,
            'working_days' => [1, 2, 3, 4, 5, 6],
            'is_active' => true,
        ]);
    }

    public function policy(Client $client, array $overrides = []): AttendancePolicy
    {
        return AttendancePolicy::query()->create(array_merge([
            'client_id' => $client->id,
            'name' => 'Standard',
            'effective_from' => '2026-01-01',
            'is_active' => true,
            'weekly_offs' => [7],
            'full_day_minutes' => 480,
            'half_day_minutes' => 240,
            'grace_minutes' => 10,
            'require_portal_punch' => true,
            'source_priority' => ['machine', 'portal'],
            'geofence_required' => false,
            'paid_break_minutes' => 30,
            'max_break_minutes' => 60,
            'deduct_excess_break' => true,
            'missing_punch_treatment' => 'review',
        ], $overrides));
    }

    public function employee(Client $client, array $attributes = [], ?Shift $shift = null, string $role = 'employee'): User
    {
        static $n = 0;
        $n++;

        $user = User::factory()->create(array_merge([
            'employee_id' => 'E' . str_pad((string) $n, 4, '0', STR_PAD_LEFT),
            'client_id' => $client->id,
            'date_of_joining' => '2026-01-01',
            'employment_status' => 'active',
        ], $attributes));

        $user->assignRole($role);

        if ($shift) {
            EmployeeShift::query()->create([
                'user_id' => $user->id,
                'shift_id' => $shift->id,
                'client_id' => $client->id,
                'effective_from' => '2026-01-01',
                'is_active' => true,
            ]);
        }

        return $user;
    }

    public function office(Client $client, float $lat = 22.7196, float $lng = 75.8577, int $radius = 10): OfficeLocation
    {
        return OfficeLocation::query()->create([
            'client_id' => $client->id,
            'name' => 'Head office',
            'latitude' => $lat,
            'longitude' => $lng,
            'radius_meters' => $radius,
            'max_accuracy_meters' => 50,
            'is_active' => true,
        ]);
    }
}
