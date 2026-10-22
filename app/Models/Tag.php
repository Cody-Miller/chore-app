<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class Tag extends Model
{
    // Pastels ordered so neighbouring entries contrast (Tailwind 300 shades)
    const PALETTE = [
        '#fca5a5', // red
        '#93c5fd', // blue
        '#fde68a', // amber
        '#c4b5fd', // violet
        '#86efac', // green
        '#f9a8d4', // pink
        '#67e8f9', // cyan
        '#fdba74', // orange
        '#a5b4fc', // indigo
        '#bef264', // lime
        '#f0abfc', // fuchsia
        '#5eead4', // teal
    ];

    protected $fillable = [
        'name',
        'color',
    ];

    protected static function booted()
    {
        static::creating(function (Tag $tag) {
            $tag->color ??= self::nextColor();
        });
    }

    public function chores()
    {
        return $this->belongsToMany(Chore::class);
    }

    /**
     * Find tags by name (case-insensitive), creating any that don't exist yet.
     */
    public static function findOrCreateByNames(array $names): Collection
    {
        return collect($names)
            ->map(fn ($name) => trim((string) $name))
            ->filter()
            ->unique(fn ($name) => mb_strtolower($name))
            ->map(fn ($name) => self::firstOrCreate(['name' => $name]))
            ->values();
    }

    /**
     * The palette color used by the fewest tags, so tags stay visually distinct.
     * Ties go to the earliest color in the palette.
     */
    public static function nextColor(): string
    {
        $counts = self::pluck('color')->map(fn ($color) => strtolower($color))->countBy();

        return collect(self::PALETTE)
            ->sortBy(fn ($color, $index) => ($counts[$color] ?? 0) * count(self::PALETTE) + $index)
            ->first();
    }
}
