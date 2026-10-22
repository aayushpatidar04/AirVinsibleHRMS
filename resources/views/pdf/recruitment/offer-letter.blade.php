@php
    use App\Support\LetterHelper;

    $name = $candidate->full_name
        ?? trim(($candidate->first_name ?? '') . ' ' . ($candidate->last_name ?? ''));

    // Fields the offer does not carry stay as blank lines, exactly as in the Word template.
    $blank = '____________________';

    $designation = $offer->designation ?: $blank;
    $department = $offer->department ?: $blank;
    $nbsp = fn (string $value) => str_replace(' ', "\u{00A0}", $value);
    $today = $nbsp(now()->format('d / m / Y'));
    $joiningDate = $nbsp($offer->joining_date ? $offer->joining_date->format('d / m / Y') : '___ / ___ / 20___');

    $reportingTime = $blank;
    if ($offer->reporting_time) {
        try {
            $reportingTime = \Carbon\Carbon::parse($offer->reporting_time)->format('h:i A');
        } catch (\Throwable $e) {
            $reportingTime = (string) $offer->reporting_time;
        }
    }

    $probationMonths = (int) ($offer->probation_months ?: 6);
    $monthWords = [1 => 'one', 2 => 'two', 3 => 'three', 4 => 'four', 5 => 'five', 6 => 'six',
        7 => 'seven', 8 => 'eight', 9 => 'nine', 10 => 'ten', 11 => 'eleven', 12 => 'twelve'];
    $probationWords = $monthWords[$probationMonths] ?? (string) $probationMonths;

    $location = $offer->work_location
        ?: trim(implode(', ', array_filter([$offer->branch?->name, $offer->branch?->address, $offer->branch?->city])))
        ?: ($company['address'] ?? '')
        ?: $blank;

    // Annexure-A: salary components
    $visible = $offer->salaryComponents->where('show_in_offer', true);

    $monthly = fn ($c) => (float) ($c->monthly_amount ?? $c->calculated_monthly_amount ?? 0);
    $annual = fn ($c) => (float) ($c->annual_amount ?? $c->calculated_annual_amount ?? 0);

    $earnings = $visible->where('component_type', 'earning');
    $deductions = $visible->where('component_type', 'deduction');
    $retirals = $visible->where('component_type', 'employer_contribution');

    $gross = ['m' => $earnings->sum($monthly), 'a' => $earnings->sum($annual)];

    // Only deductions that reduce the in-hand salary count towards Take Home.
    $takeHomeDeductions = $deductions->filter(fn ($c) => $c->affects_in_hand ?? true);
    $takeHome = [
        'm' => $gross['m'] - $takeHomeDeductions->sum($monthly),
        'a' => $gross['a'] - $takeHomeDeductions->sum($annual),
    ];

    $retiralTotal = ['m' => $retirals->sum($monthly), 'a' => $retirals->sum($annual)];
    $ctc = ['m' => $gross['m'] + $retiralTotal['m'], 'a' => $gross['a'] + $retiralTotal['a']];

    $inr = fn ($v) => LetterHelper::inr($v);
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Appointment Letter - {{ $offer->offer_number }}</title>
    @include('pdf.recruitment.partials.letter-styles')
    <style>
        .front-h1, .front-h2, .front-h3 { font-weight: bold; margin: 0 0 8px; }
        .front-h1 { font-size: 13px; }
        .subsection { margin: 12px 0 4px; font-weight: bold; page-break-after: avoid; }
        ol { margin: 2px 0 8px; padding-left: 38px; }
        .signature-image { height: 44px; }
        .salary-table { width: 100%; border-collapse: collapse; margin: 8px 0 6px; page-break-inside: avoid; }
        .salary-table th, .salary-table td { border: 1px solid #000; padding: 1px 5px; font-size: 11px; line-height: 1.35; vertical-align: top; }
        .salary-table th { text-align: left; font-weight: bold; }
        .salary-table .num { text-align: right; }
        .salary-table .total td, .salary-table .group td { font-weight: bold; }
        .annexure { page-break-before: always; }
    </style>
</head>
<body>
    @include('pdf.recruitment.partials.letter-chrome')

    @if(in_array($offer->status, ['draft', 'pending_approval'], true))
        <div class="watermark">DRAFT</div>
    @endif

<p class="front-h1"><span class="bold">APPOINTMENT LETTER</span></p>
<p class="front-h2"><span class="bold">PART – I</span></p>
<p class="front-h3"><span class="bold">Appointment, Employment Terms, Position, Probation &amp; General Conditions</span></p>
<p><span class="bold">Ref. No.:</span> {{ $offer->offer_number }}</p>
<p><span class="bold">Date:</span> {{ $today }}</p>
<p class="front-h3">TO,</p>
<p><span class="bold">Mr./Ms. {{ $name }}</span></p>
<p>Address: ______________________________________</p>
<p>Email: {{ $candidate->email ?: $blank }}</p>
<p>Mobile No.: {{ $candidate->phone ?: $blank }}</p>
<p class="front-h2"><span class="bold">Subject: Appointment as {{ $designation }}</span></p>
<p>Dear Mr./Ms. {{ $name }},</p>
<p>We are pleased to appoint you as <span class="bold">{{ $designation }}</span> with <span class="bold">AIRVINCIBLE</span> ("Company") on the terms and conditions contained in this Appointment Letter.</p>
<p>This Appointment Letter shall constitute the principal contract of employment between you and the Company and shall be read together with the Company's Human Resources Policy, Employee Handbook, Code of Conduct, Leave &amp; Attendance Policy, Zero Tolerance Policy, Information Security Policy, Employee Confidentiality &amp; NDA, POSH Policy and all other policies issued by the Company from time to time.</p>
<p>By accepting this Appointment Letter, you acknowledge that you have carefully read, understood and voluntarily accepted all terms and conditions governing your employment.</p>
<p class="section">1. APPOINTMENT</p>
<p>You are hereby appointed as <span class="bold">{{ $designation }}</span> in the <span class="bold">{{ $department }} Department</span> of AIRVINCIBLE with effect from <span class="bold">{{ $joiningDate }}</span> ("Joining Date").</p>
<p>Your appointment is based upon the information, declarations, documents and representations submitted by you during the recruitment process.</p>
<p>Your employment shall remain subject to:</p>
<ul>
<li>satisfactory verification of documents;</li>
<li>successful completion of background verification;</li>
<li>compliance with Company policies;</li>
<li>execution of all employment-related documents;</li>
<li>satisfactory performance;</li>
<li>adherence to Company standards; and</li>
<li>continued business requirements.</li>
<li>Police verification.</li>
</ul>
<p class="section">2. NATURE OF EMPLOYMENT</p>
<p>Your employment shall be <span class="bold">full-time employment</span>.</p>
<p>Nothing contained in this Appointment Letter shall be interpreted as creating permanent employment or employment for a fixed tenure unless specifically communicated by the Company in writing.</p>
<p>The Company reserves the right to modify the nature of employment in accordance with applicable law and business requirements.</p>
<p class="section">3. PLACE OF POSTING</p>
<p>Your initial place of posting shall be:</p>
<p><span class="bold">{{ $location }}</span></p>
<p>However, considering the operational requirements of the Company, you may be transferred, deputed or assigned to:</p>
<ul>
<li>any office of the Company;</li>
<li>client premises;</li>
<li>project sites;</li>
<li>branch offices;</li>
<li>affiliate entities;</li>
<li>remote working locations;</li>
<li>hybrid work locations; or</li>
<li>any other place within India as may be considered necessary.</li>
</ul>
<p>Such transfer shall not constitute alteration of your employment.</p>
<p class="section">4. DESIGNATION</p>
<p>Your initial designation shall be:</p>
<p><span class="bold">{{ $designation }}</span></p>
<p>The Company reserves the right to change your designation, reporting structure, department, team, client allocation or functional responsibilities without affecting the continuity of your employment, provided such changes are made for legitimate business reasons.</p>
<p class="section">5. REPORTING AUTHORITY</p>
<p>You shall report to the Reporting Manager designated by the Company.</p>
<p>The Reporting Authority may be changed at any time depending upon:</p>
<ul>
<li>operational restructuring;</li>
<li>client allocation;</li>
<li>business requirements;</li>
<li>internal promotions;</li>
<li>organizational restructuring; or</li>
<li>management decisions.</li>
<li>Talent acquisition.</li>
<li>HR/ IT/ ADMIN.</li>
</ul>
<p>You shall comply with all lawful directions issued by your Reporting Manager or any authorised representative of the Company.</p>
<p class="section">6. PROBATION</p>
<p>You shall initially be on <span class="bold">probation for {{ $probationWords }} ({{ $probationMonths }}) {{ $probationMonths === 1 ? 'month' : 'months' }}</span> from the date of joining.</p>
<p>The probation period may be:</p>
<ul>
<li>extended;</li>
<li>reduced; or</li>
<li>modified,</li>
</ul>
<p>at the sole discretion of the Company based upon your:</p>
<ul>
<li>attendance;</li>
<li>punctuality;</li>
<li>discipline;</li>
<li>behaviour;</li>
<li>integrity;</li>
<li>work quality;</li>
<li>productivity;</li>
<li>client feedback;</li>
<li>training performance;</li>
<li>policy compliance; and</li>
<li>overall suitability.</li>
</ul>
<p>Extension of probation shall not require your consent.</p>
<p>The Company may terminate your employment during probation in accordance with applicable law and the terms of this Appointment Letter.</p>
<p class="section">7. CONFIRMATION OF EMPLOYMENT</p>
<p>Completion of the probation period shall not automatically result in confirmation.</p>
<p>Confirmation shall be granted only after:</p>
<ul>
<li>successful completion of probation;</li>
<li>satisfactory performance evaluation;</li>
<li>successful completion of mandatory training;</li>
<li>satisfactory attendance record;</li>
<li>compliance with Company policies;</li>
<li>satisfactory conduct;</li>
<li>completion of all documentation; and</li>
<li>issuance of a written Confirmation Letter by the Company.</li>
</ul>
<p>In the absence of a written Confirmation Letter, you shall continue to remain on probation or as otherwise communicated by the Company.</p>
<p class="section">8. JOB RESPONSIBILITIES</p>
<p>You shall faithfully, honestly and diligently perform the duties assigned to you.</p>
<p>Your responsibilities shall include:</p>
<ul>
<li>achieving performance targets;</li>
<li>complying with client requirements;</li>
<li>maintaining service quality;</li>
<li>protecting confidential information;</li>
<li>adhering to Company policies;</li>
<li>maintaining professional conduct;</li>
<li>timely reporting;</li>
<li>cooperating with colleagues;</li>
<li>protecting Company assets;</li>
<li>complying with applicable laws; and</li>
<li>performing any other duties reasonably assigned by the Company.</li>
</ul>
<p>Your duties may change from time to time depending upon operational requirements.</p>
<p class="section">9. WORKING HOURS</p>
<p>Considering the nature of the Company's BPO operations, you shall work according to the working hours, shifts and schedules communicated by the Company.</p>
<p>Your normal working schedule shall ordinarily be:</p>
<p><span class="bold">Reporting Time:</span> {{ $reportingTime }}</p>
<p><span class="bold">Working Hours:</span> __________</p>
<p><span class="bold">Weekly Off:</span> Rotational.</p>
<p>However, depending upon client requirements and operational exigencies, the Company may revise:</p>
<ul>
<li>reporting time;</li>
<li>shift timings;</li>
<li>weekly off;</li>
<li>working schedule;</li>
<li>break timings; and</li>
<li>process allocation.</li>
</ul>
<p>Employees shall strictly adhere to the attendance and shift policies issued by the Company.</p>
<p class="section">10. SHIFT WORK</p>
<p>You acknowledge that AIRVINCIBLE operates on a <span class="bold">24×7×365 business model</span>.</p>
<p>Accordingly, you may be required to work in:</p>
<ul>
<li>day shifts;</li>
<li>evening shifts;</li>
<li>night shifts;</li>
<li>split shifts;</li>
<li>rotational shifts;</li>
<li>weekend shifts;</li>
<li>public holidays; and</li>
<li>emergency business operations.</li>
</ul>
<p>Shift allocation shall remain at the sole discretion of the Company.</p>
<p>Failure to report for assigned shifts without approval may constitute misconduct.</p>
<p class="section">11. TRAINING</p>
<p>You shall undergo mandatory induction, compliance training, process training, client certification, information security training and quality assessment programmes as prescribed by the Company.</p>
<p>Successful completion of such training shall be an essential condition of your continued employment.</p>
<p>The Company may require retraining or reassessment whenever considered necessary.</p>
<p>Failure to complete mandatory training or certification may result in extension of probation, reassignment or disciplinary action, subject to applicable law.</p>
<p class="section">12. POLICY COMPLIANCE</p>
<p>You agree to comply with all Company policies, including but not limited to:</p>
<ul>
<li>Human Resources Policy;</li>
<li>Employee Handbook;</li>
<li>Leave &amp; Attendance Policy;</li>
<li>Code of Conduct;</li>
<li>Zero Tolerance Policy;</li>
<li>POSH Policy;</li>
<li>Information Security Policy;</li>
<li>Confidentiality &amp; NDA;</li>
<li>Data Protection Policy;</li>
<li>IT Acceptable Use Policy;</li>
<li>Social Media Policy;</li>
<li>Performance Management Policy;</li>
<li>Disciplinary Policy; and</li>
<li>any future policy issued by the Company.</li>
</ul>
<p>Violation of any Company policy may result in disciplinary action, including termination where justified and after following applicable legal procedures.</p>
<p class="section">13. COMPANY'S RIGHT TO MODIFY DUTIES</p>
<p>The Company may, in accordance with business requirements, assign additional responsibilities, modify work allocation, transfer processes, introduce new technologies, implement automation or revise operational procedures.</p>
<p>You agree to cooperate with such changes and undertake reasonable training required for effective performance of your duties.</p>
<p class="section">14. COMPENSATION</p>
<p>Your compensation shall be as communicated in <span class="bold">Annexure–A (Compensation Structure)</span> attached to this Appointment Letter.</p>
<p>The compensation package shall consist of:</p>
<ul>
<li>Basic Salary;</li>
<li>House Rent Allowance (where applicable);</li>
<li>Special Allowance;</li>
<li>Performance Incentive;</li>
<li>Attendance Incentive;</li>
<li>Shift Allowance (where applicable);</li>
<li>Variable Pay (if applicable);</li>
<li>Other Allowances as determined by the Company.</li>
</ul>
<p>The salary structure may be revised from time to time based on Company policy, performance, statutory amendments and business requirements.</p>
<p>Nothing contained herein shall create any automatic right to salary revision.</p>
<p class="section">15. MODE OF SALARY PAYMENT</p>
<p>Salary shall ordinarily be paid on or before the date determined by the Company through electronic transfer into the Employee's designated bank account.</p>
<p>Salary shall be subject to:</p>
<ul>
<li>attendance verification;</li>
<li>payroll processing;</li>
<li>statutory deductions;</li>
<li>authorised deductions;</li>
<li>recovery of advances, if any; and</li>
<li>applicable tax deductions.</li>
</ul>
<p>Delay caused by banking systems, statutory compliance or circumstances beyond the Company's reasonable control shall not constitute default.</p>
<p class="section">16. STATUTORY DEDUCTIONS</p>
<p>The Company shall make deductions from salary as required under applicable law, including but not limited to:</p>
<ul>
<li>Income Tax (TDS);</li>
<li>Employees' Provident Fund (EPF), where applicable;</li>
<li>Employees' State Insurance (ESI), where applicable;</li>
<li>Professional Tax, wherever applicable;</li>
<li>Labour Welfare Fund, wherever applicable; and</li>
<li>any other statutory deduction required by law.</li>
</ul>
<p>The Employee authorises the Company to make all lawful deductions from salary.</p>
<p class="section">17. VARIABLE PAY &amp; INCENTIVES</p>
<p>Performance incentives, attendance incentives, client incentives, sales incentives, quality bonuses or any other variable payments shall be entirely discretionary unless specifically provided under a separate written incentive policy.</p>
<p>Such incentives shall depend upon:</p>
<ul>
<li>achievement of targets;</li>
<li>attendance;</li>
<li>quality scores;</li>
<li>client satisfaction;</li>
<li>compliance score;</li>
<li>productivity;</li>
<li>business profitability; and</li>
<li>Company policy.</li>
</ul>
<p>Variable pay shall not form part of the Employee's fixed salary and may be modified, suspended or discontinued by the Company.</p>
<p class="section">18. PERFORMANCE STANDARDS</p>
<p>The Employee shall perform duties with honesty, diligence and professionalism.</p>
<p>Performance shall be evaluated on the basis of:</p>
<p>(a) Key Performance Indicators (KPIs);</p>
<p>(b) Key Result Areas (KRAs);</p>
<p>(c) productivity;</p>
<p>(d) attendance;</p>
<p>(e) punctuality;</p>
<p>(f) quality score;</p>
<p>(g) customer satisfaction;</p>
<p>(h) call quality;</p>
<p>(i) compliance score;</p>
<p>(j) behavioural standards;</p>
<p>(k) teamwork;</p>
<p>(l) policy adherence; and</p>
<p>(m) any other performance metric introduced by the Company.</p>
<p>Failure to achieve minimum performance expectations may result in counselling, retraining, a Performance Improvement Plan (PIP) or disciplinary action.</p>
<p class="section"><span class="bold">19. ATTENDANCE, WORKING HOURS &amp; PUNCTUALITY</span></p>
<p>Attendance and punctuality are fundamental conditions of employment. Every Employee is required to maintain regular attendance and comply with the Company's prescribed working hours and attendance procedures.</p>
<p>Every Employee shall:</p>
<ul>
<li>Report to work on time as per the designated shift or working schedule.</li>
<li>Complete the prescribed <span class="bold">L1 Login Hours</span> on the Company's official work systems/applications each working day.</li>
<li>Mark attendance through the Company's designated <span class="bold">L2 Biometric Attendance System</span> immediately upon reporting to work and while leaving the workplace, wherever applicable.</li>
<li>Adhere to assigned working hours, shift timings, meal breaks and rest intervals.</li>
<li>Obtain prior approval from the Reporting Manager/HR Department before availing any leave or permission, except in emergencies.</li>
<li>Comply with the Company's Leave &amp; Attendance Policy, Working Hours Policy and all attendance-related guidelines.</li>
<li>Ensure that attendance records, login records and biometric records accurately reflect the actual time worked.</li>
<li>Promptly report any biometric malfunction or technical issue affecting attendance recording to HR on the same day.</li>
</ul>
<p class="subsection"><span class="bold">19.1 L1 Login Hours</span></p>
<ol>
<li>Employees shall complete a minimum of <span class="bold">9 (Nine) hours</span> of official login time per working day or such duration as may be prescribed by the Company from time to time.</li>
<li>Login hours shall be recorded through the Company's authorized systems, software or applications.</li>
<li>Login time shall not include unauthorized idle time, prolonged inactivity or non-work-related usage.</li>
<li>Failure to complete the prescribed login hours without prior approval may result in attendance regularization, deduction of salary, disciplinary action or other corrective measures as determined by the Company.</li>
</ol>
<p class="subsection"><span class="bold">19.2 L2 Biometric Attendance</span></p>
<ol>
<li>Every Employee shall mark attendance using the Company's biometric attendance device at the beginning and end of each working day.</li>
<li>Biometric attendance shall be treated as the primary proof of physical presence at the workplace.</li>
<li>Employees shall not mark attendance on behalf of another employee or permit another person to mark attendance for them.</li>
<li>Any attempt to manipulate, bypass or misuse the biometric attendance system shall constitute serious misconduct and may result in disciplinary action, including termination of employment.</li>
</ol>
<p class="subsection"><span class="bold">19.3 Late Reporting &amp; Early Departure</span></p>
<ul>
<li>Employees are expected to report to work on or before the scheduled reporting time.</li>
<li>Habitual late reporting, early departure, unauthorized absence or failure to complete prescribed login hours may result in salary deductions, loss of attendance benefits, issuance of warning letters or disciplinary proceedings.</li>
<li>Repeated violations may be treated as misconduct under the Company's Code of Conduct.</li>
</ul>
<p class="subsection"><span class="bold">19.4 Attendance Manipulation</span></p>
<p>Any act of attendance fraud, including but not limited to:</p>
<ul>
<li>Buddy punching or proxy biometric attendance;</li>
<li>Manipulation of login/logout timings;</li>
<li>Tampering with attendance records;</li>
<li>Submission of false attendance information; or</li>
<li>Misuse of attendance systems,</li>
</ul>
<p>shall constitute <span class="bold">gross misconduct</span> and may attract disciplinary action, including suspension, recovery of losses, or termination of employment.</p>
<p class="section">20. LEAVE ENTITLEMENT</p>
<p>Leave shall be governed exclusively by the Company's <span class="bold">Leave &amp; Attendance Policy</span>, as amended from time to time.</p>
<p>No Employee shall absent himself/herself from work without obtaining prior approval except in genuine emergencies.</p>
<p>Leave shall always remain subject to operational requirements and approval by the competent authority.</p>
<p class="section">21. BENEFITS</p>
<p>Subject to eligibility and Company policy, the Employee may be entitled to benefits such as:</p>
<ul>
<li>Provident Fund (where applicable);</li>
<li>ESI (where applicable);</li>
<li>statutory leave benefits;</li>
<li>incentive schemes;</li>
<li>employee welfare programmes;</li>
<li>training programmes;</li>
<li>career development opportunities; and</li>
<li>any other benefit introduced by the Company.</li>
</ul>
<p>The Company reserves the right to modify or withdraw any non-statutory benefit at its discretion.</p>
<p class="section">22. CONFIDENTIALITY</p>
<p>The Employee shall maintain strict confidentiality regarding all Company, client and customer information.</p>
<p>The Employee shall execute and remain bound by the <span class="bold">Employee Non-Disclosure, Confidentiality, Data Protection &amp; Intellectual Property Agreement</span>, which shall form an integral part of this Appointment Letter.</p>
<p>Confidentiality obligations shall continue even after cessation of employment to the extent provided in the NDA and applicable law.</p>
<p class="section">23. INFORMATION SECURITY</p>
<p>The Employee shall comply with all Information Security Policies issued by the Company.</p>
<p>Without prior written authorisation, the Employee shall not:</p>
<ul>
<li>disclose confidential information;</li>
<li>copy Company data;</li>
<li>share customer information;</li>
<li>download Company databases;</li>
<li>use unauthorised storage devices;</li>
<li>transmit confidential information through personal email or messaging applications; or</li>
<li>bypass Company security measures.</li>
</ul>
<p>Any information security violation shall constitute serious misconduct.</p>
<p class="section">24. COMPANY PROPERTY</p>
<p>All property provided by the Company shall remain the exclusive property of the Company.</p>
<p>Company Property includes, without limitation:</p>
<ul>
<li>laptops;</li>
<li>desktops;</li>
<li>mobile devices;</li>
<li>headsets;</li>
<li>identity cards;</li>
<li>access cards;</li>
<li>SIM cards;</li>
<li>documents;</li>
<li>manuals;</li>
<li>confidential records;</li>
<li>software;</li>
<li>passwords;</li>
<li>source code;</li>
<li>databases;</li>
<li>CRM access;</li>
<li>official email accounts; and</li>
<li>all other assets issued by the Company.</li>
</ul>
<p>The Employee shall exercise reasonable care in the use of Company Property and shall return all such property immediately upon demand or upon cessation of employment.</p>
<p class="section">25. USE OF COMPANY SYSTEMS</p>
<p>The Employee shall use Company systems solely for authorised business purposes.</p>
<p>The Employee shall not:</p>
<p>(a) install unauthorised software;</p>
<p>(b) disable antivirus or security controls;</p>
<p>(c) access prohibited websites;</p>
<p>(d) download pirated software;</p>
<p>(e) share login credentials;</p>
<p>(f) access another Employee's account;</p>
<p>(g) connect unauthorised devices; or</p>
<p>(h) misuse Company internet facilities.</p>
<p>The Company may monitor all usage of Company systems to the extent permitted by applicable law.</p>
<p class="section">26. INTELLECTUAL PROPERTY</p>
<p>Any invention, software, process, document, design, report, database, presentation, work product or other intellectual property created by the Employee during the course of employment and relating to the Company's business shall belong exclusively to the Company, subject to applicable law.</p>
<p>The Employee agrees to execute all documents reasonably required to perfect or protect such rights.</p>
<p class="section">27. CONFLICT OF INTEREST</p>
<p>The Employee shall immediately disclose any actual, potential or perceived conflict of interest.</p>
<p>Without prior written approval, the Employee shall not:</p>
<ul>
<li>engage in competing business;</li>
<li>accept employment with another organisation during working hours;</li>
<li>undertake activities prejudicial to the Company's interests;</li>
<li>misuse Company resources for personal gain; or</li>
<li>participate in any transaction creating a conflict between personal interests and Company interests.</li>
</ul>
<p class="section">28. CODE OF CONDUCT</p>
<p>The Employee shall comply with the Company's Code of Conduct at all times.</p>
<p>The Employee shall maintain:</p>
<ul>
<li>professionalism;</li>
<li>honesty;</li>
<li>integrity;</li>
<li>respect for colleagues;</li>
<li>compliance with client requirements;</li>
<li>ethical conduct;</li>
<li>non-discrimination;</li>
<li>anti-harassment standards; and</li>
<li>all behavioural expectations prescribed by Company policy.</li>
</ul>
<p>Any violation may result in disciplinary action.</p>
<p class="section">29. POLICY ACKNOWLEDGEMENT</p>
<p>The Employee acknowledges having received or been provided access to the Company's policies, including:</p>
<ul>
<li>Human Resources Policy;</li>
<li>Employee Handbook;</li>
<li>Leave &amp; Attendance Policy;</li>
<li>Code of Conduct;</li>
<li>POSH Policy;</li>
<li>Zero Tolerance Policy;</li>
<li>Information Security Policy;</li>
<li>NDA;</li>
<li>Data Protection Policy;</li>
<li>IT Acceptable Use Policy; and</li>
<li>any future policies issued by the Company.</li>
</ul>
<p>The Employee agrees to comply with all such policies throughout the period of employment.</p>
<p class="section">30. BACKGROUND VERIFICATION</p>
<p>Your employment shall remain subject to satisfactory background verification conducted by the Company or its authorized verification agency.</p>
<p>The Company reserves the right to verify:</p>
<ul>
<li>Identity;</li>
<li>Aadhaar;</li>
<li>PAN;</li>
<li>Address;</li>
<li>Educational qualifications;</li>
<li>Professional qualifications;</li>
<li>Previous employment;</li>
<li>Experience certificates;</li>
<li>Criminal records, where legally permissible;</li>
<li>Reference checks;</li>
<li>Social and professional credentials relevant to employment; and</li>
<li>Any other information necessary for employment verification.</li>
</ul>
<p>If, at any stage, any information, declaration or document submitted by the Employee is found to be false, misleading, fabricated, suppressed or materially inaccurate, the Company may:</p>
<p>(a) immediately terminate employment after following applicable legal procedures;</p>
<p>(b) recover losses, damages or costs suffered by the Company to the extent permitted by law;</p>
<p>(c) initiate disciplinary proceedings; and</p>
<p>(d) pursue appropriate civil or criminal remedies where applicable.</p>
<p class="section">31. DISCIPLINARY ACTION</p>
<p>Every Employee shall comply with all Company policies, lawful instructions and professional standards.</p>
<p>Without prejudice to any other remedy available under law, misconduct may result in one or more of the following actions depending upon the gravity of the misconduct:</p>
<ul>
<li>Verbal Counselling;</li>
<li>Written Warning;</li>
<li>Final Written Warning;</li>
<li>Performance Improvement Plan (PIP);</li>
<li>Suspension pending inquiry;</li>
<li>Recovery of losses where legally permissible;</li>
<li>Withdrawal of incentives or privileges;</li>
<li>Demotion or reassignment, where appropriate;</li>
<li>Termination of employment after due process; and</li>
<li>Civil and/or criminal proceedings where warranted.</li>
</ul>
<p>Disciplinary proceedings shall be conducted in accordance with the principles of natural justice and applicable law.</p>
<p class="section">32. SUSPENSION PENDING INQUIRY</p>
<p>Where serious allegations of misconduct, fraud, confidentiality breach, harassment, data theft, financial irregularity, cyber misconduct or any other grave misconduct arise, the Company may place the Employee under suspension pending completion of an internal disciplinary inquiry.</p>
<p>During suspension:</p>
<p>(a) the Employee shall cooperate fully with the inquiry;</p>
<p>(b) the Employee shall not interfere with witnesses or evidence;</p>
<p>(c) the Employee shall surrender Company property if directed;</p>
<p>(d) subsistence allowance, where legally required, shall be governed by applicable law; and</p>
<p>(e) suspension shall not be construed as a finding of guilt.</p>
<p class="section">33. NOTICE PERIOD</p>
<p>Upon successful completion of probation, either party may terminate the employment by providing <span class="bold">Thirty (30) days' prior written notice</span> or salary in lieu of notice, subject to applicable law and the terms of this Appointment Letter.</p>
<p>During probation, the notice period shall ordinarily be <span class="bold">Seven (7) days</span>, unless otherwise specified by applicable law or Company policy.</p>
<p>The Company reserves the right to:</p>
<ul>
<li>waive the notice period;</li>
<li>require the Employee to serve the full notice period;</li>
<li>relieve the Employee immediately by paying salary in lieu of notice, where applicable;</li>
<li>place the Employee on Garden Leave; or</li>
<li>adjust notice period in accordance with applicable law and Company policy.</li>
</ul>
<p class="section">34. GARDEN LEAVE</p>
<p>During the notice period, the Company may, at its sole discretion, direct the Employee not to attend the workplace while remaining on the Company's rolls for all or part of the notice period.</p>
<p>During Garden Leave, the Employee shall:</p>
<ul>
<li>remain available for official communication;</li>
<li>maintain confidentiality;</li>
<li>not engage in competing employment;</li>
<li>complete handover obligations as directed; and</li>
<li>comply with all terms of employment.</li>
</ul>
<p class="section">35. TERMINATION OF EMPLOYMENT</p>
<p>The Company may terminate employment, after following applicable legal procedures, on grounds including but not limited to:</p>
<ul>
<li>serious misconduct;</li>
<li>fraud or dishonesty;</li>
<li>falsification of records;</li>
<li>data theft or confidentiality breach;</li>
<li>repeated policy violations;</li>
<li>habitual absenteeism;</li>
<li>unsatisfactory performance despite reasonable opportunity for improvement;</li>
<li>insubordination;</li>
<li>workplace violence;</li>
<li>harassment;</li>
<li>conflict of interest;</li>
<li>conviction for an offence involving moral turpitude, where relevant;</li>
<li>loss of client confidence affecting role suitability;</li>
<li>operational restructuring, redundancy or business closure, subject to applicable law; or</li>
<li>any other lawful ground.</li>
</ul>
<p>Termination shall not prejudice the Company's right to pursue any legal remedy available under law.</p>
<p class="section">36. EXIT FORMALITIES (HRMS)</p>
<p>Before separation from employment, the Employee shall:</p>
<p>(a) complete handover of all assigned work;</p>
<p>(b) return all Company property;</p>
<p>(c) surrender ID cards, access cards and security credentials;</p>
<p>(d) return laptops, mobile devices, headsets and other equipment;</p>
<p>(e) return confidential documents and data;</p>
<p>(f) provide passwords or access credentials where authorised by Company policy;</p>
<p>(g) complete clearance from all concerned departments; and</p>
<p>(h) comply with the Company's exit procedures.</p>
<p>Failure to complete exit formalities may delay issuance of relieving documents or Full &amp; Final Settlement to the extent permitted by law.</p>
<p class="section">37. FULL &amp; FINAL SETTLEMENT</p>
<p>Subject to completion of exit formalities and recovery of lawful dues, the Company shall process the Employee's Full &amp; Final Settlement in accordance with applicable law and Company policy.</p>
<p>The Company may make lawful adjustments towards:</p>
<ul>
<li>notice pay recovery;</li>
<li>salary advances;</li>
<li>loans;</li>
<li>Company property not returned;</li>
<li>authorised deductions;</li>
<li>statutory deductions; and</li>
<li>any other recoverable amount legally payable by the Employee.</li>
</ul>
<p class="section">38. RETURN OF COMPANY PROPERTY</p>
<p>Upon cessation of employment or whenever directed, the Employee shall immediately return all Company property, including physical and digital assets.</p>
<p>The Employee shall not retain, copy, delete, destroy, transfer or use any Company information after cessation of employment.</p>
<p>The Company reserves the right to recover the reasonable replacement cost of Company property that is not returned, where legally permissible.</p>
<p class="section"><span class="bold">39. POST-EMPLOYMENT OBLIGATIONS</span></p>
<p>Upon cessation of employment for any reason, whether by resignation, retirement, termination, abandonment of service or expiry of contract, the Employee shall continue to remain bound by the obligations that survive the employment relationship, to the extent permissible under applicable law.</p>
<p>The Employee shall remain responsible for:</p>
<ul>
<li>maintaining the confidentiality of all Confidential Information obtained during employment;</li>
<li>protecting the Company's intellectual property, proprietary information, trade secrets and business know-how;</li>
<li>complying with all data protection, privacy and information security obligations;</li>
<li>safeguarding customer, employee, vendor and business data from unauthorized use or disclosure;</li>
<li>maintaining the confidentiality and integrity of all databases, <span class="bold">Database Management Systems (DBMS)</span>, servers, cloud platforms, applications, source codes, APIs, repositories and digital infrastructure accessed during employment;</li>
<li>refraining from copying, downloading, extracting, transferring, retaining, publishing or using any data stored in the Company's DBMS, CRM, ERP, HRMS, accounting software or any other digital platform after cessation of employment;</li>
<li>immediately surrendering all user IDs, passwords, authentication devices, access credentials, digital certificates, encryption keys, security tokens and administrative privileges;</li>
<li>returning all Company property including laptops, mobile phones, identity cards, access cards, documents, storage devices, files, software licences and any other physical or electronic assets belonging to the Company;</li>
<li>deleting all Company information stored on personal devices, cloud storage, email accounts or external media and providing confirmation of such deletion, if required by the Company;</li>
<li>complying with all non-disclosure, confidentiality, non-solicitation and other surviving contractual obligations; and</li>
<li>refraining from any unauthorized use, disclosure, modification, deletion or destruction of Company information, databases or digital assets.</li>
</ul>
<p>The Company reserves the right to disable, suspend or revoke all physical and electronic access immediately upon cessation of employment and to conduct audits to ensure the return of Company property and protection of Company information.</p>
<p>The obligations contained in this Clause shall survive the termination of employment and shall remain enforceable for so long as permitted under applicable law, irrespective of the reason for cessation of employment.</p>
<p class="section">40. NON-SOLICITATION</p>
<p>For a period specified in the Employee NDA or any separate agreement, and to the extent enforceable under applicable law, the Employee shall not knowingly solicit:</p>
<ul>
<li>the Company's employees for competing employment;</li>
<li>the Company's clients for competing services; or</li>
<li>confidential business opportunities obtained solely through employment with the Company.</li>
</ul>
<p>Nothing in this clause shall restrain lawful employment or create any restriction prohibited under Section 27 of the Indian Contract Act, 1872.</p>
<p class="section">41. DATA PRIVACY &amp; CONSENT</p>
<p>The Employee acknowledges and consents that the Company may collect, process, store and use personal information for legitimate employment-related purposes, including payroll, statutory compliance, attendance, security, benefits administration, performance management and legal obligations, in accordance with applicable data protection laws and Company policies.</p>
<p class="section">42. AMENDMENT OF TERMS</p>
<p>The Company reserves the right to amend its policies, procedures, operational guidelines and employment practices from time to time.</p>
<p>Any statutory amendment or policy revision shall become applicable from the effective date notified by the Company.</p>
<p class="section">43. SEVERABILITY</p>
<p>If any provision of this Appointment Letter is held to be invalid or unenforceable by a court of competent jurisdiction, the remaining provisions shall continue in full force and effect.</p>
<p class="section">44. GOVERNING LAW &amp; JURISDICTION</p>
<p>This Appointment Letter shall be governed by and construed in accordance with the laws of India.</p>
<p>Subject to applicable law, the courts and competent authorities at <span class="bold">Indore, Madhya Pradesh</span>, shall have exclusive jurisdiction over disputes arising out of or in connection with this Appointment Letter.</p>
<p class="section">45. ENTIRE AGREEMENT</p>
<p>This Appointment Letter, together with the Employee NDA, Human Resources Policy, Employee Handbook, Leave &amp; Attendance Policy, Code of Conduct, Zero Tolerance Policy, POSH Policy, Information Security Policy and other Company policies, constitutes the entire understanding between the Company and the Employee relating to employment and supersedes all prior discussions or representations relating to the appointment.</p>
<p class="section">46. EMPLOYEE DECLARATION</p>
<p>I hereby declare that:</p>
<ul>
<li>I have carefully read and understood this Appointment Letter.</li>
<li>The information furnished by me during recruitment is true and complete.</li>
<li>I shall faithfully discharge my duties and comply with all Company policies.</li>
<li>I understand that breach of Company policies may result in disciplinary action, including termination after due process.</li>
<li>I shall maintain confidentiality during and after my employment.</li>
<li>I shall promptly return all Company property upon demand or cessation of employment.</li>
<li>I voluntarily accept the terms and conditions of this Appointment Letter.</li>
</ul>
<div class="nobreak">
<p class="section">ACCEPTANCE</p>
<p>I hereby accept the appointment on the terms and conditions contained in this Appointment Letter and agree to abide by all Company policies as amended from time to time.</p>
<p class="section"><span class="bold">FOR AIRVINCIBLE</span></p>
<p><span class="bold">Authorized Signatory:</span> ___________________________</p>
<p><span class="bold">Name:</span> {{ $company['hr_name'] }}</p>
<p><span class="bold">Designation:</span> {{ $company['hr_designation'] }}</p>
<p><span class="bold">Signature:</span> @if($company['signature_path'])<img src="{{ $company['signature_path'] }}" class="signature-image" alt="Signature">@else ___________________________ @endif</p>
<p><span class="bold">Date:</span> {{ $today }}</p>
<p class="section"><span class="bold">EMPLOYEE</span></p>
<p><span class="bold">Name:</span> {{ $name }}</p>
<p><span class="bold">Employee ID:</span> ___________________________</p>
<p><span class="bold">Signature:</span> ___________________________</p>
<p><span class="bold">Date:</span> ___________________________</p>
<p class="section"><span class="bold">WITNESS – 1</span></p>
<p><span class="bold">Name:</span> ___________________________</p>
<p><span class="bold">Signature:</span> ___________________________</p>
<p class="section"><span class="bold">WITNESS – 2</span></p>
<p><span class="bold">Name:</span> ___________________________</p>
<p><span class="bold">Signature:</span> ___________________________</p>
</div>

    <div class="annexure">
        <p class="front-h1">ANNEXURE – A</p>
        <p class="front-h3">(Compensation Structure)</p>
        <p>Referred to in Clause 14 (Compensation) of this Appointment Letter. This salary break-up is confidential and should not be disclosed to anyone.</p>

        <table class="salary-table">
            <thead>
                <tr>
                    <th style="width: 44%;">Particulars</th>
                    <th style="width: 28%;">Monthly Amount in INR</th>
                    <th style="width: 28%;">Annual Amount in INR</th>
                </tr>
            </thead>
            <tbody>
                @foreach($earnings as $component)
                    <tr>
                        <td>{{ $component->component_name }}</td>
                        <td class="num">{{ $inr($monthly($component)) === '-' ? '' : $inr($monthly($component)) }}</td>
                        <td class="num">{{ $inr($annual($component)) }}</td>
                    </tr>
                @endforeach
                <tr class="total">
                    <td>Gross Total (A)</td>
                    <td class="num">{{ $inr($gross['m']) }}</td>
                    <td class="num">{{ $inr($gross['a']) }}</td>
                </tr>

                @foreach($deductions as $component)
                    <tr>
                        <td>{{ $component->component_name }}</td>
                        <td class="num">{{ $inr($monthly($component)) }}</td>
                        <td class="num">{{ $inr($annual($component)) }}</td>
                    </tr>
                @endforeach
                <tr class="total">
                    <td>Take Home</td>
                    <td class="num">{{ $inr($takeHome['m']) }}</td>
                    <td class="num">{{ $inr($takeHome['a']) }}</td>
                </tr>

                @if($retirals->isNotEmpty())
                    <tr class="group"><td colspan="3">Retirals</td></tr>
                    @foreach($retirals as $component)
                        <tr>
                            <td>{{ $component->component_name }}</td>
                            <td class="num">{{ $inr($monthly($component)) }}</td>
                            <td class="num">{{ $inr($annual($component)) }}</td>
                        </tr>
                    @endforeach
                    <tr class="total">
                        <td>Total Retirals (B)</td>
                        <td class="num">{{ $inr($retiralTotal['m']) }}</td>
                        <td class="num">{{ $inr($retiralTotal['a']) }}</td>
                    </tr>
                @endif

                <tr class="total">
                    <td>Total CTC</td>
                    <td class="num">{{ $inr($ctc['m']) }}</td>
                    <td class="num">{{ $inr($ctc['a']) }}</td>
                </tr>
            </tbody>
        </table>

        @if($offer->salary_annexure)
            <p style="white-space: pre-line; font-size: 10.5px;">{!! $offer->salary_annexure !!}</p>
        @endif

        @if($offer->offer_terms)
            <p class="section">Additional Terms</p>
            <p style="white-space: pre-line;">{{ $offer->offer_terms }}</p>
        @endif
    </div>
</body>
</html>
