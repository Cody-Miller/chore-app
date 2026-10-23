<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ChoreLogResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'chore' => $this->chore?->name,
            'chore_slug' => $this->chore?->slug,
            'completed_at' => $this->completed_at->toIso8601String(),
            'completed_by' => $this->user?->first_name,
            'weight_percentage' => (float) $this->weight_percentage,
            'split_group_id' => $this->split_group_id,
        ];
    }
}
