<?php

namespace App\Models;

use App\Models\Traits\HasEffectiveDate;
use Carbon\CarbonPeriod;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PillLog extends Model
{
    use HasEffectiveDate, HasFactory, SoftDeletes;

    protected $fillable = [
        'pill_id',
        'user_id',
        'administered_at',
        'scheduled_time',
        'notes',
    ];

    protected $casts = [
        'administered_at' => 'datetime',
    ];

    /**
     * Get the pill this log belongs to.
     */
    public function pill()
    {
        return $this->belongsTo(Pill::class);
    }

    /**
     * Get the user who administered this pill.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get pill administrations by users for a date range.
     */
    public static function getPillAdministrationsByUsers($startDate, $endDate)
    {
        return static::query()
            ->join('users', 'users.id', '=', 'pill_logs.user_id')
            ->whereBetween('administered_at', [$startDate, $endDate])
            ->whereNull('pill_logs.deleted_at')
            ->selectRaw('users.name, COUNT(*) AS pill_count')
            ->groupBy('users.id', 'users.name')
            ->get();
    }

    /**
     * Get pill administrations by pet for a date range.
     */
    public static function getPillAdministrationsByPet($startDate, $endDate)
    {
        return static::query()
            ->join('pills', 'pills.id', '=', 'pill_logs.pill_id')
            ->join('pets', 'pets.id', '=', 'pills.pet_id')
            ->whereBetween('administered_at', [$startDate, $endDate])
            ->whereNull('pill_logs.deleted_at')
            ->whereNull('pills.deleted_at')
            ->whereNull('pets.deleted_at')
            ->selectRaw('pets.name, COUNT(*) AS pill_count')
            ->groupBy('pets.id', 'pets.name')
            ->get();
    }

    /**
     * Get missed doses by pill for a date range.
     * A dose is missed if its scheduled time has passed but no log exists.
     * Doses scheduled before the pill was created are not counted.
     */
    public static function getMissedDosesByPill($startDate, $endDate)
    {
        $pills = Pill::whereNull('deleted_at')->active()->with('pet')->get();
        $results = [];

        $period = CarbonPeriod::create($startDate, $endDate);
        $now = now();

        // Load every log in the range once, keyed by pill / effective date / scheduled time
        $administered = static::whereIn('pill_id', $pills->pluck('id'))
            ->where('administered_at', '>=', static::getEffectiveDayStart($startDate->copy()))
            ->where('administered_at', '<=', static::getEffectiveDayEnd($endDate->copy()))
            ->get(['pill_id', 'administered_at', 'scheduled_time'])
            ->mapWithKeys(fn ($log) => [
                $log->pill_id.'|'.static::getEffectiveDate($log->administered_at)->format('Y-m-d').'|'.$log->scheduled_time => true,
            ]);

        foreach ($pills as $pill) {
            foreach ($period as $date) {
                $missedCount = 0;

                foreach ($pill->scheduled_times ?? [] as $scheduledTime) {
                    // Times before the cutoff belong to the next calendar day
                    $scheduledDateTime = $date->copy()->setTimeFromTimeString($scheduledTime);
                    if ($scheduledDateTime->hour < static::DAY_CUTOFF_HOUR) {
                        $scheduledDateTime->addDay();
                    }

                    // Only count as missed if the time has passed and the pill existed
                    if ($scheduledDateTime->lt($now) && $scheduledDateTime->gte($pill->created_at)) {
                        $key = $pill->id.'|'.$date->format('Y-m-d').'|'.$scheduledTime;
                        if (! isset($administered[$key])) {
                            $missedCount++;
                        }
                    }
                }
                if ($missedCount > 0) {
                    $results[] = [
                        'pill_id' => $pill->id,
                        'pill_name' => $pill->name,
                        'pet_name' => $pill->pet?->name ?? 'Unknown',
                        'date' => $date->format('Y-m-d'),
                        'missed_count' => $missedCount,
                    ];
                }
            }
        }

        return collect($results);
    }
}
