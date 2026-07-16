{{-- resources/views/emails/interview-scheduled.blade.php --}}

@component('mail::message')
    # Hello {{ $candidate->full_name }},

    Your interview has been scheduled for the **{{ $round->name }}** round.

    ## Interview Details

    | | |
    |:---|:---|
    | **Date & Time** | {{ $schedule->formattedTime() }} |
    | **Round** | {{ $round->name }} |
    | **Interviewer** | {{ $interviewer->full_name }} |
    | **Mode** | Google Meet (Online) |

    ## Join the Interview

    @component('mail::button', ['url' => $schedule->gmeet_link, 'color' => 'primary'])
        Join Google Meet
    @endcomponent

    **Google Meet Link:** [{{ $schedule->gmeet_link }}]({{ $schedule->gmeet_link }})

    @if ($schedule->notes)
        ## Additional Notes
        {{ $schedule->notes }}
    @endif

    Please join the meeting **5 minutes before** the scheduled time.

    If you have any questions, feel free to reply to this email.

    Thanks,<br>
    {{ config('app.name') }} HR Team
@endcomponent
