<?php

namespace App\Http\Controllers;

use App\Models\Chore;
use App\Models\ChoreSnooze;
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
        $user = Auth::user();

        // Delete any existing snoozes for this chore (global snooze)
        ChoreSnooze::where('chore_id', $chore->id)->delete();

        // Create new snooze
        ChoreSnooze::create([
            'chore_id' => $chore->id,
            'user_id' => $user->id,
            'snoozed_until' => now()->addHours($request->hours),
        ]);

        return redirect()->back()->with('success', "Chore '{$chore->name}' snoozed for {$request->hours} hour(s).");
    }

    /**
     * Remove snooze from a chore (globally, for all users).
     */
    public function destroy($slug)
    {
        $chore = Chore::where('slug', $slug)->firstOrFail();

        // Delete all snoozes for this chore (global unsnooze)
        ChoreSnooze::where('chore_id', $chore->id)->delete();

        return redirect()->back()->with('success', "Chore '{$chore->name}' unnoozed.");
    }
}
