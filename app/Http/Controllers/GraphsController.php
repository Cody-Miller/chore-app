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
        $return = [];
        $content = $chartWeek->build();
        if ($chartWeek->hasData()) {
            $return['chartWeek'] = $content;
        }
        $content = $chartWeekWeighted->build();
        if ($chartWeekWeighted->hasData()) {
            $return['chartWeekWeighted'] = $content;
        }
        $content = $chartMonth->build();
        if ($chartMonth->hasData()) {
            $return['chartMonth'] = $content;
        }
        $content = $chartYear->build();
        if ($chartYear->hasData()) {
            $return['chartYear'] = $content;
        }
        return view('graphs.index', $return);
    }
}
