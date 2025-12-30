<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PillLog extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'pill_id',
        'user_id',
        'administered_at',
        'scheduled_time',
        'notes'
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
}
