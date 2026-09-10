<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>{{ $offer->offer_number }}</title>
</head>

<body style="
        margin: 0;
        padding: 0;
        background-color: #f3f4f6;
        font-family: Arial, Helvetica, sans-serif;
        color: #1f2937;
    ">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0"
        style="background-color: #f3f4f6;">
        <tr>
            <td align="center" style="padding: 32px 16px;">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="
                        max-width: 640px;
                        background-color: #ffffff;
                        border-radius: 14px;
                        overflow: hidden;
                        box-shadow: 0 8px 25px rgba(0,0,0,0.06);
                    ">
                    <tr>
                        <td style="
                                padding: 24px 32px;
                                background-color: #4f46e5;
                                color: #ffffff;
                            ">
                            <div style="
                                    font-size: 20px;
                                    font-weight: bold;
                                ">
                                Employment Offer
                            </div>

                            <div style="
                                    margin-top: 6px;
                                    font-size: 13px;
                                    color: #e0e7ff;
                                ">
                                {{ $offer->offer_number }}
                            </div>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding: 32px;">
                            <p style="
                                    margin: 0 0 18px;
                                    font-size: 15px;
                                    line-height: 1.7;
                                ">
                                Dear
                                <strong>
                                    {{
    $candidate->first_name
    ?? $candidate->full_name
    ?? 'Candidate'
                                    }}
                                </strong>,
                            </p>

                            <div style="
                                    white-space: pre-line;
                                    font-size: 14px;
                                    line-height: 1.8;
                                    color: #374151;
                                ">{{ $emailMessage }}</div>

                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="
                                    margin-top: 28px;
                                    border-collapse: collapse;
                                ">
                                <tr>
                                    <td style="
                                            padding: 12px;
                                            border: 1px solid #e5e7eb;
                                            background: #f9fafb;
                                            font-size: 12px;
                                            color: #6b7280;
                                        ">
                                        Position
                                    </td>

                                    <td style="
                                            padding: 12px;
                                            border: 1px solid #e5e7eb;
                                            font-size: 13px;
                                            font-weight: bold;
                                        ">
                                        {{
    $offer->designation?->name
    ?? $offer->designation
    ?? '—'
                                        }}
                                    </td>
                                </tr>

                                <tr>
                                    <td style="
                                            padding: 12px;
                                            border: 1px solid #e5e7eb;
                                            background: #f9fafb;
                                            font-size: 12px;
                                            color: #6b7280;
                                        ">
                                        Joining Date
                                    </td>

                                    <td style="
                                            padding: 12px;
                                            border: 1px solid #e5e7eb;
                                            font-size: 13px;
                                            font-weight: bold;
                                        ">
                                        {{
    optional(
        $offer->joining_date
    )->format('d M Y')
    ?? '—'
                                        }}
                                    </td>
                                </tr>

                                <tr>
                                    <td style="
                                            padding: 12px;
                                            border: 1px solid #e5e7eb;
                                            background: #f9fafb;
                                            font-size: 12px;
                                            color: #6b7280;
                                        ">
                                        Offer Valid Till
                                    </td>

                                    <td style="
                                            padding: 12px;
                                            border: 1px solid #e5e7eb;
                                            font-size: 13px;
                                            font-weight: bold;
                                        ">
                                        {{
    optional(
        $offer->valid_till
    )->format('d M Y')
    ?? '—'
                                        }}
                                    </td>
                                </tr>
                            </table>

                            @if($portalUrl)
                                                    <table role="presentation" cellspacing="0" cellpadding="0" border="0"
                                                        style="margin-top: 28px;">
                                                        <tr>
                                                            <td style="
                                            border-radius: 10px;
                                            background-color: #4f46e5;
                                        ">
                                                                <a href="{{ $portalUrl }}" target="_blank" style="
                                                display: inline-block;
                                                padding: 13px 22px;
                                                color: #ffffff;
                                                text-decoration: none;
                                                font-size: 14px;
                                                font-weight: bold;
                                            ">
                                                                    Review and Respond to Offer
                                                                </a>
                                                            </td>
                                                        </tr>
                                                    </table>

                                                    <p style="
                                    margin: 18px 0 0;
                                    font-size: 12px;
                                    line-height: 1.6;
                                    color: #6b7280;
                                ">
                                                        This link is private and intended only for you.
                                                        Please do not forward it to anyone.
                                                    </p>
                            @endif

                            <p style="
                                    margin: 28px 0 0;
                                    font-size: 13px;
                                    line-height: 1.7;
                                    color: #6b7280;
                                ">
                                The complete offer letter is
                                attached to this email as a PDF.
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <td style="
                                padding: 20px 32px;
                                background: #f9fafb;
                                border-top: 1px solid #e5e7eb;
                                font-size: 12px;
                                color: #6b7280;
                            ">
                            This email contains confidential
                            employment information intended only
                            for the recipient.
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>

</html>