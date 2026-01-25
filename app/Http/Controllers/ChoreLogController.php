<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreChoreLogRequest;
use App\Http\Requests\UpdateChoreLogRequest;
use App\Models\Chore;
use App\Models\ChoreLog;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Str;

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
        // Check if this is a split completion
        if ($request->has('split_with_user_id') && $request->split_with_user_id) {
            // Generate a unique ID to link the two split records
            $splitGroupId = Str::uuid();

            // Create first log for the authenticated user
            ChoreLog::create([
                'chore_id' => $chore->id,
                'user_id' => auth()->user()->id,
                'completed_at' => now(),
                'weight_percentage' => 50.00,
                'split_group_id' => $splitGroupId,
                'is_split' => true,
            ]);

            // Create second log for the selected user
            ChoreLog::create([
                'chore_id' => $chore->id,
                'user_id' => $request->split_with_user_id,
                'completed_at' => now(),
                'weight_percentage' => 50.00,
                'split_group_id' => $splitGroupId,
                'is_split' => true,
            ]);
        } else {
            // Regular single-user completion
            ChoreLog::create([
                'chore_id' => $chore->id,
                'user_id' => $request->user_id ?? auth()->user()->id,
                'completed_at' => now(),
                'weight_percentage' => 100.00,
                'is_split' => false,
            ]);
        }

        // Redirect back to the same page the user was on
        $redirectTo = $request->input('redirect_to');
        $tab = $request->input('tab');
        $successMessage = "Chore '{$chore->name}' completed!";

        if ($redirectTo) {
            $url = $redirectTo;
            if ($tab) {
                $url .= (str_contains($url, '?') ? '&' : '?').'tab='.$tab;
            }

            return redirect($url)->with('success', $successMessage);
        }

        return redirect()->route('dashboard')->with('success', $successMessage);
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
        $chorelog->completed_at = Carbon::parse($request->completed_time);
        $chorelog->save();

        return redirect()->route('chorelog.index');
    }

    public function destroy(ChoreLog $chorelog)
    {
        $chorelog->delete();

        return redirect()->route('chorelog.index');
    }
}
