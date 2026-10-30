<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Document Upload Verification Code</title>
</head>
<body style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;">
    <div style="background: #4f46e5; color: white; padding: 20px; border-radius: 8px 8px 0 0;">
        <h2 style="margin: 0;">Document Upload Verification</h2>
    </div>
    <div style="padding: 24px; border: 1px solid #e5e7eb; border-top: none; border-radius: 0 0 8px 8px;">
        <p>Hello,</p>
        <p>Your verification code for document upload is:</p>
        <div style="background: #f3f4f6; padding: 16px; text-align: center; border-radius: 6px; margin: 20px 0;">
            <span style="font-size: 32px; font-weight: bold; letter-spacing: 8px; color: #4f46e5;">{{ $otp }}</span>
        </div>
        <p>This code will expire in 10 minutes.</p>
        <p style="color: #6b7280; font-size: 14px;">If you did not request this, please ignore this email.</p>
    </div>
</body>
</html>
