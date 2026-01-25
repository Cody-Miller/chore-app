<?php

namespace App\Http\Controllers;

use App\Charts\ChoreCompletionFrequency;
use App\Charts\ChoreCompletionRate;
use App\Charts\PillMissedDoses;
use App\Charts\UserActionMonth;
use App\Charts\UserActionPastWeek;
use App\Charts\UserActionPastWeekWeighted;
use App\Charts\UserActionYear;
use Carbon\Carbon;
use Illuminate\Http\Request;

class GraphsController extends Controller
{
    private function parseDateFromRequest(Request $request, string $key, Carbon $default, bool $isEndDate = false): Carbon
    {
        if ($request->input($key)) {
            $date = Carbon::parse($request->input($key));

            return $isEndDate ? $date->endOfDay() : $date->startOfDay();
        }

        return $default;
    }

    public function index(
        Request $request,
        UserActionPastWeek $chartWeek,
        UserActionPastWeekWeighted $chartWeekWeighted,
        UserActionMonth $chartMonth,
        UserActionYear $chartYear,
        ChoreCompletionFrequency $chartChoreFrequency,
        ChoreCompletionRate $chartChoreRate,
        PillMissedDoses $chartPillMissedDoses,
    ) {
        $return = [];

        // Parse date range parameters
        $weekStartDate = $this->parseDateFromRequest($request, 'week_start_date', now()->startOfWeek());
        $weekEndDate = $this->parseDateFromRequest($request, 'week_end_date', now()->endOfWeek(), true);

        $monthStartDate = $this->parseDateFromRequest($request, 'month_start_date', now()->subMonth());
        $monthEndDate = $this->parseDateFromRequest($request, 'month_end_date', now(), true);

        $pillStartDate = $this->parseDateFromRequest($request, 'pill_start_date', now()->subMonths()->startOfDay());
        $pillEndDate = $this->parseDateFromRequest($request, 'pill_end_date', now()->subDay()->endOfDay(), true);

        // Build week charts with custom dates
        $content = $chartWeek->build($weekStartDate, $weekEndDate);
        if ($chartWeek->hasData()) {
            $return['chartWeek'] = $content;
        }

        $content = $chartWeekWeighted->build($weekStartDate, $weekEndDate);
        if ($chartWeekWeighted->hasData()) {
            $return['chartWeekWeighted'] = $content;
        }

        // Build month chart with custom dates
        $content = $chartMonth->build($monthStartDate, $monthEndDate);
        if ($chartMonth->hasData()) {
            $return['chartMonth'] = $content;
        }

        // Year chart always uses 365 days (no custom dates)
        $content = $chartYear->build();
        if ($chartYear->hasData()) {
            $return['chartYear'] = $content;
        }

        // Build new chore-specific charts with month date range
        $content = $chartChoreFrequency->build($monthStartDate, $monthEndDate);
        if ($chartChoreFrequency->hasData()) {
            $return['chartChoreFrequency'] = $content;
        }

        $content = $chartChoreRate->build($monthStartDate, $monthEndDate);
        if ($chartChoreRate->hasData()) {
            $return['chartChoreRate'] = $content;
        }

        // Build pill missed doses chart
        $content = $chartPillMissedDoses->build($pillStartDate, $pillEndDate);
        if ($chartPillMissedDoses->hasData()) {
            $return['chartPillMissedDoses'] = $content;
        }

        // Pass date values back to view for form
        $return['weekStartDate'] = $weekStartDate->format('Y-m-d');
        $return['weekEndDate'] = $weekEndDate->format('Y-m-d');
        $return['monthStartDate'] = $monthStartDate->format('Y-m-d');
        $return['monthEndDate'] = $monthEndDate->format('Y-m-d');
        $return['pillStartDate'] = $pillStartDate->format('Y-m-d');
        $return['pillEndDate'] = $pillEndDate->format('Y-m-d');

        return view('graphs.index', $return);
    }
}
