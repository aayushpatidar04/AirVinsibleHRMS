<?php

namespace App\Mail;

use App\Models\InterviewSchedule;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class InterviewScheduledMail extends Mailable
{
    use Queueable, SerializesModels;

    public $schedule;

    public function __construct(InterviewSchedule $schedule)
    {
        $this->schedule = $schedule;
    }

    public function build()
    {
        return $this->subject('Your Interview is Scheduled - ' . $this->schedule->round->name)
            ->markdown('emails.interview-scheduled')
            ->with([
                'schedule' => $this->schedule,
                'candidate' => $this->schedule->candidate,
                'round' => $this->schedule->round,
                'interviewer' => $this->schedule->interviewer,
            ]);
    }
}