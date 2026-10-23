<?php

namespace App\Http\Resources;

use App\Support\Speech;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ChoreResource extends JsonResource
{
    /**
     * Relations to eager load before serializing chores.
     */
    const RELATIONS = ['assignedUser', 'tags', 'lastChoreLog.user', 'activeSnooze'];

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'weight' => $this->weight,
            'status' => $this->getStatus(),
            'repeats' => $this->repeatsLabel(),
            'occurrence_hours' => $this->occurrence_hours,
            'assigned_to' => $this->assignedUser?->first_name,
            'tags' => $this->tags->pluck('name'),
            'last_completed_at' => $this->lastChoreLog?->completed_at?->toIso8601String(),
            'last_completed_by' => $this->lastChoreLog?->user?->first_name,
            'next_due_at' => $this->getNextDueAt()?->toIso8601String(),
            'snoozed_until' => $this->activeSnooze?->snoozed_until?->toIso8601String(),
            'url' => route('chores.show', $this->slug),
            'recent_logs' => ChoreLogResource::collection($this->whenLoaded('choreLogs')),
        ];
    }

    /**
     * "every 3 days", "every 1 month and 2 days" or "one time".
     */
    private function repeatsLabel(): string
    {
        if ($this->occurrence_hours == 0) {
            return 'one time';
        }

        $parts = [];
        if ($months = (int) $this->getOccurrencesMonths()) {
            $parts[] = Speech::count($months, 'month');
        }
        if ($days = (int) $this->getOccurrencesDays()) {
            $parts[] = Speech::count($days, 'day');
        }

        return 'every '.implode(' and ', $parts);
    }
}
