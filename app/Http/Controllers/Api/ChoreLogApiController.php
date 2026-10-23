<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\ChoreLogResource;
use App\Models\ChoreLog;
use App\Services\Api\NameResolver;
use App\Support\Speech;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ChoreLogApiController extends ApiController
{
    /**
     * GET /api/chore-logs?chore=&user=&since=&limit=
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'chore' => ['nullable', 'string'],
            'user' => ['nullable', 'string'],
            'since' => ['nullable', 'string'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $chore = $request->filled('chore') ? NameResolver::chore($request->input('chore')) : null;
        $user = $request->filled('user') ? NameResolver::user($request->input('user')) : null;
        $since = $request->filled('since') ? $this->parseTime($request->input('since'), 'since') : null;

        $logs = ChoreLog::with(['chore', 'user'])
            ->whereHas('chore')
            ->when($chore, fn ($q) => $q->where('chore_id', $chore->id))
            ->when($user, fn ($q) => $q->where('user_id', $user->id))
            ->when($since, fn ($q) => $q->where('completed_at', '>=', $since))
            ->latest('completed_at')
            ->limit($request->integer('limit', 20))
            ->get();

        return $this->ok(ChoreLogResource::collection($logs));
    }

    /**
     * DELETE /api/chore-logs/{id|last}
     * Undo a recent completion. Both halves of a split are removed together.
     */
    public function destroy(string $log): JsonResponse
    {
        $choreLog = $this->findUndoableLog(ChoreLog::query(), $log, 'chore log');
        $logs = $choreLog->split_group_id
            ? ChoreLog::where('split_group_id', $choreLog->split_group_id)->get()
            : collect([$choreLog]);

        $logs->each->load(['chore', 'user']);
        $logs->each->delete();

        return $this->ok(
            ChoreLogResource::collection($logs),
            "Removed the {$choreLog->chore?->name} completion from ".Speech::time($choreLog->completed_at).'.'
        );
    }
}
