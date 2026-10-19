@php
    use App\Support\LetterHelper;
    $name = $candidate->full_name;
    $money = fn ($value) => LetterHelper::inr($value, false);
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Letter of Intent - {{ $letter->letter_number }}</title>
    @include('pdf.recruitment.partials.letter-styles')
</head>
<body>
    @include('pdf.recruitment.partials.letter-chrome')

    <p class="heading">LETTER OF INTENT</p>

    <p><span class="bold">Date:</span> {{ $letter->letter_date->format('d F Y') }}</p>

    <p class="bold" style="margin-bottom: 2px;">To,</p>
    <p class="bold">{{ $name }},</p>

    <p class="bold" style="margin-top: 14px;">Subject: Letter of Intent for Employment</p>

    <p>Dear {{ $name }},</p>

    <p>Following our discussion and the selection process, we are pleased to issue this <span class="bold">Letter of Intent (LOI)</span> for the position of {{ $letter->position_title }} with <span class="bold">{{ $company['legal_name'] }}</span></p>

    <p>The broad terms and conditions applicable to your proposed employment are outlined below:</p>

    <p class="section">1. Joining Details</p>
    <ul>
        <li>Your proposed employment will be with <span class="bold">{{ $company['legal_name'] }}</span></li>
        <li>Your proposed work location will be: {{ $letter->work_location }}</li>
        <li>Your monthly offered <span class="bold">CTC will be Rs. {{ $money($letter->monthly_ctc) }}/-.</span></li>
        <li>Your estimated monthly <span class="bold">In-Hand Salary after applicable PF and ESIC deductions will be Rs. {{ $money($letter->monthly_in_hand) }}/-</span></li>
        <li>Salary and compensation details are confidential and should not be disclosed or discussed with any employee.</li>
        <li>Your proposed <span class="bold">Date of Joining (DOJ) is {{ $letter->joining_date->format('d F Y') }}</span>. The DOJ may be changed based on business requirements and will be communicated by the company, wherever applicable.</li>
    </ul>

    <p class="section">2. Pre-Joining Conditions</p>
    <p>This Letter of Intent is subject to successful completion of all applicable pre-joining formalities, including:</p>
    <ul>
        <li>Required assessment/skill tests, wherever applicable</li>
        <li>HR and Operations selection process</li>
        <li>Submission and verification of required documents</li>
        <li>Background verification, wherever applicable</li>
        <li>Fulfillment of eligibility criteria for the offered position</li>
    </ul>
    <p>The final employment offer will be subject to successful completion of the above requirements.</p>

    <p class="section">3. Documents Required on DOJ</p>
    <p>You are required to submit the following documents at the time of joining:</p>
    <p class="bold" style="margin-bottom: 4px;">Documents required at the time of Joining.</p>
    <ul>
        <li>Aadhaar Card, PAN Card, Two passport-size photographs, 10th &amp; 12th Mark Sheet, Graduation Mark Sheets/Degree, wherever applicable, Experience/Relieving Letter, Last drawn salary proof, Previous organization's offer/appointment letter, Cancelled Cheque or Bank Statement, Last 3 months' salary slips from the previous organization</li>
    </ul>

    <p class="section">4. General Terms &amp; Conditions</p>
    <ul>
        <li>This Letter of Intent is issued based on the information and documents provided by you during the recruitment process.</li>
        <li>Any discrepancy, misrepresentation, concealment of information, or adverse finding during document verification/background verification may result in withdrawal of this Letter of Intent or termination of employment, as applicable.</li>
        <li>If you fail to report on the confirmed Date of Joining without prior approval from the company, this Letter of Intent may stand withdrawn.</li>
        <li>The final terms and conditions of employment will be governed by the company's applicable HR policies, employment terms, and the final appointment/offer letter issued by the company.</li>
        <li>This Letter of Intent does not override or replace the company's HR policies or the final employment documentation.</li>
    </ul>

    <div class="nobreak">
        <p class="section">Employee Declaration</p>

        @if($includeAcknowledgement)
            <p>{{ $letter->acknowledgement_text }}</p>
            <p><span class="bold">Employee Name:</span> <span class="signed">{{ $letter->acknowledgement_name }}</span></p>
            <p><span class="bold">Employee Signature:</span> <span class="signed">{{ $letter->acknowledgement_name }}</span></p>
            <p><span class="bold">Date:</span> {{ $letter->acknowledged_at->format('d F Y, h:i A') }}</p>
        @else
            <p>I, <span class="blank" style="min-width: 210px;">&nbsp;</span>, hereby confirm that I have read and understood the above terms and conditions and agree to comply with the applicable rules, policies, and requirements of {{ $company['legal_name'] }}</p>
            <p><span class="bold">Employee Name:</span> <span class="blank" style="min-width: 210px;">&nbsp;</span></p>
            <p><span class="bold">Employee Signature:</span> <span class="blank" style="min-width: 190px;">&nbsp;</span></p>
            <p><span class="bold">Date:</span> <span class="blank">&nbsp;</span></p>
        @endif

        <p class="bold" style="margin-top: 16px;">Warm Regards,<br>HR Department<br>{{ $company['legal_name'] }}</p>
    </div>
</body>
</html>
