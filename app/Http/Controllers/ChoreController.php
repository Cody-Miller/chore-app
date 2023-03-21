<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use App\Http\Requests\StoreChoreRequest;
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
}
