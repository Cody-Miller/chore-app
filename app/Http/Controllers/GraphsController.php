<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Action;
use App\Charts\UserActionPastWeek;
use App\Charts\UserActionMonth;
use App\Charts\UserActionYear;

class GraphsController extends Controller
{
    public function index(
        UserActionPastWeek $chartWeek,
        UserActionMonth $chartMonth,
        UserActionYear $chartYear,
    ) {
        return view(
            'graphs.index', [
                'chartWeek' => $chartWeek->build(),
                'chartMonth' => $chartMonth->build(),
                'chartYear' => $chartYear->build(),
            ]
        );
    }
}
