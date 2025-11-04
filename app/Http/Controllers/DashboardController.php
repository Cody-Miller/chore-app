<?php

namespace App\Http\Controllers;

use App\Models\Chore;
use App\Models\ChoreSnooze;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index() {
        $userId = Auth::id();

        return view('dashboard', [
            'due_now_chores' => Chore::getDue($userId),
            'upcoming_chores' => Chore::getUpcoming($userId),
            'one_time_chores' => Chore::getOneTime($userId),
            'snoozed_chores' => $this->getSnoozedChores(),
            'users' => User::select('id', 'name')->get(),
        ]);
    }

    /**
     * Get chores that are snoozed
     */
    private function getSnoozedChores()
    {
        return Chore::select('chores.*')
            ->join('chore_snoozes', 'chores.id', '=', 'chore_snoozes.chore_id')
            ->where('chore_snoozes.snoozed_until', '>', now())
            ->whereNull('chores.deleted_at')
            ->orderBy('chore_snoozes.snoozed_until')
            ->get();
    }
}
