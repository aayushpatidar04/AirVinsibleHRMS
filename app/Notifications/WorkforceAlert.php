<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * One in-app notification used by the attendance, leave and payroll modules.
 * Email delivery can be added to via() once channels are confirmed with the client.
 */
class WorkforceAlert extends Notification
{
    use Queueable;

    public function __construct(
        private readonly string $category,
        private readonly string $message,
        private readonly ?string $url = null,
    ) {
    }

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toDatabase($notifiable): array
    {
        return [
            'category' => $this->category,
            'message' => $this->message,
            'url' => $this->url,
        ];
    }
}
