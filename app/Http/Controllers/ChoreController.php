<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use App\Http\Requests\StoreChoreRequest;
use App\Http\Requests\UpdateChoreRequest;
use App\Models\Chore;

class ChoreController extends Controller
{
    public function index() {
        return view('chore.index', [
            'chores' => Chore::latest()->paginate(3)
        ]);
    }

    public function create() {
        return view('chore.create');
    }

    public function show($slug) {
        return view('chore.show', [
            'chore' => Chore::where('slug', $slug)->first()
        ]);
    }

    public function edit($slug) {
        $chore = Chore::where('slug', $slug)->first();
        $choreMonth = floor($chore->occurrence_hours / 730);
        $chore->occurrence_hours -= $choreMonth * 730;
        $choreDay = floor($chore->occurrence_hours / 24);
        return view('chore.edit', [
            'chore' => Chore::where('slug', $slug)->first(),
            'choreMonth' => $choreMonth,
            'choreDay' => $choreDay
        ]);
    }

    public function store(StoreChoreRequest $request) {
        // Get our slug and see if we already have one
        $slug = Str::slug($request->name, '-');
        if (Chore::where('slug', '=', $slug)->count() > 0) {
            throw ValidationException::withMessages(['name' => 'Name is too close to another name already in use.']);
        }
        // Convert our occurrence to hours
        $hours = ($request->occurDay * 24) + ($request->occurMonth * 730); 
        Chore::create([
            'name' => $request->name,
            'slug' => $slug,
            'description' => $request->desc,
            'weight' => $request->weight,
            'occurrence_hours' => $hours
        ]);

        return redirect()->route('chores.index'); //->with('success', 'Chore created successfully!');
    }

    public function update(UpdateChoreRequest $request, $slug) {
        // Get our slug and see if we already have one
        $chore = Chore::where('slug', $slug)->first();
        $slug = Str::slug($request->name, '-');
        if (Chore::where('slug', '=', $slug)->where('id', '!=', $chore->id)->count() > 0) {
            throw ValidationException::withMessages(['name' => 'Name is too close to another name already in use.']);
        }
        // Convert our occurrence to hours
        $hours = ($request->occurDay * 24) + ($request->occurMonth * 730); 
        $chore->name = $request->name;
        $chore->slug = $slug;
        $chore->description = $request->desc;
        $chore->weight = $request->weight; 
        $chore->occurrence_hours = $hours;
        $chore->save();

        return redirect()->route('chores.index'); //->with('success', 'Chore update successfully!');
    }
}
