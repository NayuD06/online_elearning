<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

/**
 * A notification that is only shown under the bell in the top bar (no email).
 */
abstract class InAppNotification extends Notification
{
    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array{icon: string, title: string, body: string, url: string}
     */
    abstract public function toArray(object $notifiable): array;
}
