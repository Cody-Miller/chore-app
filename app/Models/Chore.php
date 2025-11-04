<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Chore extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'weight',
        'occurrence_hours'
    ];

    /**
     * Get the route key for the model.
     */
    public function getRouteKeyName()
    {
        return 'slug';
    }

    public static function getDue($userId = null)
    {
        $query = self::select('chores.*')
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

        // Filter out snoozed chores for the user
        if ($userId) {
            $query->whereNotExists(function ($q) use ($userId) {
                $q->select(\DB::raw(1))
                    ->from('chore_snoozes')
                    ->whereColumn('chore_snoozes.chore_id', 'chores.id')
                    ->where('chore_snoozes.user_id', $userId)
                    ->where('chore_snoozes.snoozed_until', '>', now());
            });
        }

        return $query->get();
    }

    public static function getUpcoming($userId = null)
    {
        $query = self::select('chores.*')
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

        // Filter out snoozed chores for the user
        if ($userId) {
            $query->whereNotExists(function ($q) use ($userId) {
                $q->select(\DB::raw(1))
                    ->from('chore_snoozes')
                    ->whereColumn('chore_snoozes.chore_id', 'chores.id')
                    ->where('chore_snoozes.user_id', $userId)
                    ->where('chore_snoozes.snoozed_until', '>', now());
            });
        }

        return $query->get();
    }

    public static function getOneTime($userId = null)
    {
        $query = self::whereDoesntHave('choreLogs')->where('occurrence_hours', 0);

        // Filter out snoozed chores for the user
        if ($userId) {
            $query->whereDoesntHave('snoozes', function ($q) use ($userId) {
                $q->where('user_id', $userId)
                    ->where('snoozed_until', '>', now());
            });
        }

        return $query->get();
    }

    public function hasCompleted()
    {
        return (bool)$this->getLastChoreLog();
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
