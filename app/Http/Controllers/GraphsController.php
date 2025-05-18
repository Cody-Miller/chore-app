<?php

namespace App\Http\Controllers;

use App\Charts\UserActionPastWeekWeighted;
use App\Charts\UserActionPastWeek;
use App\Charts\UserActionMonth;
use App\Charts\UserActionYear;

class GraphsController extends Controller
{
    public function index(
        UserActionPastWeek $chartWeek,
        UserActionPastWeekWeighted $chartWeekWeighted,
        UserActionMonth $chartMonth,
        UserActionYear $chartYear,
    ) {
        return view(
            'graphs.index', [
                'chartWeek' => $chartWeek->build(),
                'chartWeekWeighted' => $chartWeekWeighted->build(),
                'chartMonth' => $chartMonth->build(),
                'chartYear' => $chartYear->build(),
            ]
        );
    }
}
