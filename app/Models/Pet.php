<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Pet extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'species',
        'breed',
        'birth_date',
        'photo_path',
        'notes'
    ];

    protected $casts = [
        'birth_date' => 'date',
    ];

    /**
     * Get the route key for the model.
     */
    public function getRouteKeyName()
    {
        return 'slug';
    }

    /**
     * Get all pills for this pet.
     */
    public function pills()
    {
        return $this->hasMany(Pill::class);
    }

    /**
     * Get all active pills for this pet.
     */
    public function activePills()
    {
        return $this->pills()->whereNull('deleted_at');
    }

    /**
     * Calculate pet's age in years.
     */
    public function getAge()
    {
        return $this->birth_date ?
            $this->birth_date->diffInYears(now()) : null;
    }

    /**
     * Get all pets with pill counts.
     */
    public static function getAllWithPillCounts()
    {
        return static::withCount('pills')
            ->orderBy('name')
            ->get();
    }
}
