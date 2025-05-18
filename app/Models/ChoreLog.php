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
        'completed_at'
    ];

    public array $dates = ['completed_at'];

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
            ->selectRaw('users.name, SUM(chores.weight) AS total_weight')
            ->groupBy('users.id', 'users.name')
            ->get();
    }

    public static function getChoreLogsWeightByUsersAndWeekday($startDate, $endDate)
    {
        return static::query()
            ->join('chores', 'chores.id', '=', 'chore_logs.chore_id')
            ->join('users', 'users.id', '=', 'chore_logs.user_id')
            ->whereBetween('completed_at', [$startDate, $endDate])
            ->selectRaw('users.name, SUM(chores.weight) AS total_weight, WEEKDAY(completed_at) AS day')
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

}
