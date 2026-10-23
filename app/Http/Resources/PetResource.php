<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PetResource extends JsonResource
{
    /**
     * Relations to eager load before serializing pets with their pills.
     */
    const RELATIONS = ['activePills.tags', 'activePills.lastPillLog.user'];

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'species' => $this->species,
            'breed' => $this->breed,
            'birth_date' => $this->birth_date?->toDateString(),
            'age' => $this->birth_date ? (int) floor($this->getAge()) : null,
            'notes' => $this->notes,
            'photo_url' => $this->photo_path ? asset('storage/'.$this->photo_path) : null,
            'pills' => PillResource::collection($this->whenLoaded('activePills', fn () => $this->activePills->sortBy('name')->values())),
        ];
    }
}
