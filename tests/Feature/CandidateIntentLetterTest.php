<?php

use App\Models\CandidateIntentLetter;
use App\Models\Branch;
use App\Models\Candidate;
use App\Models\User;
use App\Mail\CandidateIntentLetterMail;
use App\Services\Recruitment\CandidateIntentLetterDeliveryService;
use App\Services\Recruitment\CandidateIntentLetterPdfService;
use App\Policies\CandidateOfferPolicy;
use App\Services\Recruitment\CandidateIntentLetterPortalTokenService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Storage;

test('an intent letter is acknowledged only when candidate consent and timestamp are recorded', function () {
    $letter = new CandidateIntentLetter([
        'status' => CandidateIntentLetter::STATUS_ACKNOWLEDGED,
        'acknowledged_at' => now(),
        'candidate_consent' => true,
    ]);

    expect($letter->isAcknowledged())->toBeTrue();

    $letter->candidate_consent = false;
    expect($letter->isAcknowledged())->toBeFalse();

    $letter->candidate_consent = true;
    $letter->acknowledged_at = null;
    expect($letter->isAcknowledged())->toBeFalse();
});

test('intent letter acknowledgement records the submitted name and preliminary-offer language', function () {
    config()->set('company.legal_name', 'Airvincible Pvt. Ltd.');

    $text = CandidateIntentLetter::acknowledgementText('Asha Candidate');

    expect($text)
        ->toContain('Asha Candidate')
        ->toContain('Airvincible Pvt. Ltd.')
        ->toContain('not the final employment offer');
});

test('intent letter stores the department and reporting contact', function () {
    $candidate = intentLetterTestCandidate();

    $letter = CandidateIntentLetter::query()->create([
        'candidate_id' => $candidate->id,
        'position_title' => 'Customer Support Executive',
        'department' => 'Customer Support',
        'reporting_to' => 'Support Manager',
        'work_location' => 'Indore',
        'monthly_ctc' => 25000,
        'monthly_in_hand' => 22000,
        'joining_date' => today()->addDays(10),
        'letter_date' => today(),
    ]);

    expect($letter->fresh()->department)->toBe('Customer Support')
        ->and($letter->fresh()->reporting_to)->toBe('Support Manager');
});

test('intent letter portal expiry accepts numeric environment strings', function () {
    $candidate = intentLetterTestCandidate();
    $letter = CandidateIntentLetter::query()->create([
        'candidate_id' => $candidate->id,
        'position_title' => 'Customer Support Executive',
        'work_location' => 'Indore',
        'monthly_ctc' => 25000,
        'monthly_in_hand' => 22000,
        'joining_date' => today()->addDays(10),
        'letter_date' => today(),
    ]);

    config()->set('recruitment.intent_letter_portal_expiry_days', '5');

    app(CandidateIntentLetterPortalTokenService::class)->issue($letter);

    expect($letter->fresh()->portal_token_expires_at->toDateString())
        ->toBe(now()->addDays(5)->toDateString());
});

test('intent letter delivery sends the candidate portal link and records sent status', function () {
    Storage::fake('local');

    $candidate = intentLetterTestCandidate();
    $sender = User::query()->create([
        'first_name' => 'HR',
        'last_name' => 'Sender',
        'email' => 'hr-sender@example.test',
        'password' => Hash::make('password'),
    ]);
    $letter = CandidateIntentLetter::query()->create([
        'candidate_id' => $candidate->id,
        'status' => CandidateIntentLetter::STATUS_DRAFT,
        'position_title' => 'Customer Support Executive',
        'department' => 'Customer Support',
        'reporting_to' => 'Support Manager',
        'work_location' => 'Indore',
        'monthly_ctc' => 25000,
        'monthly_in_hand' => 22000,
        'joining_date' => today()->addDays(10),
        'letter_date' => today(),
        'pdf_path' => 'recruitment/intent-letters/test/LOI-test.pdf',
    ]);
    Storage::disk('local')->put($letter->pdf_path, 'draft-pdf');
    Mail::fake();

    app(CandidateIntentLetterDeliveryService::class)->send(
        $letter,
        $sender->id,
        app(CandidateIntentLetterPdfService::class),
        app(CandidateIntentLetterPortalTokenService::class)
    );

    Mail::assertSent(CandidateIntentLetterMail::class);

    expect($letter->fresh()->status)
        ->toBe(CandidateIntentLetter::STATUS_SENT)
        ->and($letter->fresh()->sent_to_email)
        ->toBe($candidate->email)
        ->and($letter->fresh()->sent_by)
        ->toBe($sender->id)
        ->and($letter->fresh()->portal_token_hash)
        ->not->toBeNull();
});

