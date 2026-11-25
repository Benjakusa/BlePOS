<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\DatabaseMessage;
use Illuminate\Notifications\Notification;

class WorkspaceAlert extends Notification
{
    use Queueable;

    /** @param array{title: string, message: string, url: string, kind?: string} $data */
    public function __construct(private readonly array $data) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): DatabaseMessage
    {
        return new DatabaseMessage($this->data);
    }
}
