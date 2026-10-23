<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreChoreLogRequest;
use App\Http\Requests\UpdateChoreLogRequest;
use App\Models\Chore;
use App\Models\ChoreLog;
use App\Models\User;
use App\Support\Timezone;
use Carbon\Carbon;

class ChoreLogController extends Controller
{
    public function index()
    {
        return view('chorelog.index', [
            'choreLogs' => ChoreLog::latest('completed_at')
                ->with(['chore', 'user'])
                ->whereHas('chore', function ($chore) {
                    $chore->whereNull('deleted_at');
                })->paginate(50),
        ]);
    }

    public function store(StoreChoreLogRequest $request, Chore $chore)
    {
        $splitWith = $request->split_with_user_id ? User::findOrFail($request->split_with_user_id) : null;
        // Splits are always with the logged-in user; otherwise it may be completed for someone else
        $user = !$splitWith && $request->user_id ? User::findOrFail($request->user_id) : auth()->user();

        $choreLogs = ChoreLog::recordCompletion($chore, $user, $splitWith);

        // Only single completions get an edit link; editing one half of a split leaves its partner unchanged
        $editUrl = $splitWith ? null : route('chorelog.edit', $choreLogs->first());

        // Redirect back to the same page the user was on
        $redirectTo = $request->input('redirect_to');
        $tab = $request->input('tab');
        $anchor = $request->input('anchor');
        $successMessage = "Chore '{$chore->name}' completed!";

        if ($redirectTo) {
            $url = $redirectTo;
            if ($tab) {
                $url .= (str_contains($url, '?') ? '&' : '?').'tab='.$tab;
            }
            if ($anchor && preg_match('/^[A-Za-z0-9_-]+$/', $anchor)) {
                $url .= '#'.$anchor;
            }

            return redirect($url)
                ->with('success', $successMessage)
                ->with('success_action_url', route('chores.show', $chore))
                ->with('success_edit_url', $editUrl);
        }

        return redirect()->route('dashboard')
            ->with('success', $successMessage)
            ->with('success_action_url', route('chores.show', $chore))
            ->with('success_edit_url', $editUrl);
    }

    public function edit(ChoreLog $chorelog)
    {
        return view('chorelog.edit', [
            'chorelog' => $chorelog,
            'users' => User::all(),
            'chores' => Chore::all(),
        ]);
    }

    public function update(UpdateChoreLogRequest $request, ChoreLog $chorelog)
    {
        $chorelog->chore_id = $request->chore_id;
        $chorelog->user_id = $request->user_id;
        // The form shows and takes times in the user's timezone
        $chorelog->completed_at = Carbon::parse($request->completed_time, Timezone::viewer())->utc();
        $chorelog->save();

        return redirect()->route('chorelog.index');
    }

    public function destroy(ChoreLog $chorelog)
    {
        $chorelog->delete();

        return redirect()->route('chorelog.index');
    }
}
