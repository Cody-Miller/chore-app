<?php

namespace App\Console\Commands;

use App\Models\Chore;
use App\Models\Pill;
use App\Models\SentReminder;
use App\Models\User;
use App\Notifications\Channels\NtfyChannel;
use App\Notifications\ChoreDueNotification;
use App\Notifications\PillDueNotification;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendReminders extends Command
{
    /**
     * How long after a dose's scheduled time its reminder can still go out. This catches
     * a missed scheduler run without sending reminders for long-past doses.
     */
    const PILL_WINDOW_MINUTES = 60;

    protected $signature = 'reminders:send';

    protected $description = 'Send ntfy reminders for chores due today and pill doses that are due';

    private int $sent = 0;

    public function handle(): int
    {
        if (! NtfyChannel::isConfigured()) {
            $this->info('NTFY_URL is not set, skipping reminders.');

            return self::SUCCESS;
        }

        $recipients = User::withNtfyTopic()->get();

        if ($recipients->isNotEmpty()) {
            $this->sendChoreReminders($recipients);
            $this->sendPillReminders($recipients);
        }

        $this->info("Sent {$this->sent} reminder(s).");

        return self::SUCCESS;
    }

    /**
     * From the configured time (8:00 AM by default), remind about each opted-in chore that is
     * due today or overdue. Assigned chores only go to the assignee.
     */
    private function sendChoreReminders(Collection $recipients): void
    {
        if (now()->format('H:i') < config('services.ntfy.chore_time')) {
            return;
        }

        $today = today()->toDateString();

        Chore::getDueToday()
            ->where('notifications_enabled', true)
            ->each(function (Chore $chore) use ($recipients, $today) {
                $users = $chore->assigned_user_id
                    ? $recipients->where('id', $chore->assigned_user_id)
                    : $recipients;

                foreach ($users as $user) {
                    $this->remind($user, $chore, $today, '', new ChoreDueNotification($chore));
                }
            });
    }

    /**
     * Remind everyone about each opted-in dose whose scheduled time has just passed
     * and that hasn't been logged yet.
     */
    private function sendPillReminders(Collection $recipients): void
    {
        $currentTime = now()->format('H:i');
        $windowStart = now()->subMinutes(self::PILL_WINDOW_MINUTES)->format('H:i');
        $effectiveDate = Pill::getEffectiveDateString();

        Pill::active()
            ->where('notifications_enabled', true)
            ->with('pet')
            ->get()
            ->each(function (Pill $pill) use ($recipients, $currentTime, $windowStart, $effectiveDate) {
                $given = $pill->getTodaysCompletedDoses();

                foreach ($pill->scheduled_times as $time) {
                    // The window wraps past midnight when it starts the previous evening
                    $inWindow = $windowStart < $currentTime
                        ? $time > $windowStart && $time <= $currentTime
                        : $time > $windowStart || $time <= $currentTime;

                    if (! $inWindow || in_array($time, $given)) {
                        continue;
                    }

                    foreach ($recipients as $user) {
                        $this->remind($user, $pill, $effectiveDate, $time, new PillDueNotification($pill, $time));
                    }
                }
            });
    }

    /**
     * Send one reminder unless it already went out. It's only recorded once ntfy
     * accepts it, so a failed send is retried on the next run.
     */
    private function remind(User $user, Model $remindable, string $date, string $time, Notification $notification): void
    {
        $key = [
            'remindable_type' => $remindable->getMorphClass(),
            'remindable_id' => $remindable->getKey(),
            'user_id' => $user->id,
            'for_date' => $date,
            'scheduled_time' => $time,
        ];

        if (SentReminder::where($key)->exists()) {
            return;
        }

        try {
            $user->notify($notification);
        } catch (Throwable $e) {
            Log::warning("ntfy reminder for {$remindable->name} to {$user->name} failed: {$e->getMessage()}");
            $this->warn("Failed to remind {$user->name} about {$remindable->name}: {$e->getMessage()}");

            return;
        }

        SentReminder::create($key);
        $this->sent++;
    }
}
