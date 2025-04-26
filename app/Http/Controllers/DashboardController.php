<?php

namespace App\Http\Controllers;

use App\Models\Chore;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index() {
        return view('dashboard', [
            'due_now_chores' => Chore::getDue(),
            'upcoming_chores' => Chore::getUpcoming(),
            'one_time_chores' => Chore::getOneTime(),
        ]);
    }
}
