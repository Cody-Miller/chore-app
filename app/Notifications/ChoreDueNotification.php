<?php

namespace App\Notifications;

use App\Models\Chore;
use App\Notifications\Channels\NtfyChannel;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;

class ChoreDueNotification extends Notification
{
    public function __construct(public Chore $chore)
    {
    }

    public function via(object $notifiable): array
    {
        return [NtfyChannel::class];
    }

    public function toNtfy(object $notifiable): array
    {
        $chore = $this->chore;
        $lastDate = $chore->getLastCompletedDate();
        $dueDate = $lastDate && $chore->occurrence_hours
            ? $lastDate->copy()->addHours($chore->occurrence_hours)->startOfDay()
            : null;

        $lines = [$dueDate && $dueDate->isBefore(today()) ? 'Overdue since '.$dueDate->format('D, M j') : 'Due today'];
        if ($lastDate) {
            $lines[] = 'Last done by '.$chore->getLastCompletedUser().' on '.$lastDate->format('D, M j');
        }

        return [
            'title' => "Chore due: {$chore->name}",
            'message' => implode("\n", $lines),
            'tags' => ['broom'],
            'click' => route('chores.show', $chore),
            'actions' => [
                [
                    'action' => 'http',
                    'label' => 'Done',
                    'url' => $this->completeUrl($notifiable),
                    'clear' => true,
                ],
                [
                    'action' => 'view',
                    'label' => 'Open',
                    'url' => route('chores.show', $chore),
                ],
            ],
        ];
    }

    /**
     * Signed link that logs this chore as done by the recipient. The signature only
     * covers the path and query, so it still validates behind a reverse proxy.
     */
    private function completeUrl(object $notifiable): string
    {
        return url(URL::temporarySignedRoute('reminders.chores.complete', now()->addDay(), [
            'chore' => $this->chore,
            'user' => $notifiable,
            'date' => today()->toDateString(),
        ], absolute: false));
    }
}
