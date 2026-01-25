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
     */
    public static function getMissedDosesByPill($startDate, $endDate)
    {
        $pills = Pill::whereNull('deleted_at')->with('pet')->get();
        $results = [];

        $period = CarbonPeriod::create($startDate, $endDate);
        $now = now();

        foreach ($pills as $pill) {
            foreach ($period as $date) {
                $missedCount = 0;

                // Calculate effective day boundaries for this date
                $effectiveDayStart = static::getEffectiveDayStart($date);
                $effectiveDayEnd = static::getEffectiveDayEnd($date);

                foreach ($pill->scheduled_times ?? [] as $scheduledTime) {
                    // Only count as missed if the time has passed
                    $scheduledDateTime = $date->copy()->setTimeFromTimeString($scheduledTime);
                    if ($scheduledDateTime->lt($now)) {
                        $exists = static::where('pill_id', $pill->id)
                            ->where('administered_at', '>=', $effectiveDayStart)
                            ->where('administered_at', '<=', $effectiveDayEnd)
                            ->where('scheduled_time', $scheduledTime)
                            ->whereNull('deleted_at')
                            ->exists();
                        if (! $exists) {
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
