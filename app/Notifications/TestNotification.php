<?php

namespace App\Notifications;

use App\Notifications\Channels\NtfyChannel;
use Illuminate\Notifications\Notification;

/**
 * Sent from the Profile page to check that a user's ntfy topic is set up.
 */
class TestNotification extends Notification
{
    public function via(object $notifiable): array
    {
        return [NtfyChannel::class];
    }

    public function toNtfy(object $notifiable): array
    {
        return [
            'title' => 'BeChore test notification',
            'message' => "Reminders for {$notifiable->name} will show up here.",
            'tags' => ['white_check_mark'],
            'click' => route('dashboard'),
        ];
    }
}
