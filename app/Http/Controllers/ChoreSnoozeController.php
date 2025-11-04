<?php

namespace App\Http\Controllers;

use App\Models\Chore;
use App\Models\ChoreSnooze;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ChoreSnoozeController extends Controller
{
    /**
     * Snooze a chore for the authenticated user.
     */
    public function store(Request $request, $slug)
    {
        $request->validate([
            'hours' => 'required|integer|min:1|max:8064', // Max 1 year
        ]);

        $chore = Chore::where('slug', $slug)->firstOrFail();
        $user = Auth::user();

        // Delete any existing snooze for this chore and user
        ChoreSnooze::where('chore_id', $chore->id)
            ->where('user_id', $user->id)
            ->delete();

        // Create new snooze
        ChoreSnooze::create([
            'chore_id' => $chore->id,
            'user_id' => $user->id,
            'snoozed_until' => now()->addHours($request->hours),
        ]);

        return redirect()->back()->with('success', "Chore '{$chore->name}' snoozed for {$request->hours} hour(s).");
    }

    /**
     * Remove snooze from a chore for the authenticated user.
     */
    public function destroy($slug)
    {
        $chore = Chore::where('slug', $slug)->firstOrFail();
        $user = Auth::user();

        ChoreSnooze::where('chore_id', $chore->id)
            ->where('user_id', $user->id)
            ->delete();

        return redirect()->back()->with('success', "Chore '{$chore->name}' unnoozed.");
    }
}
