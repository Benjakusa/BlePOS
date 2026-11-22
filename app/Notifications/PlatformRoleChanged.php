<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PlatformRoleChanged extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $roleName,
        public string $change,
        public string $reason,
        public string $changedBy,
    ) {
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
            ->subject('BlePOS platform access changed')
            ->greeting('Hello '.$notifiable->name.',')
            ->line('Your platform role “'.$this->roleName.'” was '.$this->change.'.')
            ->line('Changed by: '.$this->changedBy)
            ->line('Reason: '.$this->reason)
            ->line('If you did not expect this change, contact your platform security administrator.');
    }
}
