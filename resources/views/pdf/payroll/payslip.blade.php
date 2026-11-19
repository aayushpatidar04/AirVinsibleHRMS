@php
    use App\Support\LetterHelper;
    $inr = fn ($v) => LetterHelper::inr($v, false);
    $rows = max(count($s['earnings']), count($s['deductions']));
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Payslip {{ $payslip->payslip_number }}</title>
    <style>
        @page { margin: 40px 44px 50px; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 10.5px; color: #111; line-height: 1.4; }
        .head { text-align: center; margin-bottom: 10px; }
        .head img { height: 52px; }
        .company { font-size: 9px; color: #444; margin-top: 2px; }
        h1 { font-size: 14px; text-align: center; margin: 8px 0 2px; letter-spacing: 1px; }
        .sub { text-align: center; color: #444; margin-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; }
        .box td, .box th { border: 1px solid #000; padding: 3px 6px; vertical-align: top; }
        .info td { padding: 2px 4px; border: none; }
        .info .l { color: #555; width: 18%; }
        .info .v { font-weight: bold; width: 32%; }
        th { background: #f1f1f1; text-align: left; }
        .num { text-align: right; }
        .total td { font-weight: bold; background: #f7f7f7; }
        .net { margin-top: 10px; border: 1px solid #000; padding: 6px 8px; }
        .net b { font-size: 13px; }
        .foot { margin-top: 18px; font-size: 9px; color: #555; }
    </style>
</head>
<body>
    <div class="head">
        @if($logo)<img src="{{ $logo }}" alt="">@endif
        <div class="company">{{ $s['company']['address'] }}</div>
    </div>

    <h1>PAYSLIP</h1>
    <div class="sub">{{ $s['period']['name'] }} &nbsp;({{ $s['period']['start'] }} – {{ $s['period']['end'] }})</div>

    <table class="info" style="margin-bottom: 8px;">
        <tr>
            <td class="l">Employee</td><td class="v">{{ $s['employee']['name'] }}</td>
            <td class="l">Employee ID</td><td class="v">{{ $s['employee']['employee_id'] }}</td>
        </tr>
        <tr>
            <td class="l">Designation</td><td class="v">{{ $s['employee']['designation'] ?: '-' }}</td>
            <td class="l">Department</td><td class="v">{{ $s['employee']['department'] ?: '-' }}</td>
        </tr>
        <tr>
            <td class="l">Client</td><td class="v">{{ $s['employee']['client'] }}</td>
            <td class="l">Date of joining</td><td class="v">{{ $s['employee']['date_of_joining'] ?: '-' }}</td>
        </tr>
        <tr>
            <td class="l">Paid days</td><td class="v">{{ rtrim(rtrim(number_format($s['days']['paid'], 2), '0'), '.') }} / {{ $s['days']['calendar'] }}</td>
            <td class="l">Unpaid days</td><td class="v">{{ rtrim(rtrim(number_format($s['days']['unpaid'], 2), '0'), '.') }}</td>
        </tr>
        <tr>
            <td class="l">Paid leave</td><td class="v">{{ rtrim(rtrim(number_format($s['days']['paid_leave'], 2), '0'), '.') ?: 0 }}</td>
            <td class="l">Overtime</td><td class="v">{{ $s['overtime_hours'] }} h</td>
        </tr>
    </table>

    <table class="box">
        <thead>
            <tr>
                <th style="width: 30%">Earnings</th><th class="num" style="width: 20%">Amount (INR)</th>
                <th style="width: 30%">Deductions</th><th class="num" style="width: 20%">Amount (INR)</th>
            </tr>
        </thead>
        <tbody>
            @for($i = 0; $i < $rows; $i++)
                <tr>
                    <td>{{ $s['earnings'][$i]['name'] ?? '' }}</td>
                    <td class="num">{{ isset($s['earnings'][$i]) ? $inr($s['earnings'][$i]['amount']) : '' }}</td>
                    <td>{{ $s['deductions'][$i]['name'] ?? '' }}</td>
                    <td class="num">{{ isset($s['deductions'][$i]) ? $inr($s['deductions'][$i]['amount']) : '' }}</td>
                </tr>
            @endfor
            <tr class="total">
                <td>Gross earnings</td><td class="num">{{ $inr($s['gross']) }}</td>
                <td>Total deductions</td><td class="num">{{ $inr($s['total_deductions']) }}</td>
            </tr>
        </tbody>
    </table>

    <div class="net">
        Net payable salary: <b>₹ {{ $inr($s['net']) }}</b><br>
        <span style="color:#444">{{ $s['net_in_words'] }}</span><br>
        <span style="color:#444">Payment status: {{ $s['payment_status'] }}</span>
    </div>

    <div class="foot">
        Payslip no. {{ $payslip->payslip_number }} · Generated on {{ $payslip->generated_at->format('d M Y') }}.
        This is a computer-generated payslip. Salary details are confidential.
    </div>
</body>
</html>
