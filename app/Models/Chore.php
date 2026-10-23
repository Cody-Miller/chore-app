<?php

namespace App\Models;

use App\Models\Traits\HasTags;
use App\Support\Timezone;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Chore extends Model
{
    use HasFactory, HasTags, SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'weight',
        'occurrence_hours',
        'assigned_user_id',
    ];

    /**
     * Get the route key for the model.
     */
    public function getRouteKeyName()
    {
        return 'slug';
    }

    /**
     * Recurring chores that are due (see getStatus()), skipping snoozed ones.
     */
    public static function getDue($userId = null, $showAll = true)
    {
        return self::dashboardCandidates($userId, $showAll)
            ->filter(fn (Chore $chore) => $chore->getStatus() === 'due')
            ->values();
    }

    /**
     * Recurring chores due within the next few days (see getStatus()), skipping snoozed ones.
     */
    public static function getUpcoming($userId = null, $showAll = true)
    {
        return self::dashboardCandidates($userId, $showAll)
            ->filter(fn (Chore $chore) => $chore->getStatus() === 'upcoming')
            ->values();
    }

    /**
     * Recurring, unsnoozed chores that could be on the dashboard, with what getStatus() needs loaded.
     */
    private static function dashboardCandidates($userId, $showAll)
    {
        return self::with(['assignedUser', 'tags', 'lastChoreLog', 'activeSnooze'])
            ->where('occurrence_hours', '!=', 0)
            ->whereDoesntHave('snoozes', fn ($q) => $q->where('snoozed_until', '>', now()))
            ->when(!$showAll && $userId, fn ($q) => $q->visibleOnDashboardFor($userId))
            ->orderBy('id')
            ->get();
    }

    public static function getOneTime($userId = null, $showAll = true)
    {
        $query = self::with(['assignedUser', 'tags'])->where('occurrence_hours', 0);

        // Filter out globally snoozed chores
        $query->whereDoesntHave('snoozes', function ($q) {
            $q->where('snoozed_until', '>', now());
        });

        if (!$showAll && $userId) {
            $query->visibleOnDashboardFor($userId);
        }

        return $query->get();
    }

    /**
     * Get chores that are snoozed (globally, for all users), soonest to wake first.
     */
    public static function getSnoozed()
    {
        return self::select('chores.*', \DB::raw('MAX(chore_snoozes.snoozed_until) as snoozed_until'))
            ->with('tags')
            ->join('chore_snoozes', 'chores.id', '=', 'chore_snoozes.chore_id')
            ->where('chore_snoozes.snoozed_until', '>', now())
            ->whereNull('chores.deleted_at')
            ->groupBy('chores.id')
            ->orderBy('snoozed_until')
            ->get();
    }

    /**
     * Snooze this chore globally, replacing any existing snooze.
     */
    public function snooze(User $user, int $hours): ChoreSnooze
    {
        $this->unsnooze();

        return ChoreSnooze::create([
            'chore_id' => $this->id,
            'user_id' => $user->id,
            'snoozed_until' => now()->addHours($hours),
        ]);
    }

    /**
     * Remove every snooze from this chore (globally, for all users).
     */
    public function unsnooze(): void
    {
        ChoreSnooze::where('chore_id', $this->id)->delete();
    }

    /**
     * When this recurring chore is next due, or null for one-time or never-completed chores.
     */
    public function getNextDueAt(): ?Carbon
    {
        $lastCompleted = $this->lastChoreLog?->completed_at;

        if (!$lastCompleted || $this->occurrence_hours == 0) {
            return null;
        }

        return $this->addOccurrence($lastCompleted->copy()->setTimezone(Timezone::household()))->utc();
    }

    /**
     * Which dashboard list this chore belongs to: snoozed, one_time, due, upcoming or scheduled.
     * Dates are the household's. A chore is due the day after its due date and upcoming
     * from 3 days before it, matching how the dashboard has always worked.
     */
    public function getStatus(): string
    {
        if ($this->activeSnooze) {
            return 'snoozed';
        }
        if ($this->occurrence_hours == 0) {
            return 'one_time';
        }

        $lastCompleted = $this->lastChoreLog?->completed_at?->copy()->setTimezone(Timezone::household());
        $today = now(Timezone::household())->startOfDay();

        if (!$lastCompleted || $today->gt($this->addOccurrence($lastCompleted)->startOfDay())) {
            return 'due';
        }

        $upcomingAt = $this->addOccurrence($lastCompleted->copy()->startOfDay());
        if ($today->copy()->addDays(3)->gte($upcomingAt) && $today->lte($upcomingAt)) {
            return 'upcoming';
        }

        return 'scheduled';
    }

    /**
     * The date plus this chore's occurrence, counting whole days on the calendar so
     * daylight saving changes don't shift it by an hour.
     */
    private function addOccurrence(Carbon $date): Carbon
    {
        return $date->copy()
            ->addDays(intdiv($this->occurrence_hours, 24))
            ->addHours($this->occurrence_hours % 24);
    }

    public function hasCompleted()
    {
        return (bool) $this->getLastChoreLog();
    }

    private function getLastChoreLog()
    {
        return $this->choreLogs()->latest()->first();
    }

    public function choreLogs()
    {
        return $this->hasMany(ChoreLog::class)->orderBy('completed_at', 'desc');
    }

    public function lastChoreLog()
    {
        return $this->hasOne(ChoreLog::class)->latestOfMany('completed_at');
    }

    public function snoozes()
    {
        return $this->hasMany(ChoreSnooze::class);
    }

    public function activeSnooze()
    {
        return $this->hasOne(ChoreSnooze::class)->ofMany(
            ['snoozed_until' => 'max'],
            fn ($query) => $query->where('snoozed_until', '>', now())
        );
    }

    public function assignedUser()
    {
        return $this->belongsTo(User::class, 'assigned_user_id');
    }

    /**
     * Limit to chores that are unassigned or assigned to the given user.
     */
    public function scopeVisibleOnDashboardFor($query, $userId)
    {
        return $query->where(function ($q) use ($userId) {
            $q->whereNull('chores.assigned_user_id')
                ->orWhere('chores.assigned_user_id', $userId);
        });
    }

    /**
     * Check if this chore is snoozed for a specific user.
     */
    public function isSnoozedForUser($userId)
    {
        return $this->snoozes()
            ->where('user_id', $userId)
            ->where('snoozed_until', '>', now())
            ->exists();
    }

    /**
     * Get the active snooze for a specific user.
     */
    public function getActiveSnoozeForUser($userId)
    {
        return $this->snoozes()
            ->where('user_id', $userId)
            ->where('snoozed_until', '>', now())
            ->first();
    }

    public function getLastCompletedUser()
    {
        return $this->getLastChoreLog()?->user?->name;
    }

    public function getLastCompletedDate()
    {
        return $this->getLastChoreLog()?->completed_at;
    }

    public function hasOccurrences()
    {
        return $this->hasOccurrenceMonth() || $this->hasOccurrenceDay();
    }

    public function hasOccurrenceMonth()
    {
        return $this->getOccurrencesMonths() > 0;
    }

    public function getOccurrencesMonths()
    {
        return floor($this->occurrence_hours / 720);
    }

    public function hasOccurrenceDay()
    {
        return $this->getOccurrencesDays() > 0;
    }

    public function getOccurrencesDays()
    {
        return ($this->occurrence_hours - ($this->getOccurrencesMonths() * 720)) / 24;
    }
}
