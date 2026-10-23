<?php

namespace App\Models;

use App\Models\Traits\StoresDatesAsUtc;
use App\Support\Timezone;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class ChoreLog extends Model
{
    use HasFactory, SoftDeletes, StoresDatesAsUtc;

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

    /**
     * Record a chore completion for a user, or a 50/50 split between two users.
     * Returns the created log(s).
     */
    public static function recordCompletion(Chore $chore, User $user, ?User $splitWith = null, ?Carbon $at = null): Collection
    {
        $completedAt = $at ?? now();

        if (!$splitWith) {
            return collect([static::create([
                'chore_id' => $chore->id,
                'user_id' => $user->id,
                'completed_at' => $completedAt,
                'weight_percentage' => 100.00,
                'is_split' => false,
            ])]);
        }

        // A shared ID links the two halves of a split
        $splitGroupId = Str::uuid();

        return collect([$user, $splitWith])->map(fn (User $splitUser) => static::create([
            'chore_id' => $chore->id,
            'user_id' => $splitUser->id,
            'completed_at' => $completedAt,
            'weight_percentage' => 50.00,
            'split_group_id' => $splitGroupId,
            'is_split' => true,
        ]));
    }

    public static function getChoreLogsWeightByUsers($startDate, $endDate)
    {
        return static::query()
            ->join('chores', 'chores.id', '=', 'chore_logs.chore_id')
            ->join('users', 'users.id', '=', 'chore_logs.user_id')
            ->whereBetween('completed_at', Timezone::utcRange($startDate, $endDate))
            ->selectRaw('users.name, SUM(chores.weight * COALESCE(chore_logs.weight_percentage, 100) / 100) AS total_weight')
            ->groupBy('users.id', 'users.name')
            ->get();
    }

    public static function getChoreLogsWeightByUsersAndWeekday($startDate, $endDate)
    {
        return static::totalsByUserAndWeekday($startDate, $endDate, 'total_weight', fn ($logs) => $logs->sum(
            fn ($log) => $log->weight * ($log->weight_percentage ?? 100) / 100
        ));
    }

    public static function getChoreLogsCountByUsersAndWeekday($startDate, $endDate)
    {
        return static::totalsByUserAndWeekday($startDate, $endDate, 'chore_count', fn ($logs) => $logs->count());
    }

    /**
     * One row per user per weekday (0 = Monday, in the viewer's timezone) with name, day and the total.
     * Grouped here rather than in SQL so weekdays follow the viewer's timezone.
     */
    private static function totalsByUserAndWeekday($startDate, $endDate, string $field, callable $total): Collection
    {
        $weekday = fn ($log) => $log->completed_at->copy()->setTimezone(Timezone::viewer())->dayOfWeekIso - 1;

        return static::query()
            ->join('chores', 'chores.id', '=', 'chore_logs.chore_id')
            ->join('users', 'users.id', '=', 'chore_logs.user_id')
            ->whereBetween('completed_at', Timezone::utcRange($startDate, $endDate))
            ->get(['users.id as user_id', 'users.name', 'chores.weight', 'chore_logs.weight_percentage', 'chore_logs.completed_at'])
            ->toBase()
            ->groupBy(fn ($log) => $log->user_id.'|'.$weekday($log))
            ->map(fn ($logs) => (object) [
                'name' => $logs->first()->name,
                'day' => $weekday($logs->first()),
                $field => $total($logs),
            ])
            ->values();
    }

    /**
     * Get chore completion counts for a date range (for frequency chart)
     */
    public static function getChoreCompletionCounts($startDate, $endDate)
    {
        return static::query()
            ->join('chores', 'chores.id', '=', 'chore_logs.chore_id')
            ->whereBetween('chore_logs.completed_at', Timezone::utcRange($startDate, $endDate))
            ->whereNull('chores.deleted_at')
            ->selectRaw('chores.name, COUNT(DISTINCT COALESCE(chore_logs.split_group_id, chore_logs.id)) AS completion_count')
            ->groupBy('chores.id', 'chores.name')
            ->orderByDesc('completion_count')
            ->limit(10)
            ->get();
    }

    /**
     * Get chores with completion rate vs expected (based on occurrence_hours)
     * Shows chores that are not being completed as frequently as they should
     * Includes all recurring chores even if they have no completions
     * Expected completions only count from when the chore was created
     * Split completions count once, snoozed chores and on-track chores are excluded
     */
    public static function getChoreCompletionRates($startDate, $endDate)
    {
        // Don't expect completions for time that hasn't happened yet
        [$startDate, $endDate] = Timezone::utcRange($startDate, $endDate->copy()->min(now()));
        $start = $startDate->format('Y-m-d H:i:s');
        $end = $endDate->format('Y-m-d H:i:s');
        $expected = 'FLOOR(TIMESTAMPDIFF(HOUR, GREATEST(?, chores.created_at), ?) / chores.occurrence_hours)';

        return \DB::table('chores')
            ->leftJoin('chore_logs', function($join) use ($startDate, $endDate) {
                $join->on('chores.id', '=', 'chore_logs.chore_id')
                    ->whereBetween('chore_logs.completed_at', Timezone::utcRange($startDate, $endDate))
                    ->whereNull('chore_logs.deleted_at');
            })
            ->whereNull('chores.deleted_at')
            ->whereNotExists(function ($q) {
                $q->select(\DB::raw(1))
                    ->from('chore_snoozes')
                    ->whereColumn('chore_snoozes.chore_id', 'chores.id')
                    ->where('chore_snoozes.snoozed_until', '>', now());
            })
            ->where('chores.occurrence_hours', '>', 0) // Only recurring chores
            ->selectRaw("
                chores.name,
                chores.occurrence_hours,
                COUNT(DISTINCT COALESCE(chore_logs.split_group_id, chore_logs.id)) AS actual_completions,
                {$expected} AS expected_completions,
                (COUNT(DISTINCT COALESCE(chore_logs.split_group_id, chore_logs.id)) / {$expected} * 100) AS completion_rate
            ", [$start, $end, $start, $end])
            ->groupBy('chores.id', 'chores.name', 'chores.occurrence_hours', 'chores.created_at')
            ->havingRaw('expected_completions > 0 AND completion_rate < 100')
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
