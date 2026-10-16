<?php

use App\Models\Branch;
use App\Models\Candidate;
use App\Models\CandidateRoundProgress;
use App\Models\InterviewRound;
use App\Models\InterviewSchedule;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Facades\Hash;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

function dashboardBranch(string $name, string $code): Branch
{
    return Branch::query()->create([
        'name' => $name,
        'code' => $code,
        'address' => 'Test address',
        'city' => $name,
        'state' => 'Test',
        'postal_code' => '000000',
        'phone' => '9999999999',
        'email' => strtolower($code) . '@example.test',
    ]);
}

function dashboardUser(string $role, ?Branch $branch = null, ?string $email = null): User
{
    $user = User::query()->create([
        'name' => ucfirst($role) . ' User',
        'first_name' => ucfirst($role),
        'last_name' => 'User',
        'email' => $email ?? $role . '@example.test',
        'password' => Hash::make('password'),
        'branch_id' => $branch?->id,
        'is_active' => true,
        'can_interview' => true,
    ]);
    $user->forceFill(['email_verified_at' => now()])->save();
    $user->assignRole($role);

    return $user;
}

function dashboardCandidate(Branch $branch, string $first): Candidate
{
    return Candidate::query()->create([
        'first_name' => $first,
        'last_name' => 'Candidate',
        'email' => strtolower($first) . '@example.test',
        'phone' => (string) random_int(7000000000, 9999999999),
        'position_applied' => 'Support Executive',
        'branch_id' => $branch->id,
    ]);
}

function dashboardFixture(): array
{
    $indore = dashboardBranch('Indore', 'IND');
    $jaipur = dashboardBranch('Jaipur', 'JAI');

    $round = InterviewRound::query()->create([
        'name' => 'Ops Round',
        'slug' => 'ops-round',
        'sequence_number' => 1,
        'is_active' => true,
    ]);

    $interviewer = dashboardUser('interviewer', $indore);

    $make = function (Branch $branch, string $name, string $status) use ($round, $interviewer) {
        $candidate = dashboardCandidate($branch, $name);
        CandidateRoundProgress::query()->create([
            'candidate_id' => $candidate->id,
            'round_id' => $round->id,
            'interviewer_id' => $interviewer->id,
            'status' => $status,
            'start_date' => $status === 'in_progress' ? now()->subMinutes(20) : null,
        ]);

        return $candidate;
    };

    // Indore: 1 pending, 1 ongoing, 1 completed. Jaipur: 1 pending.
    $make($indore, 'Pia', 'pending');
    $make($indore, 'Omar', 'in_progress');
    $make($indore, 'Cara', 'completed');
    $make($jaipur, 'Jay', 'pending');

    // Scheduled: 1 upcoming in Indore, 1 overdue in Jaipur, 1 cancelled in Jaipur.
    $upcoming = dashboardCandidate($indore, 'Sam');
    $overdue = dashboardCandidate($jaipur, 'Olive');
    $cancelled = dashboardCandidate($jaipur, 'Cal');

    foreach ([
        [$upcoming, now()->addDay(), 'scheduled'],
        [$overdue, now()->subHours(3), 'scheduled'],
        [$cancelled, now()->addDays(2), 'cancelled'],
    ] as [$candidate, $when, $status]) {
        InterviewSchedule::query()->create([
            'candidate_id' => $candidate->id,
            'round_id' => $round->id,
            'interviewer_id' => $interviewer->id,
            'scheduled_at' => $when,
            'status' => $status,
        ]);
    }

    return compact('indore', 'jaipur', 'round', 'interviewer');
}

test('guests are redirected to login', function () {
    $this->get(route('recruitment.interviews.dashboard'))->assertRedirect('/login');
});

test('employees without interview permissions cannot open the dashboard', function () {
    $branch = dashboardBranch('Indore', 'IND');
    $employee = dashboardUser('employee', $branch);

    $this->actingAs($employee)
        ->get(route('recruitment.interviews.dashboard'))
        ->assertForbidden();
});

test('admin sees scheduled, ongoing and pending interviews across all branches', function () {
    dashboardFixture();
    $admin = dashboardUser('admin');

    $this->actingAs($admin)
        ->get(route('recruitment.interviews.dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Recruitment/Interviews/Dashboard')
            ->where('canSeeAllBranches', true)
            ->where('stats.scheduled', 2)
            ->where('stats.overdue', 1)
            ->where('stats.ongoing', 1)
            ->where('stats.pending', 2)
            ->has('scheduled', 2)
            ->has('ongoing', 1)
            ->has('pending', 2)
            ->has('branches', 2)
        );
});

test('branch report groups interview activity by candidate branch', function () {
    dashboardFixture();
    $admin = dashboardUser('admin');

    $this->actingAs($admin)
        ->get(route('recruitment.interviews.dashboard'))
        ->assertInertia(fn ($page) => $page
            ->has('branchReport.rows', 2)
            ->where('branchReport.rows.0.branch', 'Indore')
            ->where('branchReport.rows.0.scheduled', 1)
            ->where('branchReport.rows.0.ongoing', 1)
            ->where('branchReport.rows.0.pending', 1)
            ->where('branchReport.rows.0.completed', 1)
            ->where('branchReport.rows.0.pass_rate', 100)
            ->where('branchReport.rows.1.branch', 'Jaipur')
            ->where('branchReport.rows.1.scheduled', 1)
            ->where('branchReport.rows.1.overdue', 1)
            ->where('branchReport.rows.1.cancelled', 1)
            ->where('branchReport.rows.1.pending', 1)
            ->where('branchReport.totals.scheduled', 2)
            ->where('branchReport.totals.pending', 2)
        );
});

test('branch filter narrows lists and the report for admins', function () {
    $fx = dashboardFixture();
    $admin = dashboardUser('admin');

    $this->actingAs($admin)
        ->get(route('recruitment.interviews.dashboard', ['branch_id' => $fx['jaipur']->id]))
        ->assertInertia(fn ($page) => $page
            ->where('stats.scheduled', 1)
            ->where('stats.ongoing', 0)
            ->where('stats.pending', 1)
            ->has('branchReport.rows', 1)
            ->where('branchReport.rows.0.branch', 'Jaipur')
        );
});

test('hr only sees their own branch and cannot widen it with a filter', function () {
    $fx = dashboardFixture();
    $hr = dashboardUser('hr', $fx['indore']);

    $this->actingAs($hr)
        ->get(route('recruitment.interviews.dashboard', ['branch_id' => $fx['jaipur']->id]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('canSeeAllBranches', false)
            ->has('branches', 0)
            ->where('stats.scheduled', 1)
            ->where('stats.ongoing', 1)
            ->where('stats.pending', 1)
            ->has('branchReport.rows', 1)
            ->where('branchReport.rows.0.branch', 'Indore')
        );
});

test('soft-deleted candidates are excluded from the dashboard', function () {
    $fx = dashboardFixture();
    Candidate::query()->where('first_name', 'Jay')->first()->delete();
    $admin = dashboardUser('admin');

    $this->actingAs($admin)
        ->get(route('recruitment.interviews.dashboard'))
        ->assertInertia(fn ($page) => $page
            ->where('stats.pending', 1)
            ->where('branchReport.rows.1.pending', 0)
        );
});
