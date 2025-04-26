<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreChoreLogRequest;
use App\Http\Requests\UpdateChoreLogRequest;
use App\Models\Chore;
use App\Models\ChoreLog;
use App\Models\User;

class ChoreLogController extends Controller
{
    public function index() {
        return view('chorelog.index', [
            'choreLogs' => ChoreLog::latest('completed_at')
                ->with(['chore', 'user'])
                ->whereHas('chore', function ($chore) {
                    $chore->whereNull('deleted_at');
                })->paginate(50)
        ]);
    }

    public function store($chore) {
        $chore = Chore::findOrFail($chore);
        ChoreLog::create([
            'chore_id' => $chore->id,
            'user_id' => auth()->user()->id,
        ]);
        return redirect()->route('dashboard'); //->with('success', 'Chore created successfully!');
    }

    public function edit($id) {
        $choreLog = ChoreLog::where('id', $id)->first();
        return view('chorelog.edit', [
            'chorelog' => $choreLog,
            'users' => User::all(),
            'chores' => Chore::all(),
        ]);
    }

    public function update(UpdateChoreLogRequest $request, $id) {
        $choreLog = ChoreLog::where('id', $id)->first();
        $choreLog->chore_id = $request->chore_id;
        $choreLog->user_id = $request->user_id;
        $choreLog->completed_at = $request->completed_time;
        $choreLog->update();
        return redirect()->route('chorelog.index'); //->with('success', 'Chore update successfully!');
    }

    public function destroy($id) {
        $choreLog = ChoreLog::where('id', $id)->first();
        $choreLog->delete();
        return redirect()->route('chorelog.index'); //->with('success', 'Chore Removed!');
    }
}
