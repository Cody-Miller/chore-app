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

    public function choreLogs()
    {
        return $this->hasMany(ChoreLog::class);
    }

    public static function getDue()
    {
        return self::whereDoesntHave('choreLogs')
            ->orWhereHas('choreLogs', function ($query) {
                $query->select('chore_id')
                    ->selectRaw('MAX(created_at) as max_created_at')
                    ->whereNull('deleted_at')
                    ->groupBy('chore_id')
                    ->havingRaw('max_created_at < DATE_SUB(NOW(), INTERVAL chores.occurrence_hours HOUR)');
            })->get();
    }

    public static function getUpcoming()
    {
        return self::whereDoesntHave('choreLogs')
            ->orWhereHas('choreLogs', function ($query) {
                $query->select('chore_id')
                    ->selectRaw('MAX(created_at) as max_created_at')
                    ->whereNull('deleted_at')
                    ->groupBy('chore_id')
                    ->havingRaw(
                        'MAX(created_at) BETWEEN DATE_SUB(NOW(), INTERVAL chores.occurrence_hours HOUR) AND DATE_ADD(NOW(), INTERVAL 72 HOUR)'
                    );
            })->get();
    }

    public static function getOneTime()
    {
        return self::whereDoesntHave('choreLogs')->where('occurrence_hours', 0)->get();
    }

    public function hasOccurrences()
    {
        return $this->hasOccurrenceMonth() || $this->hasOccurrenceDay();
    }

    public function hasOccurrenceMonth()
    {
        return $this->getOccurrencesMonths() > 1;
    }

    public function hasOccurrenceDay()
    {
        return $this->getOccurrencesDays() > 1;
    }

    public function getOccurrencesMonths()
    {
        return floor($this->occurrence_hours / 720);
    }

    public function getOccurrencesDays()
    {
        return ($this->occurrence_hours - ($this->getOccurrencesMonths() * 720)) / 24;
    }
}
