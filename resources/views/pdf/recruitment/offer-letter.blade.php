@php
    use App\Support\LetterHelper;

    $name = $candidate->full_name
        ?? trim(($candidate->first_name ?? '') . ' ' . ($candidate->last_name ?? ''));

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

    $location = $offer->work_location
        ?: trim(implode(', ', array_filter([$offer->branch?->name, $offer->branch?->address, $offer->branch?->city])))
        ?: ($company['address'] ?? '');

    $inr = fn ($v) => LetterHelper::inr($v);
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Offer Letter - {{ $offer->offer_number }}</title>
    @include('pdf.recruitment.partials.letter-styles')
    <style>
        .salary-table { width: 100%; border-collapse: collapse; margin: 8px 0 6px; page-break-inside: avoid; }
        .salary-table th, .salary-table td { border: 1px solid #000; padding: 1px 5px; font-size: 11px; line-height: 1.35; vertical-align: top; }
        .salary-table th { text-align: left; font-weight: bold; }
        .salary-table .num { text-align: right; }
        .salary-table .total td { font-weight: bold; }
        .salary-table .group td { font-weight: bold; }
        .policy-title { margin: 12px 0 2px; font-weight: bold; page-break-after: avoid; }
        .policies p { text-align: left; }
        .signoff { margin-top: 22px; margin-left: 300px; page-break-inside: avoid; }
        .signoff img { height: 48px; margin: 4px 0; }
    </style>
</head>
<body>
    @include('pdf.recruitment.partials.letter-chrome')

    @if(in_array($offer->status, ['draft', 'pending_approval'], true))
        <div class="watermark">DRAFT</div>
    @endif

    <p class="bold">Offer Letter,</p>

    <p style="margin-bottom: 2px;"><span class="bold">Date:</span> {{ now()->format('l, F j, Y') }}</p>
    <p class="bold" style="margin-bottom: 2px;">To,</p>
    <p class="bold">{{ $name }}</p>

    <p style="margin-top: 14px;">Subject: <span class="bold">Offer of Employment</span></p>

    <p>Dear <span class="bold">{{ $name }}</span>,</p>

    <p>Following our discussion, we are pleased to offer you the position of {{ $offer->designation }} at <span class="bold">{{ $company['name'] }}</span>. The broad terms and conditions of your employment are outlined below:</p>

    <p class="section">1. Joining Details</p>
    <ul>
        <li>Your employment shall commence from the date of your reporting at our office, located at:<br><span class="bold">{{ $location }}</span></li>
        <li>Your salary break-up is mentioned below, which is confidential and should not be disclosed to anyone.</li>
    </ul>

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

    <ul>
        <li>Your <span class="bold">Date of Joining (DOJ)</span> is <span class="bold">{{ optional($offer->joining_date)->format('l, F j, Y') ?? '—' }}</span></li>
    </ul>

    <p class="section">2. Documents Required on DOJ</p>
    <p style="margin-bottom: 2px;">You are requested to submit the following documents on your joining date:</p>
    <ul>
        <li>KYC: Aadhaar Card, PAN Card, and two passport-size photographs</li>
        <li>Education: 10th, 12th, and Graduation mark sheets</li>
        <li>Experience: Experience letter, last drawn salary proof, and offer letter from the previous organization</li>
        <li>Bank Details: Cancelled cheque or bank statement</li>
        <li>Salary Slips: Last 3 months from previous organization</li>
    </ul>

    <p class="section">3. General Terms</p>
    @if($offer->offer_terms)
        <p style="white-space: pre-line;">{{ $offer->offer_terms }}</p>
    @else
        <ul>
            <li>You shall be deemed an employee of {{ $company['name'] }} from your DOJ.</li>
            <li>This offer is based on the information provided by you. Any discrepancy found during background verification may result in withdrawal of the offer or termination without notice.</li>
            <li>If failed to join on DOJ offer letter will be forfeited.</li>
        </ul>
    @endif

    @include('pdf.recruitment.partials.hr-policies')

    <div class="nobreak">
        <p class="section">Employee Declaration</p>
        <p>I, {{ $name }}, hereby declare that I have carefully read, understood, and agree to comply with all the above-mentioned HR Policies of the organization.</p>
        <p style="margin-bottom: 2px;">Employee Name: {{ $name }}</p>
        <p>Employee Signature: ____________________</p>

        <div class="signoff">
            <p style="margin-bottom: 2px;">Warm Regards,</p>
            <p class="bold" style="margin-bottom: 0;">{{ $company['hr_name'] }}</p>
            @if($company['signature_path'])
                <img src="{{ $company['signature_path'] }}" alt="Signature"><br>
            @else
                <div style="height: 44px;"></div>
            @endif
            <p class="bold" style="margin-bottom: 0;">{{ $company['hr_designation'] }}<br>{{ $company['name'] }}</p>
        </div>
    </div>
</body>
</html>
