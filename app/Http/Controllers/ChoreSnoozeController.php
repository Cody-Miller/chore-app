<?php

namespace App\Http\Controllers;

use App\Models\Chore;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ChoreSnoozeController extends Controller
{
    /**
     * Snooze a chore globally (applies to all users).
     */
    public function store(Request $request, $slug)
    {
        $request->validate([
            'hours' => 'required|integer|min:1|max:8064', // Max 1 year
        ]);

        $chore = Chore::where('slug', $slug)->firstOrFail();
        $hours = $request->integer('hours');

        $chore->snooze(Auth::user(), $hours);

        return redirect()->back()->with('success', "Chore '{$chore->name}' snoozed for {$hours} hour(s).");
    }

    /**
     * Remove snooze from a chore (globally, for all users).
     */
    public function destroy($slug)
    {
        $chore = Chore::where('slug', $slug)->firstOrFail();

        $chore->unsnooze();

        return redirect()->back()->with('success', "Chore '{$chore->name}' unnoozed.");
    }
}
