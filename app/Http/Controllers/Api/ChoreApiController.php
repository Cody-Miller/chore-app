<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\ApiException;
use App\Http\Resources\ChoreLogResource;
use App\Http\Resources\ChoreResource;
use App\Models\Chore;
use App\Models\ChoreLog;
use App\Services\Api\NameResolver;
use App\Support\Speech;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class ChoreApiController extends ApiController
{
    /**
     * Hours per unit for snooze durations like "3 days" or "a week" (matching ChoreSnooze::getSnoozeOptions()).
     */
    const DURATION_HOURS = ['hour' => 1, 'day' => 24, 'week' => 168, 'month' => 672, 'year' => 8064];

    /**
     * GET /api/chores?tag=&assigned=&occurrence=
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'tag' => ['nullable', 'string'],
            'assigned' => ['nullable', 'string'],
            'occurrence' => ['nullable', 'in:recurring,one-time'],
        ]);

        $occurrence = $request->query('occurrence');
        $assigned = $request->query('assigned');
        $tag = $request->query('tag');
        $assignedUser = $assigned && !in_array($assigned, ['everyone', 'assigned']) ? NameResolver::user($assigned) : null;

        $chores = Chore::with(ChoreResource::RELATIONS)
            ->when($occurrence === 'recurring', fn ($q) => $q->where('occurrence_hours', '>', 0))
            ->when($occurrence === 'one-time', fn ($q) => $q->where('occurrence_hours', 0))
            ->when($assigned === 'everyone', fn ($q) => $q->whereNull('assigned_user_id'))
            ->when($assigned === 'assigned', fn ($q) => $q->whereNotNull('assigned_user_id'))
            ->when($assignedUser, fn ($q) => $q->where('assigned_user_id', $assignedUser->id))
            ->when($tag, fn ($q) => $q->whereHas('tags', fn ($t) => $t->where('name', $tag)))
            ->orderBy('name')
            ->get();

        return $this->ok(ChoreResource::collection($chores));
    }

    /**
     * GET /api/chores/due?for=
     */
    public function due(Request $request): JsonResponse
    {
        return $this->choreList(Chore::getDue(...$this->dashboardScope($request)), 'chore', 'due');
    }

    /**
     * GET /api/chores/upcoming?for=
     */
    public function upcoming(Request $request): JsonResponse
    {
        return $this->choreList(Chore::getUpcoming(...$this->dashboardScope($request)), 'chore', 'coming up');
    }

    /**
     * GET /api/chores/one-time?for=
     */
    public function oneTime(Request $request): JsonResponse
    {
        return $this->choreList(Chore::getOneTime(...$this->dashboardScope($request)), 'one-time chore', 'on the list');
    }

    /**
     * GET /api/chores/snoozed
     */
    public function snoozed(): JsonResponse
    {
        return $this->choreList(Chore::getSnoozed(), 'chore', 'snoozed');
    }

    /**
     * GET /api/chores/{chore}
     */
    public function show(string $chore): JsonResponse
    {
        $chore = NameResolver::chore($chore)->load(ChoreResource::RELATIONS);
        $chore->setRelation('choreLogs', $chore->choreLogs()->with('user')->limit(10)->get()->each->setRelation('chore', $chore));

        return $this->ok(new ChoreResource($chore));
    }

    /**
     * POST /api/chores/complete {"chore": "dishes"} or POST /api/chores/{chore}/complete
     * Optional: by, split_with (needs by), at, dry_run.
     */
    public function complete(Request $request, ?string $chore = null): JsonResponse
    {
        $request->validate([
            'chore' => [$chore === null ? 'required' : 'nullable', 'string'],
            'by' => ['nullable', 'string'],
            'split_with' => ['nullable', 'string'],
            'at' => ['nullable', 'string'],
        ]);

        $chore = NameResolver::chore($chore ?? $request->input('chore'));
        $user = NameResolver::user($request->input('by'));
        $splitWith = null;

        if ($request->filled('split_with')) {
            if (!$request->filled('by')) {
                throw new ApiException('Say who did the chore as well as who they split it with.', 'split_requires_by', 422);
            }

            $splitWith = NameResolver::user($request->input('split_with'));

            if ($splitWith->is($user)) {
                throw new ApiException("{$user->first_name} can't split a chore with themselves.", 'invalid_split', 422);
            }
        }

        $at = $this->actionTime($request);
        $credit = match (true) {
            (bool) $splitWith => ", split between {$user->first_name} and {$splitWith->first_name}",
            !$user->isApiUser() => " for {$user->first_name}",
            default => '',
        };
        $data = [
            'dry_run' => $request->boolean('dry_run'),
            'completed_at' => $at->inViewerTz()->toIso8601String(),
            'completed_by' => $user->first_name,
            'split_with' => $splitWith?->first_name,
        ];

        if ($request->boolean('dry_run')) {
            return $this->ok($data + ['chore' => new ChoreResource($chore->load(ChoreResource::RELATIONS))], "Would mark {$chore->name} as done{$credit}.");
        }

        $logs = ChoreLog::recordCompletion($chore, $user, $splitWith, $at);
        $chore->load(ChoreResource::RELATIONS);
        $nextDue = $chore->getNextDueAt();

        return $this->ok(
            $data + [
                'log_id' => $logs->first()->id,
                'logs' => ChoreLogResource::collection($logs->each->load(['chore', 'user'])),
                'chore' => new ChoreResource($chore),
            ],
            "Marked {$chore->name} as done{$credit}.".($nextDue ? ' It\'s next due '.Speech::day($nextDue).'.' : ''),
            201
        );
    }

    /**
     * POST /api/chores/{chore}/snooze {"hours": 72} or {"duration": "3 days"}. Optional: by.
     */
    public function snooze(Request $request, string $chore): JsonResponse
    {
        $request->validate([
            'hours' => ['nullable', 'integer', 'min:1', 'max:8064'],
            'duration' => ['nullable', 'string'],
            'by' => ['nullable', 'string'],
        ]);

        $chore = NameResolver::chore($chore);
        $hours = $request->filled('hours') ? $request->integer('hours') : $this->durationHours($request->input('duration'));
        $snooze = $chore->snooze(NameResolver::user($request->input('by')), $hours);

        return $this->ok(
            new ChoreResource($chore->load(ChoreResource::RELATIONS)),
            "Snoozed {$chore->name} until ".Speech::day($snooze->snoozed_until).'.'
        );
    }

    /**
     * DELETE /api/chores/{chore}/snooze
     */
    public function unsnooze(string $chore): JsonResponse
    {
        $chore = NameResolver::chore($chore)->load('activeSnooze');
        $wasSnoozed = (bool) $chore->activeSnooze;
        $chore->unsnooze();

        return $this->ok(
            new ChoreResource($chore->load(ChoreResource::RELATIONS)),
            $wasSnoozed ? "{$chore->name} is no longer snoozed." : "{$chore->name} wasn't snoozed."
        );
    }

    /**
     * A list of chores with a spoken summary like "2 chores are due: Dishes and Laundry."
     */
    private function choreList(Collection $chores, string $noun, string $state): JsonResponse
    {
        $chores->load(ChoreResource::RELATIONS);

        return $this->ok(ChoreResource::collection($chores), Speech::summarize($chores->pluck('name'), $noun, $state));
    }

    /**
     * Hours in a duration like "3 days", "a week", "one month" or "2 weeks". Capped at a year.
     */
    private function durationHours(?string $duration): int
    {
        $pattern = '/^(a|an|one|\d+)\s*('.implode('|', array_keys(self::DURATION_HOURS)).')s?$/';

        if (!$duration || !preg_match($pattern, strtolower(trim($duration)), $matches)) {
            throw new ApiException(
                'How long should I snooze it? For example 3 days, 1 week or 1 month.',
                'invalid_duration',
                422
            );
        }

        $count = is_numeric($matches[1]) ? (int) $matches[1] : 1;

        return max(1, min($count * self::DURATION_HOURS[$matches[2]], 8064));
    }
}
