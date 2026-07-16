<?php

namespace App\Notifications;

use App\Models\CandidateRoundProgress;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\DatabaseMessage;
use Illuminate\Notifications\Messages\MailMessage;

class RoundAssigned extends Notification
{
    use Queueable;

    public function __construct(protected CandidateRoundProgress $progress)
    {
    }

    public function via($notifiable)
    {
        return ['database'];
    }

    public function toDatabase($notifiable)
    {
        $candidate = $this->progress->candidate;
        $round = $this->progress->round;

        return [
            'candidate_id' => $candidate->id,
            'candidate_name' => $candidate->full_name,
            'round_id' => $round->id,
            'round_name' => $round->name,
            'assigned_by' => auth()->id(),
            'message' => "You have been assigned to conduct the {$round->name} round for {$candidate->full_name}.",
            'progress_id' => $this->progress->id,
        ];
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('Interview Round Assigned')
            ->line("You have been assigned to the {$this->progress->round->name} round for {$this->progress->candidate->full_name}.")
            ->action('View Candidate', url('/interviewer/candidates/' . $this->progress->candidate->id))
            ->line('Please review the candidate details and take action as needed.');
    }
}
