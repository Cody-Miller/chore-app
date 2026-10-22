<?php

namespace App\Models;

use App\Models\Traits\HasTags;
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
        'notifications_enabled',
    ];

    protected $casts = [
        'notifications_enabled' => 'boolean',
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
        $query->notSnoozed();

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
        $query->notSnoozed();

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
     * Chores due today or overdue, for the morning reminder. Snoozed chores are left out,
     * and one-time chores count until they have been completed once.
     */
    public static function getDueToday()
    {
        // Compare against the app's date rather than MySQL's NOW(), which may be in another timezone
        $today = now()->toDateString();

        $recurring = self::select('chores.*')
            ->with('assignedUser')
            ->leftJoin('chore_logs', function ($join) {
                $join->on('chores.id', '=', 'chore_logs.chore_id')
                    ->whereNull('chore_logs.deleted_at');
            })
            ->whereNull('chores.deleted_at')
            ->where('chores.occurrence_hours', '!=', 0)
            ->notSnoozed()
            ->groupBy('chores.id')
            ->havingRaw(
                'MAX(chore_logs.completed_at) IS NULL OR DATE(DATE_ADD(MAX(chore_logs.completed_at), INTERVAL chores.occurrence_hours HOUR)) <= ?',
                [$today]
            )
            ->get();

        $oneTime = self::with('assignedUser')
            ->where('occurrence_hours', 0)
            ->notSnoozed()
            ->whereDoesntHave('choreLogs')
            ->get();

        return $recurring->merge($oneTime);
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

    public function snoozes()
    {
        return $this->hasMany(ChoreSnooze::class);
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
     * Limit to chores without an active snooze.
     */
    public function scopeNotSnoozed($query)
    {
        return $query->whereNotExists(function ($q) {
            $q->select(\DB::raw(1))
                ->from('chore_snoozes')
                ->whereColumn('chore_snoozes.chore_id', 'chores.id')
                ->where('chore_snoozes.snoozed_until', '>', now());
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
