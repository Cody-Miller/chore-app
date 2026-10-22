<?php

namespace App\Models\Traits;

use App\Models\Tag;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Tags from the shared tags table, kept in a separate list per model
 * (chore tags and pill tags never mix). Expects a "<model>_tag" pivot table.
 */
trait HasTags
{
    public function tags()
    {
        return $this->belongsToMany(Tag::class)->orderBy('name');
    }

    /**
     * The tag type for this model, e.g. "chore" or "pill".
     */
    public static function tagType(): string
    {
        return Str::snake(class_basename(static::class));
    }

    /**
     * All tags of this model's type, for the tag input's suggestions and colors.
     */
    public static function tagOptions(): Collection
    {
        return Tag::ofType(static::tagType())->orderBy('name')->get(['name', 'color']);
    }

    /**
     * Replace this model's tags with the given names, creating any new ones.
     */
    public function syncTagNames(array $names): void
    {
        $this->tags()->sync(Tag::findOrCreateByNames($names, static::tagType())->pluck('id'));
    }
}
