<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Letter of Intent</title>
</head>
<body style="margin:0;padding:32px 16px;background:#f3f4f6;font-family:Arial,Helvetica,sans-serif;color:#1f2937;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="max-width:640px;background:#fff;border-radius:14px;overflow:hidden;">
                    <tr>
                        <td style="padding:24px 32px;background:#4f46e5;color:#fff;">
                            <div style="font-size:20px;font-weight:bold;">Letter of Intent</div>
                            <div style="margin-top:6px;font-size:13px;color:#e0e7ff;">{{ $letter->letter_number }}</div>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:32px;font-size:14px;line-height:1.8;">
                            <p>Dear {{ $candidate->first_name ?? $candidate->full_name }},</p>
                            <p>Please find attached your Letter of Intent for the position of <strong>{{ $letter->position_title }}</strong>. Review the proposed employment terms and acknowledge the letter securely using the link below.</p>
                            <p style="margin-top:28px;">
                                <a href="{{ $portalUrl }}" style="display:inline-block;padding:13px 22px;border-radius:10px;background:#4f46e5;color:#fff;text-decoration:none;font-weight:bold;">Review and Acknowledge LOI</a>
                            </p>
                            <p style="color:#6b7280;font-size:12px;">The Letter of Intent is preliminary and subject to the conditions described in the attached document. It does not replace the final employment offer or appointment letter.</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
