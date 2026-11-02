<?php

use App\Models\Branch;
use App\Models\Candidate;
use App\Models\CandidateRoundProgress;
use App\Models\InterviewRound;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Facades\Hash;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

function assignmentBranch(): Branch
{
    return Branch::query()->create([
        'name' => 'Assignment Branch',
        'code' => 'ASG',
        'address' => 'Test address',
        'city' => 'Test city',
        'state' => 'Test state',
        'postal_code' => '000000',
        'phone' => '9999999999',
        'email' => 'assignment-branch@example.test',
    ]);
}

function assignmentUser(
    string $role,
    Branch $branch,
    string $email,
    ?bool $canInterview = null
): User {
    $user = User::query()->create([
        'name' => ucfirst($role).' User',
        'first_name' => ucfirst($role),
        'last_name' => 'User',
        'email' => $email,
        'password' => Hash::make('password'),
        'branch_id' => $branch->id,
        'is_active' => true,
        'employment_status' => 'active',
        'can_interview' => $canInterview ?? $role === 'interviewer',
    ]);
    $user->forceFill(['email_verified_at' => now()])->save();
    $user->assignRole($role);

    return $user;
}

function assignmentFixture(): array
{
    $branch = assignmentBranch();
    $round = InterviewRound::query()->create([
        'name' => 'Assignment Round',
        'slug' => 'assignment-round',
        'sequence_number' => 1,
        'is_active' => true,
    ]);
    $interviewer = assignmentUser(
        'interviewer',
        $branch,
        'interviewer-assignment@example.test'
    );
    $interviewer->allowedRounds()->attach($round);

    $candidate = Candidate::query()->create([
        'first_name' => 'Taylor',
        'last_name' => 'Candidate',
        'email' => 'taylor-candidate@example.test',
        'phone' => '9876543210',
        'position_applied' => 'Support Executive',
        'branch_id' => $branch->id,
        'registration_date' => now(),
        'current_round_id' => $round->id,
        'current_status' => 'in_progress',
    ]);
    $progress = CandidateRoundProgress::query()->create([
        'candidate_id' => $candidate->id,
        'round_id' => $round->id,
        'interviewer_id' => $interviewer->id,
        'assignment_status' => 'pending',
        'status' => 'pending',
    ]);

    return compact('branch', 'round', 'interviewer', 'candidate', 'progress');
}

test('new assignment requests appear separately and cannot be opened before acceptance', function () {
    $fx = assignmentFixture();

    $this->actingAs($fx['interviewer'])
        ->get(route('interviewer.dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('stats.assignment_requests', 1)
            ->where('stats.pending', 0)
            ->has('assignment_requests', 1)
            ->has('pending_interviews', 0)
        );

    $this->actingAs($fx['interviewer'])
        ->get(route('interviewer.interviews.show', [
            'candidate' => $fx['candidate']->id,
            'round' => $fx['round']->id,
        ]))
        ->assertForbidden();

    expect($fx['candidate']->fresh()->current_interviewer_id)->toBeNull();
});

test('accepting an assignment makes the interviewer the candidate current interviewer', function () {
    $fx = assignmentFixture();

    $this->actingAs($fx['interviewer'])
        ->post(route('interviewer.interviews.assignment.accept', [
            'candidate' => $fx['candidate']->id,
            'round' => $fx['round']->id,
        ]))
        ->assertRedirect();

    expect($fx['progress']->fresh()->assignment_status)->toBe('accepted')
        ->and($fx['progress']->fresh()->assignment_responded_at)->not->toBeNull()
        ->and($fx['candidate']->fresh()->current_interviewer_id)->toBe($fx['interviewer']->id);
});

test('candidate lookup does not expose an interviewer before the assignment is accepted', function () {
    $fx = assignmentFixture();

    $this->post(route('interview-lookup.search'), [
        'email' => $fx['candidate']->email,
    ])->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('result', null)
        );
});

test('hr assigned as interviewer sees and can accept the assignment on the hr dashboard', function () {
    $fx = assignmentFixture();
    $hr = assignmentUser(
        'hr_recruiter',
        $fx['branch'],
        'hr-interviewer-assignment@example.test',
        true
    );
    $hr->allowedRounds()->attach($fx['round']);
    $fx['progress']->update(['interviewer_id' => $hr->id]);

    $this->actingAs($hr)
        ->get(route('hr.dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('stats.pending_assignments', 1)
            ->has('assignmentRequests', 1)
            ->where('assignmentRequests.0.candidate_id', $fx['candidate']->id)
        );

    $this->post(route('interviewer.interviews.assignment.accept', [
        'candidate' => $fx['candidate']->id,
        'round' => $fx['round']->id,
    ]))->assertRedirect();

    expect($fx['progress']->fresh()->assignment_status)->toBe('accepted')
        ->and($fx['candidate']->fresh()->current_interviewer_id)->toBe($hr->id);
});

test('admin assigned as interviewer sees and can reject the assignment on the admin dashboard', function () {
    $fx = assignmentFixture();
    $admin = assignmentUser(
        'admin',
        $fx['branch'],
        'admin-interviewer-assignment@example.test',
        true
    );
    $admin->allowedRounds()->attach($fx['round']);
    $fx['progress']->update(['interviewer_id' => $admin->id]);

    $this->actingAs($admin)
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('stats.pending_assignments', 1)
            ->has('assignmentRequests', 1)
            ->where('assignmentRequests.0.candidate_id', $fx['candidate']->id)
        );

    $this->post(route('interviewer.interviews.assignment.reject', [
        'candidate' => $fx['candidate']->id,
        'round' => $fx['round']->id,
    ]))->assertRedirect();

    expect($fx['progress']->fresh()->assignment_status)->toBe('rejected');
});

test('a rejected assignment can be reassigned by hr to another interviewer', function () {
    $fx = assignmentFixture();
    $replacement = assignmentUser(
        'interviewer',
        $fx['branch'],
        'replacement-assignment@example.test'
    );
    $replacement->allowedRounds()->attach($fx['round']);
    $hr = assignmentUser(
        'hr_recruiter',
        $fx['branch'],
        'hr-assignment@example.test'
    );

    $this->actingAs($fx['interviewer'])
        ->post(route('interviewer.interviews.assignment.reject', [
            'candidate' => $fx['candidate']->id,
            'round' => $fx['round']->id,
        ]))
        ->assertRedirect();

    expect($fx['progress']->fresh()->assignment_status)->toBe('rejected')
        ->and($fx['candidate']->fresh()->current_interviewer_id)->toBeNull();

    $this->actingAs($hr)
        ->post(route('interviewer.interviews.reassign', [
            'candidate' => $fx['candidate']->id,
            'round' => $fx['round']->id,
        ]), [
            'new_interviewer_id' => $replacement->id,
        ])
        ->assertRedirect();

    expect($fx['progress']->fresh()->interviewer_id)->toBe($replacement->id)
        ->and($fx['progress']->fresh()->assignment_status)->toBe('pending')
        ->and($fx['progress']->fresh()->assignment_responded_at)->toBeNull()
        ->and($fx['candidate']->fresh()->current_interviewer_id)->toBeNull();
});
