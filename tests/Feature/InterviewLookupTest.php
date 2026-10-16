<?php

use App\Models\Branch;
use App\Models\Candidate;
use App\Models\CandidateRoundProgress;
use App\Models\InterviewRound;
use App\Models\InterviewSchedule;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

function lookupSetup(): array
{
    $branch = Branch::query()->create([
        'name' => 'Indore',
        'code' => 'IND',
        'address' => 'Test address',
        'city' => 'Indore',
        'state' => 'MP',
        'postal_code' => '452001',
        'phone' => '9999999999',
        'email' => 'ind@example.test',
    ]);

    $round = InterviewRound::query()->create([
        'name' => 'Ops Round',
        'slug' => 'ops-round',
        'sequence_number' => 1,
        'is_active' => true,
    ]);

    $interviewer = User::query()->create([
        'name' => 'Ravi Sharma',
        'first_name' => 'Ravi',
        'last_name' => 'Sharma',
        'email' => 'ravi@example.test',
        'password' => Hash::make('password'),
        'designation' => 'Operations Manager',
        'branch_id' => $branch->id,
        'is_active' => true,
        'can_interview' => true,
    ]);

    return compact('branch', 'round', 'interviewer');
}

function lookupCandidate(array $fx, array $overrides = []): Candidate
{
    return Candidate::query()->create(array_merge([
        'first_name' => 'Asha',
        'last_name' => 'Verma',
        'email' => 'asha@example.test',
        'phone' => '8888888888',
        'position_applied' => 'Support Executive',
        'branch_id' => $fx['branch']->id,
        'current_status' => 'in_progress',
    ], $overrides));
}

function lookupProgress(array $fx, Candidate $candidate, string $status = 'pending', ?User $interviewer = null): CandidateRoundProgress
{
    return CandidateRoundProgress::query()->create([
        'candidate_id' => $candidate->id,
        'round_id' => $fx['round']->id,
        'interviewer_id' => ($interviewer ?? $fx['interviewer'])->id,
        'status' => $status,
    ]);
}

function lookupSchedule(array $fx, Candidate $candidate, $when, string $status = 'scheduled'): InterviewSchedule
{
    return InterviewSchedule::query()->create([
        'candidate_id' => $candidate->id,
        'round_id' => $fx['round']->id,
        'interviewer_id' => $fx['interviewer']->id,
        'scheduled_at' => $when,
        'status' => $status,
    ]);
}

test('the lookup page is public', function () {
    $this->get(route('interview-lookup.show'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Candidate/InterviewLookup')
            ->where('result', null)
            ->where('searched', false));
});

test('a candidate with an interview today sees the interviewer and time', function () {
    $fx = lookupSetup();
    $candidate = lookupCandidate($fx);
    lookupProgress($fx, $candidate);
    lookupSchedule($fx, $candidate, now()->setTime(23, 30));

    $this->post(route('interview-lookup.search'), ['email' => 'asha@example.test'])
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('searched', true)
            ->where('result.state', 'today')
            ->where('result.candidate', 'Asha V.')
            ->where('result.round', 'Ops Round')
            ->where('result.branch', 'Indore')
            ->where('result.interviewer.name', 'Ravi Sharma')
            ->where('result.interviewer.designation', 'Operations Manager')
            ->where('result.scheduled_time', '11:30 PM'));
});

test('email lookup ignores case and surrounding spaces', function () {
    $fx = lookupSetup();
    $candidate = lookupCandidate($fx, ['email' => 'Asha@Example.test']);
    lookupProgress($fx, $candidate);

    $this->post(route('interview-lookup.search'), ['email' => '  ASHA@example.TEST '])
        ->assertInertia(fn ($page) => $page->where('result.state', 'unscheduled'));
});

test('an interview that is running is reported as in progress', function () {
    $fx = lookupSetup();
    $candidate = lookupCandidate($fx);
    lookupProgress($fx, $candidate, 'in_progress');

    $this->post(route('interview-lookup.search'), ['email' => 'asha@example.test'])
        ->assertInertia(fn ($page) => $page
            ->where('result.state', 'in_progress')
            ->where('result.interviewer.name', 'Ravi Sharma'));
});

test('a future schedule is flagged as upcoming rather than today', function () {
    $fx = lookupSetup();
    $candidate = lookupCandidate($fx);
    lookupProgress($fx, $candidate);
    lookupSchedule($fx, $candidate, now()->addDays(3));

    $this->post(route('interview-lookup.search'), ['email' => 'asha@example.test'])
        ->assertInertia(fn ($page) => $page->where('result.state', 'upcoming'));
});

test('the interviewer shown follows a reassignment on the round progress', function () {
    $fx = lookupSetup();
    $other = User::query()->create([
        'name' => 'Meera Joshi',
        'first_name' => 'Meera',
        'last_name' => 'Joshi',
        'email' => 'meera@example.test',
        'password' => Hash::make('password'),
        'is_active' => true,
        'can_interview' => true,
    ]);
    $candidate = lookupCandidate($fx);
    lookupProgress($fx, $candidate, 'pending', $other);
    lookupSchedule($fx, $candidate, now()->setTime(23, 30));

    $this->post(route('interview-lookup.search'), ['email' => 'asha@example.test'])
        ->assertInertia(fn ($page) => $page->where('result.interviewer.name', 'Meera Joshi'));
});

test('unknown emails, rejected candidates and finished rounds all get the same empty result', function () {
    $fx = lookupSetup();

    $rejected = lookupCandidate($fx, ['email' => 'rej@example.test', 'current_status' => 'rejected']);
    lookupProgress($fx, $rejected);

    $done = lookupCandidate($fx, ['email' => 'done@example.test', 'phone' => '7777777777']);
    lookupProgress($fx, $done, 'completed');

    foreach (['nobody@example.test', 'rej@example.test', 'done@example.test'] as $email) {
        $this->post(route('interview-lookup.search'), ['email' => $email])
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('searched', true)
                ->where('result', null));
    }
});

test('the response exposes only the minimal fields', function () {
    $fx = lookupSetup();
    $candidate = lookupCandidate($fx);
    lookupProgress($fx, $candidate);
    $schedule = lookupSchedule($fx, $candidate, now()->setTime(23, 30));
    $schedule->update(['gmeet_link' => 'https://meet.google.com/abc-defg-hij']);

    $response = $this->post(route('interview-lookup.search'), ['email' => 'asha@example.test']);
    $body = json_encode($response->viewData('page')['props'] ?? $response->json());

    expect($body)
        ->not->toContain('8888888888')
        ->not->toContain('meet.google.com')
        ->not->toContain('Verma')
        ->not->toContain('asha@example.test');
});

test('invalid emails are rejected by validation', function () {
    $this->post(route('interview-lookup.search'), ['email' => 'not-an-email'])
        ->assertSessionHasErrors('email');
});

test('repeated lookups for the same email are throttled', function () {
    foreach (range(1, 5) as $i) {
        $this->post(route('interview-lookup.search'), ['email' => 'spam@example.test'])->assertOk();
    }

    $this->post(route('interview-lookup.search'), ['email' => 'spam@example.test'])
        ->assertStatus(429);
});
