<?php

namespace App\Notifications\Channels;

use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Http;

/**
 * Publishes a notification to the user's ntfy topic using ntfy's JSON format.
 * Notifications sent on this channel build their message in toNtfy($notifiable).
 */
class NtfyChannel
{
    /**
     * Whether an ntfy server has been configured (NTFY_URL).
     */
    public static function isConfigured(): bool
    {
        return filled(config('services.ntfy.url'));
    }

    public function send(object $notifiable, Notification $notification): void
    {
        $topic = $notifiable->routeNotificationFor('ntfy', $notification);

        if (! $topic || ! static::isConfigured()) {
            return;
        }

        $request = Http::timeout(10);
        if ($token = config('services.ntfy.token')) {
            $request = $request->withToken($token);
        }

        // JSON messages are posted to the server root, with the topic in the body
        $request->post(rtrim(config('services.ntfy.url'), '/').'/', [
            'topic' => $topic,
            ...$notification->toNtfy($notifiable),
        ])->throw();
    }
}
