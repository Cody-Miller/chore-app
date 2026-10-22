<?php

namespace App\Notifications;

use App\Models\Pill;
use App\Notifications\Channels\NtfyChannel;
use Carbon\Carbon;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;

class PillDueNotification extends Notification
{
    public function __construct(public Pill $pill, public string $scheduledTime)
    {
    }

    public function via(object $notifiable): array
    {
        return [NtfyChannel::class];
    }

    public function toNtfy(object $notifiable): array
    {
        $pill = $this->pill;
        $time = Carbon::createFromFormat('H:i', $this->scheduledTime)->format('g:i A');

        return [
            'title' => "{$pill->pet->name}: {$pill->name} ({$pill->dosage})",
            'message' => "Scheduled for {$time}",
            'tags' => ['pill'],
            'priority' => 4,
            'click' => route('pills.dashboard'),
            'actions' => [
                [
                    'action' => 'http',
                    'label' => 'Given',
                    'url' => $this->completeUrl($notifiable),
                    'clear' => true,
                ],
                [
                    'action' => 'view',
                    'label' => 'Open',
                    'url' => route('pills.show', $pill),
                ],
            ],
        ];
    }

    /**
     * Signed link that logs this dose as given by the recipient, valid until the dose's
     * effective day ends. The signature only covers the path and query, so it still
     * validates behind a reverse proxy.
     */
    private function completeUrl(object $notifiable): string
    {
        return url(URL::temporarySignedRoute('reminders.pills.complete', Pill::getEffectiveDayEnd(), [
            'pill' => $this->pill,
            'user' => $notifiable,
            'time' => $this->scheduledTime,
            'date' => Pill::getEffectiveDateString(),
        ], absolute: false));
    }
}
