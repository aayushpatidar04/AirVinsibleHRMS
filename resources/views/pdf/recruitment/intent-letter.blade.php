@php
    use App\Support\LetterHelper;

    $name = $candidate->full_name;
    $companyName = mb_strtoupper($company['name']);
    $inr = fn ($value) => LetterHelper::inr($value, false);

    // The letter must be accepted within three calendar days of issue (clause 19).
    $acceptBy = $letter->letter_date->copy()->addDays(3);

    $conditions = [
        'verification of identity', 'Aadhaar verification', 'PAN verification',
        'educational qualification verification', 'professional qualification verification',
        'employment history verification', 'experience verification', 'address verification',
        'police verification, wherever required', 'criminal background verification',
        'reference verification', 'right to work verification',
        'submission of all original documents for inspection', 'execution of the Appointment Letter',
        'execution of the Employment Agreement', 'execution of the Employee NDA',
        'acceptance of the Employee Handbook', 'acceptance of all HR Policies',
        'acceptance of Information Security Policy', 'medical fitness, if required',
        'successful completion of induction requirements', 'successful completion of Company training',
        'client approval, wherever applicable', 'process allocation', 'system access approval',
        'any additional requirement prescribed by the Company',
    ];

    $joiningDocuments = [
        'Aadhaar Card', 'PAN Card', 'Address Proof', 'Educational Certificates',
        'Experience Certificates', 'Relieving Letter from previous employer, if applicable',
        'Bank Account Details', 'Passport-size Photographs', 'UAN/EPF details, if applicable',
        'ESI details, if applicable', 'Police verification documents, where required',
        'Any additional document requested by the Company',
    ];
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Conditional Offer Letter - {{ $letter->letter_number }}</title>
    @include('pdf.recruitment.partials.letter-styles')
    <style>
        .title { text-align: center; font-weight: bold; text-decoration: underline; font-size: 13px; margin: 0 0 4px; }
        .subtitle { font-weight: bold; font-style: italic; margin: 0 0 12px; }
        .lead { font-weight: bold; }
        .alpha p { margin: 0 0 4px; padding-left: 4px; }
        .shifts p { margin: 0 0 2px; padding-left: 32px; }
        ol { margin: 2px 0 8px; padding-left: 38px; }
        .info { width: 100%; border-collapse: collapse; margin: 6px 0 10px; page-break-inside: avoid; }
        .info td { border: 1px solid #000; padding: 3px 7px; vertical-align: top; }
        .info td.label { width: 46%; }
        .sign-line { display: inline-block; min-width: 190px; border-bottom: 1px solid #000; }
        .sign-box { margin-top: 6px; page-break-inside: avoid; }
        .signature-image { height: 46px; margin: 4px 0; }
        .small { font-size: 10px; color: #333; }
    </style>
</head>
<body>
    @include('pdf.recruitment.partials.letter-chrome')

    <p class="title">CONDITIONAL OFFER LETTER</p>
    <p class="subtitle">(Subject to Verification, Compliance and Final Appointment)</p>

    <p><span class="bold">Ref. No.:</span> {{ $letter->letter_number }}</p>
    <p><span class="bold">Date:</span> {{ $letter->letter_date->format('d F Y') }}</p>

    <p class="bold" style="margin-bottom: 2px;">To,</p>
    <p class="bold" style="margin-bottom: 2px;">{{ $name }}</p>
    @if($candidate->phone)
        <p style="margin-bottom: 2px;">Mobile: {{ $candidate->phone }}</p>
    @endif
    @if($candidate->email)
        <p>Email: {{ $candidate->email }}</p>
    @endif

    <p class="bold" style="margin-top: 14px;">Subject: Conditional Offer of Employment</p>

    <p>Dear {{ $name }},</p>

    <p>We are pleased to extend to you this <span class="bold">Conditional Offer of Employment</span> for the position of <span class="bold">{{ $letter->position_title }}</span> with <span class="bold">{{ $companyName }}</span> ("Company").</p>

    <p>This Conditional Offer is made solely on the basis of the information, declarations, representations and documents submitted by you during the recruitment process and is <span class="bold">strictly subject to the terms and conditions set out herein</span>.</p>

    <p class="bold">This letter is only a Conditional Offer and shall not be construed as an Appointment Letter, Employment Agreement or confirmation of employment. No employer–employee relationship shall arise merely by issuance or acceptance of this Conditional Offer.</p>

    <p>Your employment shall commence only upon successful fulfilment of all conditions mentioned in this Offer, execution of all required employment documents and issuance of a formal Appointment Letter by the Company.</p>

    <p class="section">1. POSITION OFFERED</p>
    <p>Subject to fulfilment of all conditions contained herein, the Company proposes to engage you as:</p>
    <p style="margin-bottom: 3px;"><span class="bold">Designation:</span> {{ $letter->position_title }}</p>
    <p style="margin-bottom: 3px;"><span class="bold">Department:</span> {{ $letter->department ?? '—' }}</p>
    <p style="margin-bottom: 3px;"><span class="bold">Reporting To:</span> {{ $letter->reporting_to ?? '—' }}</p>
    <p><span class="bold">Work Location:</span> {{ $letter->work_location }}</p>
    <p>The Company reserves the right to change your designation, department, reporting structure, work location, shift, process or project based on business requirements.</p>

    <p class="section">2. CONDITIONAL NATURE OF THE OFFER</p>
    <p>This Offer is entirely conditional and shall become effective only after the Company confirms that all pre-employment requirements have been successfully completed.</p>
    <p style="margin-bottom: 2px;">The Company reserves the absolute right to:</p>
    <ul>
        <li>withdraw this Offer at any time before joining;</li>
        <li>modify the offered position;</li>
        <li>revise compensation prior to appointment;</li>
        <li>postpone joining;</li>
        <li>cancel recruitment;</li>
        <li>place the candidate on hold; or</li>
        <li>decline employment,</li>
    </ul>
    <p>without assigning any reason and without any obligation to provide compensation, damages or employment.</p>
    <p>Acceptance of this Offer shall not create any vested right to employment.</p>

    <p class="section">3. PRE-EMPLOYMENT CONDITIONS</p>
    <p>Your appointment shall remain subject to satisfactory completion of all of the following conditions:</p>
    <div class="alpha">
        @foreach($conditions as $index => $condition)
            <p>({{ chr(97 + $index) }}) {{ $condition }}{{ $loop->last ? '.' : ($loop->remaining === 1 ? '; and' : ';') }}</p>
        @endforeach
    </div>
    <p>Failure to satisfy any condition may result in withdrawal of this Offer without further notice.</p>

    <p class="section">4. DOCUMENT VERIFICATION</p>
    <p>You shall produce original documents whenever required by the Company.</p>
    <p style="margin-bottom: 2px;">If at any stage it is found that:</p>
    <ul>
        <li>any information supplied is false;</li>
        <li>any material fact has been concealed;</li>
        <li>forged documents have been submitted;</li>
        <li>qualifications are misrepresented;</li>
        <li>employment history is inaccurate;</li>
        <li>experience certificates are fabricated; or</li>
        <li>any declaration is misleading,</li>
    </ul>
    <p>the Company may immediately withdraw this Offer or terminate employment (if already commenced), without prejudice to any legal remedies available under law.</p>

    <p class="section">5. BACKGROUND VERIFICATION</p>
    <p>The Company may engage independent agencies for conducting background verification.</p>
    <p style="margin-bottom: 2px;">You hereby authorize the Company to verify information from:</p>
    <ul>
        <li>previous employers;</li>
        <li>educational institutions;</li>
        <li>government authorities;</li>
        <li>law enforcement agencies;</li>
        <li>financial institutions, where legally permissible;</li>
        <li>professional bodies;</li>
        <li>references provided by you; and</li>
        <li>any other lawful source.</li>
    </ul>
    <p>Any adverse verification report may result in cancellation of this Offer.</p>

    <p class="section">6. MEDICAL FITNESS</p>
    <p>Where required by the Company, your appointment shall remain subject to satisfactory medical examination by a medical practitioner or medical institution approved by the Company.</p>
    <p>The Company may defer or withdraw this Offer where the candidate is found medically unfit for the offered role, subject to applicable law.</p>

    <p class="section">7. CONFIDENTIALITY OF THE OFFER</p>
    <p>This Conditional Offer and its contents are confidential.</p>
    <p>You shall not disclose, circulate, publish or reproduce this Offer or any compensation details to any unauthorized person, except where disclosure is required by law or for obtaining professional advice.</p>

    <p class="section">8. NO EMPLOYMENT UNTIL APPOINTMENT LETTER</p>
    <p style="margin-bottom: 2px;">You expressly acknowledge that:</p>
    <ul>
        <li>this document is not an Appointment Letter;</li>
        <li>this document is not an Employment Agreement;</li>
        <li>this document does not guarantee employment;</li>
        <li>no salary shall become payable merely because this Offer has been issued or accepted; and</li>
        <li>employment shall commence only from the joining date specified in the formal Appointment Letter issued by the Company.</li>
    </ul>

    <p class="section">9. COMPANY'S ABSOLUTE RIGHT TO WITHDRAW OFFER</p>
    <p style="margin-bottom: 2px;">Notwithstanding anything contained herein, the Company reserves the unrestricted right to withdraw, revoke, cancel or amend this Conditional Offer at any time before commencement of employment if:</p>
    <ul>
        <li>business requirements change;</li>
        <li>the position is discontinued;</li>
        <li>client requirements change;</li>
        <li>verification is unsatisfactory;</li>
        <li>documents remain incomplete;</li>
        <li>the candidate fails to join within the stipulated time;</li>
        <li>any declaration is found inaccurate;</li>
        <li>regulatory requirements so require; or</li>
        <li>any circumstance arises making employment commercially or operationally impracticable.</li>
    </ul>
    <p>Such withdrawal shall not give rise to any claim for compensation, damages or employment against the Company.</p>

    <p class="section">10. PROPOSED COMPENSATION</p>
    <p>Subject to fulfilment of all conditions contained in this Offer and issuance of a formal Appointment Letter, the proposed compensation for the offered position shall be as follows:</p>
    <table class="info">
        <tr>
            <td class="label">Proposed CTC (Annual)</td>
            <td>₹ {{ $inr($letter->monthly_ctc * 12) }}</td>
        </tr>
        <tr>
            <td class="label">Estimated Monthly In-Hand Salary (after applicable PF and ESIC deductions)</td>
            <td>₹ {{ $inr($letter->monthly_in_hand) }}</td>
        </tr>
        <tr>
            <td class="label">Performance Incentive (if applicable)</td>
            <td>As per Company policy</td>
        </tr>
        <tr>
            <td class="label">Shift Allowance (if applicable)</td>
            <td>As per Company policy</td>
        </tr>
    </table>

    <p class="bold" style="margin-bottom: 4px;">COMPANY'S RIGHT TO WITHDRAW THE OFFER</p>
    <p>The Company reserves the absolute right, at its sole discretion, to withdraw, revoke, cancel, or modify this Offer Letter at any time within <span class="bold">seven (7) calendar days</span> from the date of issuance of this Offer Letter or at any time before the Candidate's joining and issuance of the Appointment Letter, without assigning any reason and without incurring any liability whatsoever.</p>
    <p style="margin-bottom: 2px;">This wording ensures:</p>
    <ul>
        <li><strong>Candidate:</strong> must accept within <strong>3 days</strong>.</li>
        <li><strong>Company:</strong> may withdraw within <strong>7 days</strong> or <strong>before joining/appointment</strong> if required. This provides stronger contractual protection for the employer.</li>
    </ul>
    
    <p class="section">11. PROBATION</p>
    <p>Upon joining, you shall ordinarily be placed on probation for the period specified in the Appointment Letter.</p>
    <p>During probation, your performance, attendance, punctuality, conduct, learning ability, policy compliance and overall suitability shall be evaluated.</p>
    <p>The Company may extend, reduce or waive the probation period at its discretion and subject to applicable law.</p>

    <p class="section">12. WORKING HOURS, SHIFTS AND LOCATION</p>
    <p>You acknowledge that {{ $companyName }} operates in a 24×7×365 business environment.</p>
    <p style="margin-bottom: 2px;">Accordingly, you may be required to work in:</p>
    <div class="shifts">
        <p>Morning shifts;</p>
        <p>Evening shifts;</p>
        <p>Night shifts;</p>
        <p>Rotational shifts;</p>
        <p>Weekend shifts;</p>
        <p>Public holidays; or</p>
        <p>Client-specific schedules.</p>
    </div>
    <p style="margin-top: 8px;">The Company may change your shift, work location, department, process, project or reporting structure based on business requirements.</p>

    <p class="section">13. TRANSFER AND DEPLOYMENT</p>
    <p>The Company reserves the right to transfer, deploy or assign you to any department, process, client account, project, business unit or location within India, subject to business requirements and applicable law.</p>

    <p class="section">14. CONFIDENTIALITY AND DATA PROTECTION</p>
    <p>Your employment shall be subject to execution of the Company's Employee Non-Disclosure, Confidentiality, Data Protection &amp; Intellectual Property Agreement (NDA).</p>
    <p>You shall be required to maintain strict confidentiality regarding all Company, client and customer information.</p>

    <p class="section">15. INTELLECTUAL PROPERTY</p>
    <p>All work, inventions, processes, documents, reports, software, databases, presentations, training material, client solutions and other intellectual property created during the course of employment and relating to the Company's business shall belong exclusively to the Company, subject to applicable law.</p>

    <p class="section">16. TRAINING AND CERTIFICATION</p>
    <p>The Company may require you to undergo induction, process training, compliance training, information security training, quality training and client-specific certification programmes.</p>
    <p>Successful completion of such training may be a condition for continuation of employment.</p>

    <p class="section">17. TENTATIVE JOINING DATE</p>
    <p>Your joining and commencement of training will be subject to confirmation of the training batch by the Company. The below-mentioned date is tentative and may be rescheduled depending on batch formation, operational requirements, and business needs.</p>
    <p>The Company will communicate the <span class="bold">confirmed Date of Joining (DOJ)</span> to you through an official communication, such as email, SMS, or WhatsApp, before your joining.</p>
    <p>In case the scheduled training batch is postponed or rescheduled, your revised DOJ will be communicated to you accordingly. Your employment and training commencement will be effective from the confirmed DOJ, subject to completion of the Company's joining formalities and applicable employment conditions.</p>
    <p>This conditional offer is subject to verification of the information and documents provided by you and fulfilment of the Company's eligibility and employment requirements.</p>
    <p>Subject to fulfilment of all conditions, your proposed tentative joining date shall be:</p>
    <table class="info">
        <tr>
            <td class="label">Proposed tentative Joining Date</td>
            <td>{{ $letter->joining_date->format('d F Y') }}</td>
        </tr>
        <tr>
            <td class="label">Reporting Venue</td>
            <td>{{ $letter->work_location }}</td>
        </tr>
    </table>
    <p>Failure to join on the scheduled date without prior written approval may result in automatic lapse or withdrawal of this Offer.</p>

    <p class="section">18. DOCUMENTS REQUIRED AT JOINING</p>
    <p style="margin-bottom: 2px;">At the time of joining, you shall be required to submit self-attested copies and produce originals for verification of:</p>
    <ol>
        @foreach($joiningDocuments as $document)
            <li>{{ $document }}{{ $loop->last ? '.' : ($loop->remaining === 1 ? '; and' : ';') }}</li>
        @endforeach
    </ol>

    <p class="section">19. ACCEPTANCE OF OFFER</p>
    <p>If you wish to accept this Conditional Offer, please sign and return a copy of this letter on or before {{ $acceptBy->format('d F Y') }}.</p>
    <p>The Candidate shall communicate acceptance of this Offer Letter by signing and returning a copy of the same to the Company within <span class="bold">three (3) calendar days</span> from the date of issuance of this Offer Letter.</p>
    <p>If the Candidate fails to accept this Offer within the aforesaid period, this Offer shall automatically lapse and stand withdrawn without any further notice from the Company. The Company shall thereafter be free to offer the position to any other candidate or otherwise deal with the vacancy at its sole discretion.</p>
    <p>Acceptance of this Offer by the Candidate shall not create an employer-employee relationship. Employment shall commence only upon the Candidate's joining the Company on the specified date and upon issuance of the formal Appointment Letter, subject to fulfilment of all pre-employment conditions stipulated by the Company.</p>

    <p class="section">20. GOVERNING LAW AND JURISDICTION</p>
    <p>This Conditional Offer shall be governed by the laws of India.</p>
    <p>Any dispute relating to this Offer shall be subject to the exclusive jurisdiction of the competent courts at Indore, Madhya Pradesh, subject to any arbitration clause contained in the final Employment Agreement.</p>

    <div class="nobreak">
        <p class="section">21. CANDIDATE DECLARATION</p>
        <p style="margin-bottom: 2px;">I, {{ $name }}, hereby declare that:</p>
        <ul>
            <li>all information provided by me is true and correct;</li>
            <li>I understand that this is only a Conditional Offer and not a final appointment;</li>
            <li>I agree to undergo all verification processes;</li>
            <li>I agree to comply with all Company policies;</li>
            <li>I understand that the Company may withdraw this Offer before joining without assigning any reason; and</li>
            <li>I voluntarily accept the terms and conditions contained in this Conditional Offer Letter.</li>
        </ul>
    </div>

    <div class="sign-box">
        <p class="section" style="margin-top: 14px;">For {{ $companyName }}</p>
        <p style="margin-bottom: 2px;">Authorized Signatory</p>
        @if($company['signature_path'])
            <img src="{{ $company['signature_path'] }}" class="signature-image" alt="Signature"><br>
        @else
            <p style="margin: 14px 0 4px;">Signature</p>
        @endif
        <p style="margin-bottom: 2px;"><span class="bold">Name :</span> {{ $company['hr_name'] }}</p>
        <p style="margin-bottom: 2px;"><span class="bold">Designation :</span> {{ $company['hr_designation'] }}</p>
        <p><span class="bold">Date :</span> {{ $letter->letter_date->format('d F Y') }}</p>
    </div>

    <div class="sign-box">
        <p class="bold" style="margin-top: 14px; margin-bottom: 6px;">Candidate Acceptance</p>

        @if($includeAcknowledgement)
            <p style="margin-bottom: 2px;">Signature: <span class="signed">{{ $letter->acknowledgement_name }}</span></p>
            <p style="margin-bottom: 2px;"><span class="bold">Name :</span> {{ $letter->acknowledgement_name }}</p>
            <p style="margin-bottom: 6px;"><span class="bold">Date :</span> {{ $letter->acknowledged_at->format('d F Y, h:i A') }}</p>
            <p class="small">{{ $letter->acknowledgement_text }}</p>
        @else
            <p style="margin-bottom: 8px;">Signature <span class="sign-line">&nbsp;</span></p>
            <p style="margin-bottom: 8px;"><span class="bold">Name :</span> <span class="sign-line">&nbsp;</span></p>
            <p><span class="bold">Date :</span> <span class="sign-line">&nbsp;</span></p>
        @endif
    </div>
</body>
</html>
