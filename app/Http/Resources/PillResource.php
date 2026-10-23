<?php

namespace App\Http\Resources;

use App\Models\Pill;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;

class PillResource extends JsonResource
{
    /**
     * Relations to eager load before serializing pills.
     */
    const RELATIONS = ['pet', 'tags', 'lastPillLog.user'];

    public function toArray(Request $request): array
    {
        $slots = $this->doseSlots();
        $next = $slots->firstWhere('status', 'due') ?? $slots->firstWhere('status', 'upcoming');

        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'dosage' => $this->dosage,
            'description' => $this->description,
            'active' => $this->is_active,
            'pet' => $this->whenLoaded('pet', fn () => $this->pet?->name),
            'pet_slug' => $this->whenLoaded('pet', fn () => $this->pet?->slug),
            'scheduled_times' => $slots->pluck('time'),
            'doses' => $slots->map(fn (array $slot) => static::dose($slot)),
            'completed_today' => $slots->where('status', 'given')->pluck('time')->values(),
            'next_scheduled' => $next['time'] ?? null,
            'tags' => $this->whenLoaded('tags', fn () => $this->tags->pluck('name')),
            'last_given_at' => $this->lastPillLog?->administered_at?->toIso8601String(),
            'last_given_by' => $this->lastPillLog?->user?->first_name,
            'recent_logs' => PillLogResource::collection($this->whenLoaded('pillLogs')),
        ];
    }

    /**
     * One dose slot from Pill::doseSlots(). With a pill, it also says whose dose it is.
     */
    public static function dose(array $slot, ?Pill $pill = null): array
    {
        $whose = $pill ? [
            'pet' => $pill->pet?->name,
            'pet_slug' => $pill->pet?->slug,
            'pill' => $pill->name,
            'pill_slug' => $pill->slug,
            'dosage' => $pill->dosage,
        ] : [];

        return $whose + [
            'time' => $slot['time'],
            'label' => $slot['label'],
            'scheduled_at' => $slot['at']->toIso8601String(),
            'status' => $slot['status'],
            'given_at' => $slot['log']?->administered_at->toIso8601String(),
            'given_by' => $slot['log']?->user?->first_name,
        ];
    }

    /**
     * Today's doses across every active pill, in time order, optionally only one status.
     */
    public static function doses(?string $status = null): Collection
    {
        return Pill::active()
            ->whereHas('pet')
            ->with('pet')
            ->get()
            ->flatMap(fn (Pill $pill) => $pill->doseSlots()
                ->when($status, fn ($slots) => $slots->where('status', $status))
                ->map(fn (array $slot) => static::dose($slot, $pill)))
            ->sortBy('scheduled_at')
            ->values();
    }
}
