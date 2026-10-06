<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

/**
 * In-portal notification (bell + dashboard). data:
 *   title, body, url, type (notice|result|payment|absence|fees|birthday|system), icon, key
 */
class PortalNotification extends Notification
{
    public function __construct(public array $payload) {}

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toArray($notifiable): array
    {
        return $this->payload;
    }
}
