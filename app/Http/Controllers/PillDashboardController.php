<?php

namespace App\Http\Controllers;

use App\Models\Pill;
use App\Models\Pet;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class PillDashboardController extends Controller
{
    public function index()
    {
        return view('pills.dashboard', [
            'due_now_pills' => Pill::getDueNow(),
            'upcoming_pills' => Pill::getUpcoming(),
            'completed_today_pills' => Pill::getCompletedToday(),
            'users' => User::select('id', 'name')->get(),
            'pets' => Pet::all(),
        ]);
    }
}
