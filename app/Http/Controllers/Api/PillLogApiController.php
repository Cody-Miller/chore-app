<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\PillLogResource;
use App\Models\PillLog;
use App\Services\Api\NameResolver;
use App\Support\Speech;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PillLogApiController extends ApiController
{
    /**
     * GET /api/pill-logs?pet=&pill=&user=&since=&limit=
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'pet' => ['nullable', 'string'],
            'pill' => ['nullable', 'string'],
            'user' => ['nullable', 'string'],
            'since' => ['nullable', 'string'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $pet = $request->filled('pet') ? NameResolver::pet($request->input('pet')) : null;
        $pill = $request->filled('pill') ? NameResolver::pill($request->input('pill'), $pet) : null;
        $user = $request->filled('user') ? NameResolver::user($request->input('user')) : null;
        $since = $request->filled('since') ? $this->parseTime($request->input('since'), 'since') : null;

        $logs = PillLog::with(['pill.pet', 'user'])
            ->whereHas('pill')
            ->when($pet && !$pill, fn ($q) => $q->whereHas('pill', fn ($p) => $p->where('pet_id', $pet->id)))
            ->when($pill, fn ($q) => $q->where('pill_id', $pill->id))
            ->when($user, fn ($q) => $q->where('user_id', $user->id))
            ->when($since, fn ($q) => $q->where('administered_at', '>=', $since))
            ->latest('administered_at')
            ->limit($request->integer('limit', 20))
            ->get();

        return $this->ok(PillLogResource::collection($logs));
    }

    /**
     * DELETE /api/pill-logs/{id|last}
     * Undo a recently logged dose.
     */
    public function destroy(string $log): JsonResponse
    {
        $pillLog = $this->findUndoableLog(PillLog::with(['pill.pet', 'user']), $log, 'medication log');
        $pillLog->delete();

        $pet = $pillLog->pill?->pet?->name;
        $dose = ($pet ? Speech::possessive($pet).' ' : '').Speech::time($pillLog->scheduled_time).' dose of '.$pillLog->pill?->name;

        return $this->ok(new PillLogResource($pillLog), "Removed {$dose}.");
    }
}
