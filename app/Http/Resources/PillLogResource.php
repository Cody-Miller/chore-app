<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PillLogResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'pet' => $this->pill?->pet?->name,
            'pill' => $this->pill?->name,
            'pill_slug' => $this->pill?->slug,
            'dosage' => $this->pill?->dosage,
            'scheduled_time' => $this->scheduled_time,
            'administered_at' => $this->administered_at->toIso8601String(),
            'given_by' => $this->user?->first_name,
            'notes' => $this->notes,
        ];
    }
}
