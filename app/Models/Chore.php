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

    public static function getDue()
    {
        return self::select('chores.*')
            ->leftJoin('chore_logs', function ($join) {
                $join->on('chores.id', '=', 'chore_logs.chore_id')
                    ->whereNull('chore_logs.deleted_at');
            })
            ->whereNull('chores.deleted_at')
            ->where('chores.occurrence_hours', '!=', 0)
            ->groupBy('chores.id')
            ->havingRaw(
                'NOW() >= DATE_ADD(MAX(chore_logs.completed_at), INTERVAL chores.occurrence_hours HOUR) OR MAX(chore_logs.completed_at) IS NULL'
            )
            ->get();
    }

    public static function getUpcoming()
    {
        return self::select('chores.*')
            ->leftJoin('chore_logs', function ($join) {
                $join->on('chores.id', '=', 'chore_logs.chore_id')
                    ->whereNull('chore_logs.deleted_at');
            })
            ->whereNull('chores.deleted_at')
            ->where('chores.occurrence_hours', '!=', 0)
            ->groupBy('chores.id')
            ->havingRaw(
                'DATE_ADD(NOW(), INTERVAL 72 HOUR) >= DATE_ADD(MAX(chore_logs.completed_at), INTERVAL chores.occurrence_hours HOUR) AND NOW() < DATE_ADD(MAX(chore_logs.completed_at), INTERVAL chores.occurrence_hours HOUR)'
            )
            ->get();
    }

    public static function getOneTime()
    {
        return self::whereDoesntHave('choreLogs')->where('occurrence_hours', 0)->get();
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
        return $this->getOccurrencesMonths() > 1;
    }

    public function getOccurrencesMonths()
    {
        return floor($this->occurrence_hours / 720);
    }

    public function hasOccurrenceDay()
    {
        return $this->getOccurrencesDays() > 1;
    }

    public function getOccurrencesDays()
    {
        return ($this->occurrence_hours - ($this->getOccurrencesMonths() * 720)) / 24;
    }
}
