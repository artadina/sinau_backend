<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ModuleGovernanceNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly string $event,
        private readonly string $message,
        private readonly int $moduleId,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'event' => $this->event,
            'message' => $this->message,
            'module_id' => $this->moduleId,
        ];
    }
}
