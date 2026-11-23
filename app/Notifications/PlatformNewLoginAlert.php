<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Carbon;

class PlatformNewLoginAlert extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public ?string $ipAddress, public string $userAgent, public Carbon $occurredAt)
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
            ->subject('New BlePOS platform sign-in')
            ->greeting('Hello '.$notifiable->name.',')
            ->line('A successful sign-in to your platform account used a different IP address or browser than your recent sessions.')
            ->line('Time: '.$this->occurredAt->toDayDateTimeString())
            ->line('IP address: '.($this->ipAddress ?? 'Unavailable'))
            ->line('Device / browser: '.$this->userAgent)
            ->line('If this was not you, contact your platform security administrator and ask them to revoke your sessions.');
    }
}
