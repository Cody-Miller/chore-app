<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Services\Api\NameResolver;
use Carbon\Carbon;
use Carbon\Exceptions\InvalidFormatException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

abstract class ApiController extends Controller
{
    /**
     * Logs can be undone through the API for this long after they were created.
     */
    const UNDO_WINDOW_HOURS = 24;

    /**
     * A successful response: {success: true, message?, data}.
     */
    protected function ok(mixed $data, ?string $message = null, int $status = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            ...($message !== null ? ['message' => $message] : []),
            'data' => $data,
        ], $status);
    }

    /**
     * Parse a time like "2026-10-23T08:00:00-04:00", "yesterday" or "30 minutes ago" in the app's timezone.
     */
    protected function parseTime(string $input, string $field): Carbon
    {
        try {
            return Carbon::parse($input)->setTimezone(config('app.timezone'));
        } catch (InvalidFormatException) {
            throw new ApiException("I couldn't understand the time {$input}.", 'invalid_time', 422, ['field' => $field]);
        }
    }

    /**
     * When an action happened: the optional "at" field, which must be within the last 24 hours. Defaults to now.
     */
    protected function actionTime(Request $request): Carbon
    {
        if (!$request->filled('at')) {
            return now();
        }

        $at = $this->parseTime($request->input('at'), 'at');

        if ($at->isAfter(now()->addMinute())) {
            throw new ApiException('That time is in the future.', 'invalid_time', 422, ['field' => 'at']);
        }
        if ($at->isBefore(now()->subDay())) {
            throw new ApiException('That time is more than 24 hours ago. Use the website for older entries.', 'invalid_time', 422, ['field' => 'at']);
        }

        return $at->min(now());
    }

    /**
     * The dashboard filter from "?for=Name": that user's chores plus unassigned ones. Defaults to everyone's.
     *
     * @return array{0: int|null, 1: bool} [$userId, $showAll] for Chore::getDue() and friends
     */
    protected function dashboardScope(Request $request): array
    {
        if (!$request->filled('for')) {
            return [null, true];
        }

        return [NameResolver::user($request->query('for'))->id, false];
    }

    /**
     * A log to undo, by id or "last" (the most recently created). Only recent logs can be undone.
     */
    protected function findUndoableLog(Builder $query, string $id, string $label): Model
    {
        $log = match (true) {
            $id === 'last' => $query->latest('created_at')->latest('id')->first(),
            ctype_digit($id) => $query->find($id),
            default => null,
        };

        if (!$log) {
            throw new ApiException("I couldn't find that {$label}.", 'log_not_found', 404);
        }
        if ($log->created_at->lt(now()->subHours(self::UNDO_WINDOW_HOURS))) {
            throw new ApiException(
                "That {$label} is more than ".self::UNDO_WINDOW_HOURS.' hours old. Remove it on the website instead.',
                'undo_window_passed',
                422
            );
        }

        return $log;
    }
}
