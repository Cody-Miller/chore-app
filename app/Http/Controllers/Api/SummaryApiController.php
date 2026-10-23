<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\ChoreResource;
use App\Http\Resources\PillResource;
use App\Models\Chore;
use App\Support\Speech;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SummaryApiController extends ApiController
{
    /**
     * GET /api/summary?for=
     * Everything that needs doing: chores and medication doses that are due or coming up.
     */
    public function index(Request $request): JsonResponse
    {
        $scope = $this->dashboardScope($request);
        $dueChores = Chore::getDue(...$scope)->load(ChoreResource::RELATIONS);
        $upcomingChores = Chore::getUpcoming(...$scope)->load(ChoreResource::RELATIONS);
        $oneTimeChores = Chore::getOneTime(...$scope)->load(ChoreResource::RELATIONS);
        $doses = PillResource::doses();
        $dueDoses = $doses->where('status', 'due')->values();
        $upcomingDoses = $doses->where('status', 'upcoming')->values();

        $message = Speech::summarize($dueChores->pluck('name'), 'chore', 'due').' '
            .Speech::summarize($dueDoses->map(fn ($dose) => Speech::possessive($dose['pet'])." {$dose['pill']} at {$dose['label']}"), 'medication dose', 'due');

        if ($upcomingChores->isNotEmpty() || $upcomingDoses->isNotEmpty()) {
            $message .= ' Coming up: '.Speech::list(array_filter([
                $upcomingChores->isNotEmpty() ? Speech::count($upcomingChores->count(), 'chore') : null,
                $upcomingDoses->isNotEmpty() ? Speech::count($upcomingDoses->count(), 'medication dose') : null,
            ])).'.';
        }

        return $this->ok([
            'chores' => [
                'due' => ChoreResource::collection($dueChores),
                'upcoming' => ChoreResource::collection($upcomingChores),
                'one_time' => ChoreResource::collection($oneTimeChores),
                'snoozed_count' => Chore::getSnoozed()->count(),
            ],
            'pills' => [
                'due' => $dueDoses,
                'upcoming' => $upcomingDoses,
                'given_today' => $doses->where('status', 'given')->count(),
            ],
        ], $message);
    }
}
