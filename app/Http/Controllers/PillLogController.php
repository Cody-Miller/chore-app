<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePillLogRequest;
use App\Http\Requests\UpdatePillLogRequest;
use App\Models\Pill;
use App\Models\PillLog;
use App\Models\User;
use Carbon\Carbon;

class PillLogController extends Controller
{
    public function index()
    {
        return view('pilllogs.index', [
            'pillLogs' => PillLog::latest('administered_at')
                ->with(['pill.pet', 'user'])
                ->whereHas('pill', function ($pill) {
                    $pill->whereNull('deleted_at');
                })->paginate(50),
        ]);
    }

    public function store(StorePillLogRequest $request, Pill $pill)
    {
        PillLog::create([
            'pill_id' => $pill->id,
            'user_id' => $request->user_id ?? auth()->user()->id,
            'administered_at' => now(),
            'scheduled_time' => $request->scheduled_time,
            'notes' => $request->notes,
        ]);

        return redirect()->route('pills.dashboard');
    }

    public function edit($id)
    {
        $pillLog = PillLog::where('id', $id)->firstOrFail();

        return view('pilllogs.edit', [
            'pilllog' => $pillLog,
            'users' => User::all(),
            'pills' => Pill::with('pet')->get(),
        ]);
    }

    public function update(UpdatePillLogRequest $request, $id)
    {
        $pillLog = PillLog::where('id', $id)->firstOrFail();
        $pillLog->pill_id = $request->pill_id ?? $pillLog->pill_id;
        $pillLog->user_id = $request->user_id;
        $pillLog->administered_at = $request->administered_at ? Carbon::parse($request->administered_at) : $pillLog->administered_at;
        $pillLog->scheduled_time = $request->scheduled_time;
        $pillLog->notes = $request->notes;
        $pillLog->update();

        return redirect()->route('pilllogs.index');
    }

    public function destroy($id)
    {
        $pillLog = PillLog::where('id', $id)->firstOrFail();
        $pillLog->delete();

        return redirect()->route('pilllogs.index');
    }
}
