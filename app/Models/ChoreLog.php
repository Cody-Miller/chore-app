<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ChoreLog extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'chore_id',
        'user_id',
        'completed_at',
        'weight_percentage',
        'split_group_id',
        'is_split'
    ];

    protected $casts = [
        'completed_at' => 'datetime',
    ];

    public function chore()
    {
        return $this->belongsTo(Chore::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public static function getChoreLogsWeightByUsers($startDate, $endDate)
    {
        return static::query()
            ->join('chores', 'chores.id', '=', 'chore_logs.chore_id')
            ->join('users', 'users.id', '=', 'chore_logs.user_id')
            ->whereBetween('completed_at', [$startDate, $endDate])
            ->selectRaw('users.name, SUM(chores.weight * COALESCE(chore_logs.weight_percentage, 100) / 100) AS total_weight')
            ->groupBy('users.id', 'users.name')
            ->get();
    }

    public static function getChoreLogsWeightByUsersAndWeekday($startDate, $endDate)
    {
        return static::query()
            ->join('chores', 'chores.id', '=', 'chore_logs.chore_id')
            ->join('users', 'users.id', '=', 'chore_logs.user_id')
            ->whereBetween('completed_at', [$startDate, $endDate])
            ->selectRaw('users.name, SUM(chores.weight * COALESCE(chore_logs.weight_percentage, 100) / 100) AS total_weight, WEEKDAY(completed_at) AS day')
            ->groupByRaw('users.id, WEEKDAY(completed_at)')
            ->get();
    }

    public static function getChoreLogsCountByUsersAndWeekday($startDate, $endDate)
    {
        return static::query()
            ->join('users', 'users.id', '=', 'chore_logs.user_id')
            ->whereBetween('completed_at', [$startDate, $endDate])
            ->selectRaw('users.name, COUNT(*) AS chore_count, WEEKDAY(completed_at) AS day')
            ->groupByRaw('users.id, WEEKDAY(completed_at)')
            ->get();
    }

    /**
     * Get chore completion counts for a date range (for frequency chart)
     */
    public static function getChoreCompletionCounts($startDate, $endDate)
    {
        return static::query()
            ->join('chores', 'chores.id', '=', 'chore_logs.chore_id')
            ->whereBetween('chore_logs.completed_at', [$startDate, $endDate])
            ->whereNull('chores.deleted_at')
            ->selectRaw('chores.name, COUNT(*) AS completion_count')
            ->groupBy('chores.id', 'chores.name')
            ->orderByDesc('completion_count')
            ->limit(10)
            ->get();
    }

    /**
     * Get chores with completion rate vs expected (based on occurrence_hours)
     * Shows chores that are not being completed as frequently as they should
     * Includes all recurring chores even if they have no completions
     * Excludes snoozed chores
     */
    public static function getChoreCompletionRates($startDate, $endDate)
    {
        $daysDiff = $startDate->diffInDays($endDate);

        return \DB::table('chores')
            ->leftJoin('chore_logs', function($join) use ($startDate, $endDate) {
                $join->on('chores.id', '=', 'chore_logs.chore_id')
                    ->whereBetween('chore_logs.completed_at', [$startDate, $endDate])
                    ->whereNull('chore_logs.deleted_at');
            })
            ->leftJoin('chore_snoozes', function($join) {
                $join->on('chores.id', '=', 'chore_snoozes.chore_id')
                    ->where('chore_snoozes.snoozed_until', '>', now());
            })
            ->whereNull('chores.deleted_at')
            ->whereNull('chore_snoozes.id') // Exclude snoozed chores
            ->where('chores.occurrence_hours', '>', 0) // Only recurring chores
            ->selectRaw('
                chores.name,
                chores.occurrence_hours,
                COUNT(chore_logs.id) AS actual_completions,
                FLOOR(? / (chores.occurrence_hours / 24)) AS expected_completions,
                (COUNT(chore_logs.id) / FLOOR(? / (chores.occurrence_hours / 24)) * 100) AS completion_rate
            ', [$daysDiff, $daysDiff])
            ->groupBy('chores.id', 'chores.name', 'chores.occurrence_hours')
            ->havingRaw('FLOOR(? / (chores.occurrence_hours / 24)) > 0', [$daysDiff])
            ->orderBy('completion_rate', 'asc')
            ->limit(10)
            ->get();
    }

    /**
     * Get the split partner for this chore log (if it's a split completion)
     */
    public function splitPartner()
    {
        if (!$this->is_split || !$this->split_group_id) {
            return null;
        }

        return static::where('split_group_id', $this->split_group_id)
            ->where('id', '!=', $this->id)
            ->with('user')
            ->first();
    }

}
