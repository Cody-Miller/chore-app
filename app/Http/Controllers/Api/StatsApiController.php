<?php

namespace App\Http\Controllers\Api;

use App\Models\ChoreLog;
use App\Models\PillLog;
use App\Support\Speech;
use App\Support\Timezone;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class StatsApiController extends ApiController
{
    /**
     * GET /api/stats?period=week|month|year
     * Chore points and doses given per person, and missed doses. Uses the same queries as the Graphs page.
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate(['period' => ['nullable', 'in:week,month,year']]);

        $period = $request->query('period', 'week');
        // Weeks and days are the token's
        $now = now(Timezone::viewer());
        [$start, $end, $label] = match ($period) {
            'week' => [$now->copy()->startOfWeek(), $now->copy()->endOfWeek(), 'This week'],
            'month' => [$now->copy()->subMonth(), $now, 'In the past month'],
            'year' => [$now->copy()->subYear(), $now, 'In the past year'],
        };

        $chorePoints = ChoreLog::getChoreLogsWeightByUsers($start, $end)
            ->map(fn ($row) => ['first_name' => Str::before($row->name, ' '), 'points' => round((float) $row->total_weight, 1)])
            ->sortByDesc('points')
            ->values();

        $dosesGiven = PillLog::getPillAdministrationsByUsers($start, $end)
            ->map(fn ($row) => ['first_name' => Str::before($row->name, ' '), 'doses' => (int) $row->pill_count])
            ->sortByDesc('doses')
            ->values();

        // Like the Graphs page, only count missed doses up to the end of yesterday
        $missedDoses = PillLog::getMissedDosesByPill($start->copy()->startOfDay(), $end->copy()->min($now->copy()->subDay()->endOfDay()))
            ->groupBy('pill_id')
            ->map(fn ($days) => ['pet' => $days->first()['pet_name'], 'pill' => $days->first()['pill_name'], 'missed' => $days->sum('missed_count')])
            ->sortByDesc('missed')
            ->values();

        return $this->ok([
            'period' => $period,
            'start' => $start->inViewerTz()->toIso8601String(),
            'end' => $end->inViewerTz()->toIso8601String(),
            'chore_points' => $chorePoints,
            'doses_given' => $dosesGiven,
            'missed_doses' => $missedDoses,
        ], $this->message($label, $chorePoints, $missedDoses->sum('missed')));
    }

    /**
     * "This week, Cody has the most chore points with 24, then Sarah with 18. 2 medication doses were missed."
     */
    private function message(string $label, $chorePoints, int $missed): string
    {
        if ($chorePoints->isEmpty()) {
            $message = "{$label}, no chores were completed.";
        } else {
            $leader = $chorePoints->first();
            $message = "{$label}, {$leader['first_name']} has the most chore points with {$leader['points']}";
            $others = $chorePoints->slice(1)->map(fn ($row) => "{$row['first_name']} with {$row['points']}");
            $message .= ($others->isNotEmpty() ? ', then '.Speech::list($others) : '').'.';
        }

        return $message.' '.($missed === 1 ? '1 medication dose was' : "{$missed} medication doses were").' missed.';
    }
}
