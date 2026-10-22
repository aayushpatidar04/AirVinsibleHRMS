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

function appointmentOffer(array $attributes = []): CandidateOffer
{
    $offer = new CandidateOffer(array_merge([
        'offer_number' => 'OFR-2026-0007',
        'version' => 1,
        'status' => 'approved',
        'designation' => 'Advisor',
        'department' => 'Customer Support',
        'probation_months' => 6,
        'reporting_time' => '10:00:00',
        'work_location' => 'Airvincible, 401, 4th Floor Workie Tech Park A.B. Road, Indore M.P.',
    ], $attributes));
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

    return $offer;
}

function renderAppointment(CandidateOffer $offer, ?Candidate $candidate = null): string
{
    return view('pdf.recruitment.offer-letter', [
        'offer' => $offer,
        'candidate' => $candidate ?? letterTestCandidate(),
        'salarySummary' => [],
        'company' => letterTestCompany(),
    ])->render();
}

test('the offer letter follows the appointment letter format', function () {
    $candidate = letterTestCandidate();
    $candidate->phone = '9876543210';

    $html = renderAppointment(appointmentOffer(), $candidate);

    expect($html)
        ->toContain('APPOINTMENT LETTER')
        ->toContain('PART – I')
        ->toContain('Appointment, Employment Terms, Position, Probation &amp; General Conditions')
        ->toContain('OFR-2026-0007')
        ->toContain('Mr./Ms. Adarsh Bairagi')
        ->toContain('Email: adarsh@example.test')
        ->toContain('Mobile No.: 9876543210')
        ->toContain('Subject: Appointment as Advisor')
        ->toContain('Dear Mr./Ms. Adarsh Bairagi,')
        ->toContain('Customer Support Department')
        ->toContain("15\u{00A0}/\u{00A0}07\u{00A0}/\u{00A0}2026")
        ->toContain('Indore M.P.')
        ->toContain('probation for six (6) months')
        ->toContain('10:00 AM')
        ->toContain('1. APPOINTMENT')
        ->toContain('14. COMPENSATION')
        ->toContain('19.4 Attendance Manipulation')
        ->toContain('39. POST-EMPLOYMENT OBLIGATIONS')
        ->toContain('46. EMPLOYEE DECLARATION')
        ->toContain('FOR AIRVINCIBLE')
        ->toContain('Sumit Arora')
        ->toContain('Talent Acquisition Head')
        ->toContain('WITNESS – 2')
        ->toContain('ANNEXURE – A')
        ->toContain('Gross Total (A)')
        ->toContain('1,56,000')
        ->toContain('12,171')
        ->toContain('Total CTC')
        ->toContain('13,991')
        ->toContain('Mangal City Mall')
        ->not->toContain('DRAFT');
});

test('fields the offer does not carry stay as blank lines', function () {
    $offer = appointmentOffer(['department' => null, 'reporting_time' => null, 'probation_months' => null]);
    $offer->joining_date = null;

    $html = renderAppointment($offer, new Candidate(['first_name' => 'Asha', 'last_name' => 'Verma']));

    expect($html)
        ->toContain('Address: ______________________________________')
        ->toContain('Working Hours:</span> __________')
        ->toContain('Reporting Time:</span> ____________________')
        ->toContain('____________________ Department')
        ->toContain("___\u{00A0}/\u{00A0}___\u{00A0}/\u{00A0}20___")
        ->toContain('Email: ____________________')
        ->toContain('Employee ID:</span> ___________________________')
        // The Word template says six months when the offer doesn't specify.
        ->toContain('probation for six (6) months');
});

test('probation follows the months stored on the offer', function () {
    expect(renderAppointment(appointmentOffer(['probation_months' => 3])))
        ->toContain('probation for three (3) months');

    expect(renderAppointment(appointmentOffer(['probation_months' => 1])))
        ->toContain('probation for one (1) month</span>');
});

test('draft offers keep the draft watermark and extra terms move to the annexure', function () {
    $html = renderAppointment(appointmentOffer([
        'status' => 'draft',
        'offer_terms' => 'Custom term for this offer only.',
    ]));

    expect($html)
        ->toContain('DRAFT')
        ->toContain('Additional Terms')
        ->toContain('Custom term for this offer only.');
});

test('the intent letter follows the conditional offer letter format', function () {
    $candidate = letterTestCandidate();
    $candidate->phone = '9876543210';

    $letter = new CandidateIntentLetter([
        'letter_number' => 'LOI-2026-00012',
        'position_title' => 'Advisor',
        'work_location' => 'Indore',
        'monthly_ctc' => 13991,
        'monthly_in_hand' => 12171,
    ]);
    $letter->letter_date = Carbon::parse('2026-07-10');
    $letter->joining_date = Carbon::parse('2026-07-15');

    $html = view('pdf.recruitment.intent-letter', [
        'letter' => $letter,
        'candidate' => $candidate,
        'company' => letterTestCompany(),
        'includeAcknowledgement' => false,
    ])->render();

    expect($html)
        ->toContain('CONDITIONAL OFFER LETTER')
        ->toContain('(Subject to Verification, Compliance and Final Appointment)')
        ->toContain('LOI-2026-00012')
        ->toContain('Mobile: 9876543210')
        ->toContain('Subject: Conditional Offer of Employment')
        ->toContain('Dear Adarsh Bairagi,')
        ->toContain('1. POSITION OFFERED')
        ->toContain('3. PRE-EMPLOYMENT CONDITIONS')
        ->toContain('(z) any additional requirement prescribed by the Company.')
        ->toContain('10. PROPOSED COMPENSATION')
        ->toContain('₹ 1,67,892')
        ->toContain('₹ 12,171')
        ->toContain('seven (7) calendar days')
        ->toContain('15 July 2026')
        ->toContain('on or before 13 July 2026')
        ->toContain('21. CANDIDATE DECLARATION')
        ->toContain('For AIRVINCIBLE')
        ->toContain('Sumit Arora')
        ->toContain('Candidate Acceptance')
        // Drafting notes in the Word template are not part of the letter.
        ->not->toContain('This wording ensures')
        ->not->toContain('Letter of Intent');
});

test('the acknowledged intent letter shows the candidate signature block', function () {
    $letter = new CandidateIntentLetter([
        'letter_number' => 'LOI-2026-00013',
        'position_title' => 'Advisor',
        'work_location' => 'Indore',
        'monthly_ctc' => 13991,
        'monthly_in_hand' => 12171,
        'acknowledgement_name' => 'Adarsh Bairagi',
        'acknowledgement_text' => 'Recorded consent text.',
    ]);
    $letter->letter_date = Carbon::parse('2026-07-10');
    $letter->joining_date = Carbon::parse('2026-07-15');
    $letter->acknowledged_at = Carbon::parse('2026-07-11 10:30');

    $html = view('pdf.recruitment.intent-letter', [
        'letter' => $letter,
        'candidate' => letterTestCandidate(),
        'company' => letterTestCompany(),
        'includeAcknowledgement' => true,
    ])->render();

    expect($html)
        ->toContain('<span class="signed">Adarsh Bairagi</span>')
        ->toContain('11 July 2026, 10:30 AM')
        ->toContain('Recorded consent text.');
});
