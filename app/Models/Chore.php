<?php

namespace App\Models;

use App\Models\Traits\HasTags;
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

    public static function getDue($userId = null, $showAll = true)
    {
        $query = self::select('chores.*')
            ->with(['assignedUser', 'tags'])
            ->leftJoin('chore_logs', function ($join) {
                $join->on('chores.id', '=', 'chore_logs.chore_id')
                    ->whereNull('chore_logs.deleted_at');
            })
            ->whereNull('chores.deleted_at')
            ->where('chores.occurrence_hours', '!=', 0)
            ->groupBy('chores.id')
            ->havingRaw(
                'DATE(NOW()) > DATE(DATE_ADD(MAX(chore_logs.completed_at), INTERVAL chores.occurrence_hours HOUR)) OR MAX(chore_logs.completed_at) IS NULL'
            );

        // Filter out globally snoozed chores
        $query->whereNotExists(function ($q) {
            $q->select(\DB::raw(1))
                ->from('chore_snoozes')
                ->whereColumn('chore_snoozes.chore_id', 'chores.id')
                ->where('chore_snoozes.snoozed_until', '>', now());
        });

        if (!$showAll && $userId) {
            $query->visibleOnDashboardFor($userId);
        }

        return $query->get();
    }

    public static function getUpcoming($userId = null, $showAll = true)
    {
        $query = self::select('chores.*')
            ->with(['assignedUser', 'tags'])
            ->leftJoin('chore_logs', function ($join) {
                $join->on('chores.id', '=', 'chore_logs.chore_id')
                    ->whereNull('chore_logs.deleted_at');
            })
            ->whereNull('chores.deleted_at')
            ->where('chores.occurrence_hours', '!=', 0)
            ->groupBy('chores.id')
            ->havingRaw(
                'DATE_ADD(DATE(NOW()), INTERVAL 72 HOUR) >= DATE_ADD(DATE(MAX(chore_logs.completed_at)), INTERVAL chores.occurrence_hours HOUR) AND DATE(NOW()) <= DATE_ADD(DATE(MAX(chore_logs.completed_at)), INTERVAL chores.occurrence_hours HOUR)'
            );

        // Filter out globally snoozed chores
        $query->whereNotExists(function ($q) {
            $q->select(\DB::raw(1))
                ->from('chore_snoozes')
                ->whereColumn('chore_snoozes.chore_id', 'chores.id')
                ->where('chore_snoozes.snoozed_until', '>', now());
        });

        if (!$showAll && $userId) {
            $query->visibleOnDashboardFor($userId);
        }

        return $query->get();
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

        return $lastCompleted->copy()->addHours($this->occurrence_hours);
    }

    /**
     * Which dashboard list this chore belongs to: snoozed, one_time, due, upcoming or scheduled.
     * Mirrors the date math in getDue() and getUpcoming().
     */
    public function getStatus(): string
    {
        if ($this->activeSnooze) {
            return 'snoozed';
        }
        if ($this->occurrence_hours == 0) {
            return 'one_time';
        }

        $lastCompleted = $this->lastChoreLog?->completed_at;
        $today = now()->startOfDay();

        if (!$lastCompleted || $today->gt($lastCompleted->copy()->addHours($this->occurrence_hours)->startOfDay())) {
            return 'due';
        }

        $upcomingAt = $lastCompleted->copy()->startOfDay()->addHours($this->occurrence_hours);
        if ($today->copy()->addHours(72)->gte($upcomingAt) && $today->lte($upcomingAt)) {
            return 'upcoming';
        }

        return 'scheduled';
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
