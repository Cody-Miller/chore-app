<?php

namespace App\Http\Controllers;

use App\Charts\UserActionPastWeekWeighted;
use App\Charts\UserActionPastWeek;
use App\Charts\UserActionMonth;
use App\Charts\UserActionYear;
use App\Charts\ChoreCompletionFrequency;
use App\Charts\ChoreCompletionRate;
use Illuminate\Http\Request;

class GraphsController extends Controller
{
    public function index(
        Request $request,
        UserActionPastWeek $chartWeek,
        UserActionPastWeekWeighted $chartWeekWeighted,
        UserActionMonth $chartMonth,
        UserActionYear $chartYear,
        ChoreCompletionFrequency $chartChoreFrequency,
        ChoreCompletionRate $chartChoreRate,
    ) {
        $return = [];

        // Handle date range parameters
        $weekStartDate = $request->input('week_start_date')
            ? \Carbon\Carbon::parse($request->input('week_start_date'))->startOfDay()
            : now()->startOfWeek();
        $weekEndDate = $request->input('week_end_date')
            ? \Carbon\Carbon::parse($request->input('week_end_date'))->endOfDay()
            : now()->endOfWeek();

        $monthStartDate = $request->input('month_start_date')
            ? \Carbon\Carbon::parse($request->input('month_start_date'))->startOfDay()
            : now()->subMonth();
        $monthEndDate = $request->input('month_end_date')
            ? \Carbon\Carbon::parse($request->input('month_end_date'))->endOfDay()
            : now();

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

        // Pass date values back to view for form
        $return['weekStartDate'] = $weekStartDate->format('Y-m-d');
        $return['weekEndDate'] = $weekEndDate->format('Y-m-d');
        $return['monthStartDate'] = $monthStartDate->format('Y-m-d');
        $return['monthEndDate'] = $monthEndDate->format('Y-m-d');

        return view('graphs.index', $return);
    }
}
