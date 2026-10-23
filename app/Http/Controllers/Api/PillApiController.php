<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\PillLogResource;
use App\Http\Resources\PillResource;
use App\Models\Pill;
use App\Services\Api\NameResolver;
use App\Services\MedicationGiver;
use App\Support\Speech;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class PillApiController extends ApiController
{
    /**
     * GET /api/pills?pet=&tag=&include_inactive=
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'pet' => ['nullable', 'string'],
            'tag' => ['nullable', 'string'],
        ]);

        $pet = $request->filled('pet') ? NameResolver::pet($request->query('pet')) : null;
        $tag = $request->query('tag');

        $pills = Pill::with(PillResource::RELATIONS)
            ->whereHas('pet')
            ->when(!$request->boolean('include_inactive'), fn ($q) => $q->active())
            ->when($pet, fn ($q) => $q->where('pet_id', $pet->id))
            ->when($tag, fn ($q) => $q->whereHas('tags', fn ($t) => $t->where('name', $tag)))
            ->orderBy('name')
            ->get();

        return $this->ok(PillResource::collection($pills));
    }

    /**
     * GET /api/pills/due
     * Doses whose time has passed today but haven't been given.
     */
    public function due(): JsonResponse
    {
        $doses = PillResource::doses('due');

        return $this->ok($doses, Speech::summarize($doses->map(fn ($dose) => $this->spokenDose($dose)), 'medication dose', 'due'));
    }

    /**
     * GET /api/pills/upcoming
     * Doses still to come later today.
     */
    public function upcoming(): JsonResponse
    {
        $doses = PillResource::doses('upcoming');

        return $this->ok($doses, Speech::summarize($doses->map(fn ($dose) => $this->spokenDose($dose)), 'medication dose', 'coming up later today'));
    }

    /**
     * GET /api/pills/today
     * Every dose today with its status and who gave it.
     */
    public function today(): JsonResponse
    {
        $doses = PillResource::doses();

        return $this->ok($doses, $this->todayMessage($doses));
    }

    /**
     * GET /api/pills/{pill}
     */
    public function show(string $pill): JsonResponse
    {
        $pill = NameResolver::pill($pill)->load(PillResource::RELATIONS);
        $pill->setRelation('pillLogs', $pill->pillLogs()->with('user')->limit(10)->get()->each->setRelation('pill', $pill));

        return $this->ok(new PillResource($pill));
    }

    /**
     * POST /api/pills/give {"pet": "kage", "pill": "metacam"} or POST /api/pills/{pill}/give
     * Also POST /api/record-medication, kept for older callers.
     * Optional: time, early, by, notes, at, dry_run.
     */
    public function give(Request $request, ?string $pill = null): JsonResponse
    {
        $request->validate([
            'pet' => ['nullable', 'string'],
            'pill' => ['nullable', 'string'],
            'time' => ['nullable', 'string'],
            'by' => ['nullable', 'string'],
            'notes' => ['nullable', 'string', 'max:500'],
            'at' => ['nullable', 'string'],
        ]);

        $pill = $pill !== null
            ? NameResolver::pill($pill)
            : MedicationGiver::choosePill($request->input('pet'), $request->input('pill'));
        $user = NameResolver::user($request->input('by'));
        $at = $this->actionTime($request);
        $slot = MedicationGiver::chooseSlot($pill, $at, $request->input('time'), $request->boolean('early'));

        $early = $slot['status'] === 'upcoming';
        $dose = Speech::possessive($pill->pet->name)." {$slot['label']} dose of {$pill->name}"
            .($early ? ' early' : '')
            .($user->isApiUser() ? '' : ", given by {$user->first_name}");
        $data = [
            'dry_run' => $request->boolean('dry_run'),
            'pet' => $pill->pet->name,
            'pet_slug' => $pill->pet->slug,
            'pill' => $pill->name,
            'pill_slug' => $pill->slug,
            'dosage' => $pill->dosage,
            'scheduled_time' => $slot['time'],
            'scheduled_label' => $slot['label'],
            'early' => $early,
            'administered_at' => $at->toIso8601String(),
            'administered_by' => $user->first_name,
        ];

        if ($request->boolean('dry_run')) {
            return $this->ok($data, "Would log {$dose}.");
        }

        $log = MedicationGiver::give($pill, $slot, $user, $at, $request->input('notes'));
        $remaining = $pill->doseSlots($at)->where('status', '!=', 'given');

        return $this->ok(
            ['log_id' => $log->id] + $data + ['remaining_today' => $remaining->pluck('time')->values()],
            "Logged {$dose}.",
            201
        );
    }

    /**
     * "Kage's Metacam at 8:00 AM".
     */
    private function spokenDose(array $dose): string
    {
        return Speech::possessive($dose['pet'])." {$dose['pill']} at {$dose['label']}";
    }

    /**
     * "3 of 5 medication doses have been given today. Still due: Kage's Metacam at 8:00 AM."
     */
    private function todayMessage(Collection $doses): string
    {
        if ($doses->isEmpty()) {
            return 'No medication doses are scheduled today.';
        }

        $given = $doses->where('status', 'given')->count();
        $due = $doses->where('status', 'due');
        $message = "{$given} of ".Speech::count($doses->count(), 'medication dose').' '.($given === 1 ? 'has' : 'have').' been given today.';

        if ($due->isNotEmpty()) {
            $message .= ' Still due: '.Speech::list($due->map(fn ($dose) => $this->spokenDose($dose))).'.';
        }

        return $message;
    }
}
