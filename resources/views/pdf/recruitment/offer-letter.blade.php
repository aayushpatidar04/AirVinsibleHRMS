<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">

    <title>
        Offer Letter -
        {{ $offer->offer_number }}
    </title>

    <style>
        @page {
            margin: 115px 42px 75px 42px;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: DejaVu Sans, sans-serif;
            font-size: 10.5px;
            line-height: 1.55;
            color: #1f2937;
        }

        .header {
            position: fixed;
            top: -88px;
            left: 0;
            right: 0;
            height: 78px;
            border-bottom: 1px solid #d1d5db;
        }

        .header-table {
            width: 100%;
            border-collapse: collapse;
        }

        .header-table td {
            vertical-align: middle;
        }

        .logo {
            max-width: 150px;
            max-height: 52px;
        }

        .company-name {
            font-size: 16px;
            font-weight: bold;
            color: #111827;
            text-align: right;
        }

        .company-details {
            margin-top: 3px;
            font-size: 8px;
            line-height: 1.35;
            color: #6b7280;
            text-align: right;
        }

        .footer {
            position: fixed;
            bottom: -52px;
            left: 0;
            right: 0;
            height: 42px;
            border-top: 1px solid #d1d5db;
            color: #6b7280;
            font-size: 8px;
        }

        .footer-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 8px;
        }

        .footer-table td:last-child {
            text-align: right;
        }

        .page-number:after {
            content: counter(page);
        }

        .watermark {
            position: fixed;
            top: 40%;
            left: 12%;
            width: 76%;
            text-align: center;
            transform: rotate(-35deg);
            font-size: 72px;
            font-weight: bold;
            letter-spacing: 8px;
            color: rgba(107, 114, 128, 0.09);
            z-index: -1000;
        }

        .document-title {
            margin: 4px 0 4px;
            text-align: center;
            font-size: 21px;
            font-weight: bold;
            color: #111827;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .document-subtitle {
            margin-bottom: 24px;
            text-align: center;
            font-size: 9px;
            color: #6b7280;
        }

        .meta-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 22px;
        }

        .meta-table td {
            padding: 3px 0;
            vertical-align: top;
        }

        .meta-label {
            width: 75px;
            font-weight: bold;
            color: #374151;
        }

        .meta-right-label {
            width: 80px;
            font-weight: bold;
            color: #374151;
        }

        .subject {
            margin: 18px 0;
            font-weight: bold;
            color: #111827;
        }

        p {
            margin: 0 0 11px;
            text-align: justify;
        }

        .section-title {
            margin: 22px 0 10px;
            padding: 7px 10px;
            border-left: 4px solid #4f46e5;
            background: #eef2ff;
            color: #312e81;
            font-size: 12px;
            font-weight: bold;
            page-break-after: avoid;
        }

        .details-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
        }

        .details-table td {
            width: 50%;
            padding: 7px 9px;
            border: 1px solid #d1d5db;
            vertical-align: top;
        }

        .field-label {
            display: block;
            margin-bottom: 2px;
            font-size: 8px;
            font-weight: bold;
            color: #6b7280;
            text-transform: uppercase;
        }

        .field-value {
            font-size: 10px;
            font-weight: bold;
            color: #111827;
        }

        .salary-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 8px;
        }

        .salary-table thead {
            display: table-header-group;
        }

        .salary-table tr {
            page-break-inside: avoid;
        }

        .salary-table th {
            padding: 7px 6px;
            border: 1px solid #9ca3af;
            background: #f3f4f6;
            color: #374151;
            font-size: 8px;
            text-align: left;
            text-transform: uppercase;
        }

        .salary-table td {
            padding: 7px 6px;
            border: 1px solid #d1d5db;
            font-size: 9px;
        }

        .salary-table .number {
            text-align: right;
            white-space: nowrap;
        }

        .salary-table .group-row td {
            background: #f9fafb;
            font-weight: bold;
            color: #374151;
        }

        .salary-table .total-row td {
            background: #eef2ff;
            font-weight: bold;
            color: #312e81;
        }

        .salary-table .in-hand-row td {
            background: #ecfdf5;
            font-weight: bold;
            color: #065f46;
        }

        .terms {
            margin: 0;
            padding-left: 18px;
        }

        .terms li {
            margin-bottom: 7px;
            padding-left: 3px;
            text-align: justify;
        }

        .preformatted {
            white-space: pre-line;
        }

        .signature-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 35px;
            page-break-inside: avoid;
        }

        .signature-table td {
            width: 50%;
            vertical-align: bottom;
        }

        .signature-image {
            max-width: 120px;
            max-height: 48px;
            margin-bottom: 4px;
        }

        .signature-line {
            width: 190px;
            margin-top: 45px;
            border-top: 1px solid #374151;
        }

        .signature-name {
            margin-top: 5px;
            font-weight: bold;
            color: #111827;
        }

        .signature-designation {
            color: #6b7280;
            font-size: 9px;
        }

        .acceptance-box {
            margin-top: 28px;
            padding: 14px;
            border: 1px solid #9ca3af;
            page-break-inside: avoid;
        }

        .acceptance-title {
            margin-bottom: 8px;
            font-size: 12px;
            font-weight: bold;
            color: #111827;
        }

        .acceptance-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 26px;
        }

        .acceptance-table td {
            width: 50%;
            padding-right: 20px;
        }

        .blank-line {
            height: 25px;
            border-bottom: 1px solid #374151;
        }

        .blank-label {
            margin-top: 3px;
            font-size: 8px;
            color: #6b7280;
        }

        .page-break {
            page-break-before: always;
        }

        .muted {
            color: #6b7280;
        }
    </style>