test('candidate can acknowledge a sent intent letter and receive an acknowledged PDF', function () {
    Storage::fake('local');

    $candidate = intentLetterTestCandidate();
    $letter = CandidateIntentLetter::query()->create([
        'candidate_id' => $candidate->id,
        'status' => CandidateIntentLetter::STATUS_SENT,
        'position_title' => 'Customer Support Executive',
        'work_location' => 'Indore',
        'monthly_ctc' => 25000,
        'monthly_in_hand' => 22000,
        'joining_date' => today()->addDays(10),
        'letter_date' => today(),
        'pdf_path' => 'recruitment/intent-letters/test/LOI-test.pdf',
    ]);

    Storage::disk('local')->put($letter->pdf_path, 'draft-pdf');

    $token = app(CandidateIntentLetterPortalTokenService::class)
        ->issue($letter);

    $this->get(route('candidate-intent-letters.portal.show', $token))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Public/IntentLetterPortal/Show')
            ->where('portal.can_acknowledge', true));

    $this->post(
        route('candidate-intent-letters.portal.acknowledge', $token),
        [
            'candidate_name' => 'Asha Candidate',
            'consent' => true,
        ]
    )->assertRedirect();

    $letter->refresh();

    expect($letter->status)->toBe(CandidateIntentLetter::STATUS_ACKNOWLEDGED)
        ->and($letter->candidate_consent)->toBeTrue()
        ->and($letter->acknowledgement_name)->toBe('Asha Candidate')
        ->and($letter->acknowledgement_text)->toContain('Asha Candidate')
        ->and($letter->acknowledged_at)->not->toBeNull()
        ->and($letter->acknowledged_pdf_path)->not->toBeNull();

    Storage::disk('local')->assertExists($letter->acknowledged_pdf_path);
});

test('candidate can decline a sent intent letter with a required remark that HR can review', function () {
    $candidate = intentLetterTestCandidate();
    $letter = CandidateIntentLetter::query()->create([
        'candidate_id' => $candidate->id,
        'status' => CandidateIntentLetter::STATUS_SENT,
        'position_title' => 'Customer Support Executive',
        'work_location' => 'Indore',
        'monthly_ctc' => 25000,
        'monthly_in_hand' => 22000,
        'joining_date' => today()->addDays(10),
        'letter_date' => today(),
    ]);

    $token = app(CandidateIntentLetterPortalTokenService::class)
        ->issue($letter);

    $this->from(route('candidate-intent-letters.portal.show', $token))
        ->post(route('candidate-intent-letters.portal.decline', $token), [
            'candidate_name' => 'Asha Candidate',
            'reason' => 'I have accepted another opportunity.',
            'confirmation' => true,
        ])
        ->assertRedirect(route('candidate-intent-letters.portal.show', $token));

    $letter->refresh();

    expect($letter->status)->toBe(CandidateIntentLetter::STATUS_DECLINED)
        ->and($letter->isDeclined())->toBeTrue()
        ->and($letter->rejection_name)->toBe('Asha Candidate')
        ->and($letter->rejection_remark)->toBe('I have accepted another opportunity.')
        ->and($letter->rejected_at)->not->toBeNull()
        ->and($letter->rejection_ip)->not->toBeNull()
        ->and($letter->candidate_consent)->toBeFalse();

    $this->get(route('candidate-intent-letters.portal.show', $token))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('portal.can_acknowledge', false)
            ->where('portal.declined', true)
            ->where('letter.rejection_remark', 'I have accepted another opportunity.'));

    $this->post(
        route('candidate-intent-letters.portal.acknowledge', $token),
        ['candidate_name' => 'Asha Candidate', 'consent' => true]
    )->assertSessionHas('error');

    $this->from(route('candidate-intent-letters.portal.show', $token))
        ->post(route('candidate-intent-letters.portal.decline', $token), [
            'candidate_name' => 'Asha Candidate',
            'confirmation' => true,
        ])
        ->assertSessionHasErrors('reason');
});

test('a formal offer can only be created after the latest intent letter is acknowledged', function () {
    $candidate = intentLetterTestCandidate();
    $candidate->forceFill(['final_status' => 'selected'])->save();

    $user = User::query()->create([
        'first_name' => 'HR',
        'last_name' => 'Admin',
        'email' => 'hr-admin@example.test',
        'password' => Hash::make('password'),
    ]);
    $user->assignRole(Role::findOrCreate('admin', 'web'));

    $policy = new CandidateOfferPolicy();

    expect($policy->create($user, $candidate))->toBeFalse();

    $candidate->setRelation('latestIntentLetter', new CandidateIntentLetter([
        'status' => CandidateIntentLetter::STATUS_ACKNOWLEDGED,
        'acknowledged_at' => now(),
        'candidate_consent' => true,
    ]));

    expect($policy->create($user, $candidate))->toBeTrue();
});

function intentLetterTestCandidate(): Candidate
{
    $branch = Branch::query()->create([
        'name' => 'Indore',
        'code' => 'LOI',
        'address' => 'Test address',
        'city' => 'Indore',
        'state' => 'Madhya Pradesh',
        'postal_code' => '452011',
        'phone' => '9999999999',
        'email' => 'branch-loi@example.test',
    ]);

    return Candidate::query()->create([
        'first_name' => 'Asha',
        'last_name' => 'Candidate',
        'email' => 'asha@example.test',
        'phone' => '8888888888',
        'position_applied' => 'Customer Support Executive',
        'branch_id' => $branch->id,
    ]);
}
