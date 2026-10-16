<?php

namespace App\Mail;

use App\Models\CandidateIntentLetter;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class CandidateIntentLetterMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public CandidateIntentLetter $letter,
        public string $portalUrl
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: sprintf(
                'Letter of Intent for %s',
                $this->letter->position_title
            )
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.recruitment.candidate-intent-letter',
            with: [
                'letter' => $this->letter,
                'candidate' => $this->letter->candidate,
                'portalUrl' => $this->portalUrl,
            ]
        );
    }

    public function attachments(): array
    {
        if (
            !$this->letter->pdf_path ||
            !Storage::disk('local')->exists($this->letter->pdf_path)
        ) {
            return [];
        }

        return [
            Attachment::fromStorageDisk('local', $this->letter->pdf_path)
                ->as($this->letter->letter_number . '.pdf')
                ->withMime('application/pdf'),
        ];
    }
}
