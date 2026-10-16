<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Letter of Intent - {{ $letter->letter_number }}</title>
    <style>
        @page { margin: 92px 58px 64px; }
        * { box-sizing: border-box; }
        body { margin: 0; font: 10px/1.45 DejaVu Sans, sans-serif; color: #222; }
        .header { position: fixed; top: -68px; left: 0; right: 0; height: 57px; border-bottom: 1px solid #bbb; }
        .header-table { width: 100%; border-collapse: collapse; }
        .header-table td { vertical-align: middle; }
        .logo { max-width: 120px; max-height: 42px; }
        .brand { text-align: right; font-weight: bold; font-size: 12px; }
        .footer { position: fixed; bottom: -43px; left: 0; right: 0; border-top: 1px solid #bbb; padding-top: 7px; color: #555; font-size: 8px; text-align: center; }
        h1 { margin: 0 0 16px; font-size: 18px; text-align: center; letter-spacing: .4px; }
        p { margin: 8px 0; }
        .meta { width: 100%; margin: 0 0 16px; border-collapse: collapse; }
        .meta td { padding: 3px 0; vertical-align: top; }
        .label { font-weight: bold; width: 78px; }
        .subject { margin: 14px 0; font-weight: bold; }
        .section { margin: 12px 0 3px; font-weight: bold; }
        ul { margin: 3px 0 8px; padding-left: 18px; }
        li { margin: 2px 0; }
        .signature { margin-top: 20px; page-break-inside: avoid; }
        .signature-table { width: 100%; border-collapse: collapse; }
        .signature-table td { width: 50%; padding: 5px 14px 5px 0; vertical-align: top; }
        .ack { margin-top: 18px; border: 1px solid #777; padding: 12px; page-break-inside: avoid; }
        .signed { font-style: italic; font-weight: bold; font-size: 13px; }
    </style>
</head>
<body>
    <div class="header">
        <table class="header-table">
            <tr>
                <td style="width:40%">
                    @if($company['logo_path'])
                        <img src="{{ $company['logo_path'] }}" class="logo" alt="Company logo">
                    @endif
                </td>
                <td class="brand">{{ $company['legal_name'] }}</td>
            </tr>
        </table>
    </div>
    <div class="footer">300, 3rd Floor, Mangal City Mall, Vijay Nagar, Indore, M.P. – 452011 | Mail ID: {{ $company['hr_email'] }}</div>

    <h1>LETTER OF INTENT</h1>
    <table class="meta">
        <tr><td class="label">Date:</td><td>{{ $letter->letter_date->format('d F Y') }}</td></tr>
        <tr><td class="label">To:</td><td>{{ $candidate->full_name }}<br>{{ $candidate->email }}</td></tr>
    </table>
    <p class="subject">Subject: Letter of Intent for Employment</p>
    <p>Dear <strong>{{ $candidate->full_name }}</strong>,</p>
    <p>Following our discussion and the selection process, we are pleased to issue this Letter of Intent (LOI) for the position of <strong>{{ $letter->position_title }}</strong> with {{ $company['legal_name'] }}.</p>
    <p>The broad terms and conditions applicable to your proposed employment are outlined below:</p>

    <p class="section">1. Joining Details</p>
    <ul>
        <li>Your proposed employment will be with {{ $company['legal_name'] }}.</li>
        <li>Your proposed work location will be: {{ $letter->work_location }}.</li>
        <li>Your monthly offered CTC will be Rs. {{ number_format((float) $letter->monthly_ctc, 2) }}/-.</li>
        <li>Your estimated monthly In-Hand Salary after applicable PF and ESIC deductions will be Rs. {{ number_format((float) $letter->monthly_in_hand, 2) }}/-.</li>
        <li>Salary and compensation details are confidential and should not be disclosed or discussed with any employee.</li>
        <li>Your proposed Date of Joining (DOJ) is {{ $letter->joining_date->format('d F Y') }}. The DOJ may be changed based on business requirements and will be communicated by the company, wherever applicable.</li>
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
    <ul>
        <li>Aadhaar Card, PAN Card, two passport-size photographs, 10th &amp; 12th Mark Sheets, Graduation Mark Sheets/Degree wherever applicable, Experience/Relieving Letter, last drawn salary proof, previous organization's offer/appointment letter, cancelled cheque or bank statement, and last three months' salary slips from the previous organization.</li>
    </ul>

    <p class="section">4. General Terms &amp; Conditions</p>
    <ul>
        <li>This Letter of Intent is issued based on the information and documents provided by you during the recruitment process.</li>
        <li>Any discrepancy, misrepresentation, concealment of information, or adverse finding during document verification/background verification may result in withdrawal of this Letter of Intent or termination of employment, as applicable.</li>
        <li>If you fail to report on the confirmed Date of Joining without prior approval from the company, this Letter of Intent may stand withdrawn.</li>
        <li>The final terms and conditions of employment will be governed by the company's applicable HR policies, employment terms, and the final appointment/offer letter issued by the company.</li>
        <li>This Letter of Intent does not override or replace the company's HR policies or the final employment documentation.</li>
    </ul>

    @if($includeAcknowledgement)
        <div class="ack">
            <strong>Employee Declaration</strong>
            <p>{{ $letter->acknowledgement_text }}</p>
            <table class="signature-table">
                <tr>
                    <td>Employee Name<br><span class="signed">{{ $letter->acknowledgement_name }}</span></td>
                    <td>Employee Signature<br><span class="signed">{{ $letter->acknowledgement_name }}</span></td>
                </tr>
                <tr><td colspan="2">Date: {{ $letter->acknowledged_at->format('d F Y, h:i A') }}</td></tr>
            </table>
        </div>
    @else
        <p class="section">Employee Declaration</p>
        <p>I, _____________________________, hereby confirm that I have read and understood the above terms and conditions and agree to comply with the applicable rules, policies, and requirements of {{ $company['legal_name'] }}.</p>
        <table class="signature-table signature">
            <tr><td>Employee Name: ___________________________</td><td>Employee Signature: ________________________</td></tr>
            <tr><td colspan="2">Date: ___________________</td></tr>
        </table>
    @endif

    <div class="signature">
        <p>Warm Regards,</p>
        <p><strong>HR Department<br>{{ $company['legal_name'] }}</strong></p>
    </div>
</body>
</html>
