<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class DocumentUploadOtpMail extends Mailable
{
    public string $otpCode;

    public function __construct(string $otp)
    {
        $this->otpCode = $otp;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Document Upload Verification Code',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.document-upload-otp',
            with: ['otp' => $this->otpCode],
        );
    }
}
