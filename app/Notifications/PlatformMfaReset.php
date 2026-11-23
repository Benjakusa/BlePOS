<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PlatformMfaReset extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public string $changedBy, public string $reason)
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
            ->subject('BlePOS platform MFA reset')
            ->greeting('Hello '.$notifiable->name.',')
            ->line('An approved administrator reset your platform multi-factor authentication.')
            ->line('Changed by: '.$this->changedBy)
            ->line('Reason: '.$this->reason)
            ->line('Your existing sessions were revoked. Sign in with your password and enroll a new authenticator before accessing the console.');
    }
}
