<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Carbon;

class PlatformAccountLocked extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Carbon $lockedUntil)
    {
        $this->afterCommit();
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('BlePOS platform account temporarily locked')
            ->greeting('Hello '.$notifiable->name.',')
            ->line('Your platform account was temporarily locked after repeated sign-in failures.')
            ->line('The lock expires at '.$this->lockedUntil->toDayDateTimeString().'. If you did not make these attempts, contact your platform security administrator.');
    }
}