</head>

<body>
    <div class="header">
        <table class="header-table">
            <tr>
                <td style="width: 40%;">
                    @if($company['logo_path'])
                        <img src="{{ $company['logo_path'] }}" class="logo" alt="Company Logo">
                    @endif
                </td>

                <td style="width: 60%;">
                    <div class="company-name">
                        {{ $company['legal_name'] }}
                    </div>

                    <div class="company-details">
                        @if($company['address'])
                            {{ $company['address'] }}<br>
                        @endif

                        @if($company['email'])
                            {{ $company['email'] }}
                        @endif

                        @if($company['phone'])
                            | {{ $company['phone'] }}
                        @endif

                        @if($company['website'])
                            <br>{{ $company['website'] }}
                        @endif
                    </div>
                </td>
            </tr>
        </table>
    </div>

    <div class="footer">
        <table class="footer-table">
            <tr>
                <td>
                    Confidential employment document
                </td>

                <td>
                    Offer {{ $offer->offer_number }}
                    · Version {{ $offer->version ?? 1 }}
                    · Page <span class="page-number"></span>
                </td>
            </tr>
        </table>
    </div>

    @if(
            in_array(
                $offer->status,
                ['draft', 'pending_approval'],
                true
            )
        )
        <div class="watermark">
            DRAFT
        </div>
    @endif

    <div class="document-title">
        Offer Letter
    </div>

    <div class="document-subtitle">
        Private and confidential
    </div>

    <table class="meta-table">
        <tr>
            <td class="meta-label">
                Date
            </td>

            <td>
                {{ now()->format('d F Y') }}
            </td>

            <td class="meta-right-label">
                Offer No.
            </td>

            <td>
                {{ $offer->offer_number }}
            </td>
        </tr>

        <tr>
            <td class="meta-label">
                Candidate
            </td>

            <td>
                {{
    $candidate->full_name
    ?? trim(
        ($candidate->first_name ?? '')
        . ' '
        . ($candidate->last_name ?? '')
    )
                }}
            </td>

            <td class="meta-right-label">
                Version
            </td>

            <td>
                {{ $offer->version ?? 1 }}
            </td>
        </tr>
    </table>

    <p>
        Dear
        <strong>
            {{
    $candidate->first_name
    ?? $candidate->full_name
    ?? 'Candidate'
            }}
        </strong>,
    </p>

    <p class="subject">
        Subject: Offer of employment for the position of
        {{ $offer->designation }}
    </p>

    <p>
        We are pleased to offer you employment with
        <strong>{{ $company['legal_name'] }}</strong>
        for the position of
        <strong>
            {{ $offer->designation }}
        </strong>.
    </p>

    <p>
        This offer is based on the discussions held with you
        during the recruitment process and is subject to the
        terms, conditions and verification requirements
        described in this letter.
    </p>

    <div class="section-title">
        Employment Details
    </div>

    <table class="details-table">
        <tr>
            <td>
                <span class="field-label">
                    Designation
                </span>

                <span class="field-value">
                    {{ $offer->designation ?? '—' }}
                </span>
            </td>

            <td>
                <span class="field-label">
                    Department
                </span>

                <span class="field-value">
                    {{ $offer->department ?? '—' }}
                </span>
            </td>
        </tr>

        <tr>
            <td>
                <span class="field-label">
                    Employment Type
                </span>

                <span class="field-value">
                    {{
    str($offer->employment_type)
        ->replace('_', ' ')
        ->title()
                    }}
                </span>
            </td>

            <td>
                <span class="field-label">
                    Branch
                </span>

                <span class="field-value">
                    {{
    $offer->branch?->name
    ?? $offer->branch
    ?? '—'
                    }}
                </span>
            </td>
        </tr>

        <tr>
            <td>
                <span class="field-label">
                    Joining Date
                </span>

                <span class="field-value">
                    {{
    optional(
        $offer->joining_date
    )->format('d F Y')
    ?? '—'
                    }}
                </span>
            </td>

            <td>
                <span class="field-label">
                    Reporting Time
                </span>

                <span class="field-value">
                    {{ $offer->reporting_time ?? '—' }}
                </span>
            </td>
        </tr>

        <tr>
            <td>
                <span class="field-label">
                    Work Location
                </span>

                <span class="field-value">
                    {{ $offer->work_location ?? '—' }}
                </span>
            </td>

            <td>
                <span class="field-label">
                    Reporting Manager
                </span>

                <span class="field-value">
                    {{
    $offer->reportingManager?->name
    ?? $offer->reporting_manager
    ?? '—'
                    }}
                </span>
            </td>
        </tr>

        <tr>
            <td>
                <span class="field-label">
                    Probation Period
                </span>

                <span class="field-value">
                    @if($offer->probation_months)
                        {{ $offer->probation_months }}
                        month{{ $offer->probation_months == 1 ? '' : 's' }}
                    @else
                        —
                    @endif
                </span>
            </td>

            <td>
                <span class="field-label">
                    Notice Period
                </span>

                <span class="field-value">
                    @if($offer->notice_period_days)
                        {{ $offer->notice_period_days }}
                        day{{ $offer->notice_period_days == 1 ? '' : 's' }}
                    @else
                        —
                    @endif
                </span>
            </td>
        </tr>
    </table>

    <div class="section-title">
        Compensation Summary
    </div>

    <table class="salary-table">
        <thead>
            <tr>
                <th style="width: 34%;">
                    Component
                </th>

                <th style="width: 16%;">
                    Type
                </th>

                <th style="width: 16%;">
                    Frequency
                </th>

                <th style="width: 17%;" class="number">
                    Monthly
                </th>

                <th style="width: 17%;" class="number">
                    Annual
                </th>
            </tr>
        </thead>

        <tbody>
            @php
                $groups = [
                    'earning' => 'Earnings',
                    'deduction' => 'Deductions',
                    'employer_contribution' =>
                        'Employer Contributions',
                ];
            @endphp

            @foreach($groups as $type => $label)
                @php
                    $items = $offer
                        ->salaryComponents
                        ->where('component_type', $type)
                        ->where('show_in_offer', true);
                @endphp

                @if($items->isNotEmpty())
                    <tr class="group-row">
                        <td colspan="5">
                            {{ $label }}
                        </td>
                    </tr>

                    @foreach($items as $component)
                        <tr>
                            <td>
                                {{ $component->component_name }}

                                @if($component->description)
                                    <br>
                                    <span class="muted">
                                        {{ $component->description }}
                                    </span>
                                @endif
                            </td>

                            <td>
                                {{
                            str(
                                $component->calculation_type
                            )
                                ->replace('_', ' ')
                                ->title()
                                            }}
                            </td>

                            <td>
                                {{
                            str($component->frequency)
                                ->replace('_', ' ')
                                ->title()
                                            }}
                            </td>

                            <td class="number">
                                ₹
                                {{
                            number_format(
                                (float) (
                                    $component->monthly_amount
                                    ?? $component->calculated_monthly_amount
                                    ?? 0
                                ),
                                2
                            )
                                            }}
                            </td>

                            <td class="number">
                                ₹
                                {{
                            number_format(
                                (float) (
                                    $component->annual_amount
                                    ?? $component->calculated_annual_amount
                                    ?? 0
                                ),
                                2
                            )
                                            }}
                            </td>
                        </tr>
                    @endforeach
                @endif
            @endforeach

            <tr class="total-row">
                <td colspan="3">
                    Monthly Gross
                </td>

                <td colspan="2" class="number">
                    ₹
                    {{
    number_format(
        $salarySummary['monthly_gross'],
        2
    )
                    }}
                </td>
            </tr>

            <tr class="total-row">
                <td colspan="3">
                    Monthly Deductions
                </td>

                <td colspan="2" class="number">
                    ₹
                    {{
    number_format(
        $salarySummary[
            'monthly_deductions'
        ],
        2
    )
                    }}
                </td>
            </tr>

            <tr class="in-hand-row">
                <td colspan="3">
                    Estimated Monthly In-Hand
                </td>

                <td colspan="2" class="number">
                    ₹
                    {{
    number_format(
        $salarySummary[
            'monthly_in_hand'
        ],
        2
    )
                    }}
                </td>
            </tr>

            <tr class="total-row">
                <td colspan="3">
                    Annual Cost to Company
                </td>

                <td colspan="2" class="number">
                    ₹
                    {{
    number_format(
        $salarySummary['annual_ctc'],
        2
    )
                    }}
                </td>
            </tr>
        </tbody>
    </table>

    @if($offer->salary_annexure)
        <div class="section-title">
            Salary Annexure Notes
        </div>

        <div class="preformatted">
            {{ $offer->salary_annexure }}
        </div>
    @endif

    <div class="section-title">
        Terms and Conditions
    </div>

    @if($offer->offer_terms)
        <div class="preformatted">
            {{ $offer->offer_terms }}
        </div>
    @else
        <ol class="terms">
            <li>
                Your employment is subject to successful
                verification of the information and documents
                submitted by you.
            </li>

            <li>
                You shall comply with all company policies,
                confidentiality requirements, information
                security standards and codes of conduct.
            </li>

            <li>
                The compensation stated in this offer is
                subject to applicable statutory deductions,
                taxes and company policies.
            </li>

            <li>
                Your employment may be subject to a probation
                period as specified in this letter.
            </li>

            <li>
                This offer remains valid until
                <strong>
                    {{
            optional(
                $offer->valid_till
            )->format('d F Y')
            ?? 'the communicated validity date'
                        }}
                </strong>.
            </li>
        </ol>
    @endif

    <p style="margin-top: 20px;">
        We look forward to welcoming you to
        <strong>{{ $company['name'] }}</strong>
        and wish you a successful career with us.
    </p>

    <table class="signature-table">
        <tr>
            <td>
                @if($company['signature_path'])
                    <img src="{{ $company['signature_path'] }}" class="signature-image" alt="HR Signature">
                @else
                    <div class="signature-line"></div>
                @endif

                <div class="signature-name">
                    {{ $company['hr_name'] }}
                </div>

                <div class="signature-designation">
                    {{ $company['hr_designation'] }}
                    <br>
                    {{ $company['legal_name'] }}
                </div>
            </td>

            <td style="text-align: right;">
                <div class="signature-line" style="margin-left: auto;"></div>

                <div class="signature-name">
                    {{
    $candidate->full_name
    ?? trim(
        ($candidate->first_name ?? '')
        . ' '
        . ($candidate->last_name ?? '')
    )
                    }}
                </div>

                <div class="signature-designation">
                    Candidate Signature
                </div>
            </td>
        </tr>
    </table>

    <div class="acceptance-box">
        <div class="acceptance-title">
            Candidate Acceptance
        </div>

        <p>
            I acknowledge that I have read, understood and
            accepted the terms and conditions of employment
            stated in this offer letter.
        </p>

        <table class="acceptance-table">
            <tr>
                <td>
                    <div class="blank-line"></div>

                    <div class="blank-label">
                        Candidate Signature
                    </div>
                </td>

                <td>
                    <div class="blank-line"></div>

                    <div class="blank-label">
                        Date
                    </div>
                </td>
            </tr>
        </table>
    </div>
</body>

</html>