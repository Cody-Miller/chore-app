<?php

namespace App\Http\Controllers;

use App\Models\Chore;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $userId = Auth::id();
        $showAll = session('dashboard.show_all_chores', false);

        return view('dashboard', [
            'due_now_chores' => Chore::getDue($userId, $showAll),
            'upcoming_chores' => Chore::getUpcoming($userId, $showAll),
            'one_time_chores' => Chore::getOneTime($userId, $showAll),
            'snoozed_chores' => $this->getSnoozedChores(),
            'users' => User::select('id', 'name')->get(),
            'show_all_chores' => $showAll,
        ]);
    }

    /**
     * Toggle whether chores assigned to other users show on the dashboard
     */
    public function toggleShowAll(Request $request)
    {
        $request->session()->put(
            'dashboard.show_all_chores',
            !$request->session()->get('dashboard.show_all_chores', false)
        );

        return redirect()->route('dashboard', ['tab' => (int) $request->input('tab', 1)]);
    }

    /**
     * Get chores that are snoozed (globally, for all users)
     */
    private function getSnoozedChores()
    {
        return Chore::select('chores.*', \DB::raw('MAX(chore_snoozes.snoozed_until) as snoozed_until'))
            ->with('tags')
            ->join('chore_snoozes', 'chores.id', '=', 'chore_snoozes.chore_id')
            ->where('chore_snoozes.snoozed_until', '>', now())
            ->whereNull('chores.deleted_at')
            ->groupBy('chores.id')
            ->orderBy('snoozed_until')
            ->get();
    }
}
