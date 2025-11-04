<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use App\Http\Requests\StoreChoreRequest;
use App\Http\Requests\UpdateChoreRequest;
use App\Models\Chore;
use App\Models\User;

class ChoreController extends Controller
{
    public function index() {
        return view('chore.index', [
            'chores' => Chore::latest()->paginate(50)
        ]);
    }

    public function create() {
        return view('chore.create');
    }

    public function store(StoreChoreRequest $request) {
        // Get our slug and see if we already have one
        $slug = Str::slug($request->name, '-');
        if (
            Chore::where('slug', '=', $slug)
            ->where('deleted_at', '=', null)
            ->count() > 0
        ) {
            throw ValidationException::withMessages(['name' => 'Name is too close to another name already in use.']);
        }
        // Convert our occurrence to hours
        $hours = ($request->occurDay * 24) + ($request->occurMonth * 720);
        Chore::create([
            'name' => $request->name,
            'slug' => $slug,
            'description' => $request->desc,
            'weight' => $request->weight,
            'occurrence_hours' => $hours
        ]);

        return redirect()->route('chores.index'); //->with('success', 'Chore created successfully!');
    }

    public function show(Chore $chore) {
        return view('chore.show', [
            'chore' => $chore,
            'users' => User::select('id', 'name')->get(),
        ]);
    }

    public function edit(Chore $chore) {
        $choreMonth = floor($chore->occurrence_hours / 720);
        $chore->occurrence_hours -= $choreMonth * 720;
        $choreDay = floor($chore->occurrence_hours / 24);
        return view('chore.edit', [
            'chore' => $chore,
            'choreMonth' => $choreMonth,
            'choreDay' => $choreDay
        ]);
    }

    public function update(UpdateChoreRequest $request, Chore $chore) {
        // Get our slug and see if we already have one
        $slug = Str::slug($request->name, '-');
        if (
            Chore::where('slug', '=', $slug)
            ->where('id', '!=', $chore->id)
            ->where('deleted_at', '=', null)
            ->count() > 0
        ) {
            throw ValidationException::withMessages(['name' => 'Name is too close to another name already in use.']);
        }
        // Convert our occurrence to hours
        $hours = ($request->occurDay * 24) + ($request->occurMonth * 720);
        $chore->name = $request->name;
        $chore->slug = $slug;
        $chore->description = $request->desc;
        $chore->weight = $request->weight;
        $chore->occurrence_hours = $hours;
        $chore->save();

        return redirect()->route('chores.index'); //->with('success', 'Chore update successfully!');
    }

    public function destroy (Chore $chore) {
        $chore->delete();
        return redirect()->route('chores.index'); //->with('success', 'Chore Removed!');
    }
}
