<?php

namespace App\Mail;

use App\Models\CandidateOffer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;


class CandidateOfferMail extends Mailable implements ShouldQueue
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public CandidateOffer $offer,
        public string $emailSubject,
        public string $emailMessage,
        public bool $attachPdf = true,
        public ?string $portalUrl = null,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->emailSubject,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.recruitment.candidate-offer',
            with: [
                'offer' => $this->offer,
                'candidate' =>
                    $this->offer->candidate,
                'emailMessage' =>
                    $this->emailMessage,
                'portalUrl' =>
                    $this->portalUrl,
            ],
        );
    }

    public function attachments(): array
    {
        if (
            !$this->attachPdf ||
            !$this->offer->pdf_path ||
            !Storage::disk('public')->exists(
                $this->offer->pdf_path
            )
        ) {
            return [];
        }

        return [
            Attachment::fromStorageDisk(
                'public',
                $this->offer->pdf_path
            )->as(
                sprintf(
                    '%s-v%s.pdf',
                    $this->offer->offer_number
                        ?: 'offer-letter',
                    $this->offer->version ?: 1
                )
            )->withMime('application/pdf'),
        ];
    }
}