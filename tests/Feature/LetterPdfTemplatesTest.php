<?php

use App\Models\Candidate;
use App\Models\CandidateIntentLetter;
use App\Models\CandidateOffer;
use App\Models\OfferSalaryComponent;
use App\Support\LetterHelper;
use Carbon\Carbon;

function letterTestCompany(): array
{
    return [
        'name' => 'Airvincible',
        'legal_name' => 'Airvincible Pvt. Ltd.',
        'logo_path' => null,
        'footer' => LetterHelper::footer(),
        'address' => '',
        'hr_name' => 'Sumit Arora',
        'hr_designation' => 'Talent Acquisition Head',
        'signature_path' => null,
    ];
}

function letterTestCandidate(): Candidate
{
    return new Candidate([
        'first_name' => 'Adarsh',
        'last_name' => 'Bairagi',
        'email' => 'adarsh@example.test',
    ]);
}

test('inr formats amounts with Indian digit grouping', function () {
    expect(LetterHelper::inr(156000))->toBe('1,56,000')
        ->and(LetterHelper::inr(167895))->toBe('1,67,895')
        ->and(LetterHelper::inr(13000))->toBe('13,000')
        ->and(LetterHelper::inr(999))->toBe('999')
        ->and(LetterHelper::inr(12171.5))->toBe('12,171.50')
        ->and(LetterHelper::inr(12345678))->toBe('1,23,45,678')
        ->and(LetterHelper::inr(0))->toBe('-')
        ->and(LetterHelper::inr(null))->toBe('-')
        ->and(LetterHelper::inr(0, false))->toBe('0');
});

test('footer falls back to the standard letterhead line', function () {
    config(['company.letter_footer' => null]);

    expect(LetterHelper::footer())->toContain('Mangal City Mall')
        ->and(LetterHelper::footer())->toContain('hr@airvincible.com');
});

test('logo and signature helpers return null when files are missing', function () {
    config([
        'company.logo_path' => 'does/not/exist.png',
        'company.hr_signature_path' => 'does/not/exist.png',
    ]);

    expect(LetterHelper::signaturePath())->toBeNull();
});

test('the offer letter follows the new format', function () {
    $offer = new CandidateOffer([
        'offer_number' => 'OFR-1',
        'version' => 1,
        'status' => 'approved',
        'designation' => 'Advisor',
        'work_location' => 'Airvincible, 401, 4th Floor Workie Tech Park A.B. Road, Indore M.P.',
    ]);
    $offer->joining_date = Carbon::parse('2026-07-15');

    $component = fn (string $name, string $type, float $amount) => new OfferSalaryComponent([
        'component_name' => $name,
        'component_type' => $type,
        'calculation_type' => 'fixed',
        'amount' => $amount,
        'frequency' => 'monthly',
        'show_in_offer' => true,
        'affects_in_hand' => true,
    ]);

    $offer->setRelation('salaryComponents', collect([
        $component('Basic', 'earning', 6500),
        $component('HRA', 'earning', 6500),
        $component('PF employee Contribution', 'deduction', 780),
        $component('ESIC employee Contribution', 'deduction', 49),
        $component('PF Company Contribution', 'employer_contribution', 780),
        $component('ESIC Company Contribution', 'employer_contribution', 211),
    ]));
    $offer->setRelation('branch', null);

    $html = view('pdf.recruitment.offer-letter', [
        'offer' => $offer,
        'candidate' => letterTestCandidate(),
        'salarySummary' => [],
        'company' => letterTestCompany(),
    ])->render();

    expect($html)
        ->toContain('Offer Letter,')
        ->toContain('Subject: <span class="bold">Offer of Employment</span>')
        ->toContain('Dear <span class="bold">Adarsh Bairagi</span>')
        ->toContain('Indore M.P.')
        ->toContain('Gross Total (A)')
        ->toContain('1,56,000')
        ->toContain('Take Home')
        ->toContain('12,171')
        ->toContain('Total Retirals (B)')
        ->toContain('Total CTC')
        ->toContain('13,991')
        ->toContain('Wednesday, July 15, 2026')
        ->toContain('2. Documents Required on DOJ')
        ->toContain('3. General Terms')
        ->toContain('HUMAN RESOURCE POLICIES')
        ->toContain('10. Zero Tolerance Policy')
        ->toContain('Employee Declaration')
        ->toContain('Sumit Arora')
        ->toContain('Talent Acquisition Head')
        ->toContain('Mangal City Mall')
        ->not->toContain('DRAFT');
});

test('draft offers keep the draft watermark and custom terms replace the defaults', function () {
    $offer = new CandidateOffer([
        'offer_number' => 'OFR-2',
        'version' => 1,
        'status' => 'draft',
        'designation' => 'Advisor',
        'offer_terms' => 'Custom term for this offer only.',
    ]);
    $offer->setRelation('salaryComponents', collect());
    $offer->setRelation('branch', null);

    $html = view('pdf.recruitment.offer-letter', [
        'offer' => $offer,
        'candidate' => letterTestCandidate(),
        'salarySummary' => [],
        'company' => letterTestCompany(),
    ])->render();

    expect($html)
        ->toContain('DRAFT')
        ->toContain('Custom term for this offer only.')
        ->not->toContain('If failed to join on DOJ offer letter will be forfeited.');
});

test('the letter of intent follows the new format', function () {
    $letter = new CandidateIntentLetter([
        'letter_number' => 'LOI-1',
        'position_title' => 'Advisor',
        'work_location' => 'Indore',
        'monthly_ctc' => 13991,
        'monthly_in_hand' => 12171,
    ]);
    $letter->letter_date = Carbon::parse('2026-07-10');
    $letter->joining_date = Carbon::parse('2026-07-15');

    $html = view('pdf.recruitment.intent-letter', [
        'letter' => $letter,
        'candidate' => letterTestCandidate(),
        'company' => letterTestCompany(),
        'includeAcknowledgement' => false,
    ])->render();

    expect($html)
        ->toContain('LETTER OF INTENT')
        ->toContain('Subject: Letter of Intent for Employment')
        ->toContain('Dear Adarsh Bairagi,')
        ->toContain('CTC will be Rs. 13,991/-.')
        ->toContain('In-Hand Salary after applicable PF and ESIC deductions will be Rs. 12,171/-')
        ->toContain('Date of Joining (DOJ) is 15 July 2026')
        ->toContain('2. Pre-Joining Conditions')
        ->toContain('3. Documents Required on DOJ')
        ->toContain('4. General Terms &amp; Conditions')
        ->toContain('Employee Declaration')
        ->toContain('Warm Regards,')
        ->toContain('HR Department')
        ->toContain('Mangal City Mall');
});
