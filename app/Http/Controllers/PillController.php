<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use App\Http\Requests\StorePillRequest;
use App\Http\Requests\UpdatePillRequest;
use App\Models\Pill;
use App\Models\Pet;
use App\Models\User;

class PillController extends Controller
{
    public function index()
    {
        return view('pills.index', [
            'pills' => Pill::with('pet')->latest()->paginate(50)
        ]);
    }

    public function create()
    {
        return view('pills.create', [
            'pets' => Pet::orderBy('name')->get()
        ]);
    }

    public function store(StorePillRequest $request)
    {
        // Get our slug and see if we already have one
        $slug = Str::slug($request->name, '-');
        if (
            Pill::where('slug', '=', $slug)
            ->where('deleted_at', '=', null)
            ->count() > 0
        ) {
            throw ValidationException::withMessages(['name' => 'Name is too close to another name already in use.']);
        }

        Pill::create([
            'pet_id' => $request->pet_id,
            'name' => $request->name,
            'slug' => $slug,
            'description' => $request->description,
            'dosage' => $request->dosage,
            'scheduled_times' => $request->scheduled_times,
        ]);

        return redirect()->route('pills.index');
    }

    public function show(Pill $pill)
    {
        return view('pills.show', [
            'pill' => $pill,
            'users' => User::select('id', 'name')->get(),
        ]);
    }

    public function edit(Pill $pill)
    {
        return view('pills.edit', [
            'pill' => $pill,
            'pets' => Pet::orderBy('name')->get()
        ]);
    }

    public function update(UpdatePillRequest $request, Pill $pill)
    {
        // Get our slug and see if we already have one
        $slug = Str::slug($request->name, '-');
        if (
            Pill::where('slug', '=', $slug)
            ->where('id', '!=', $pill->id)
            ->where('deleted_at', '=', null)
            ->count() > 0
        ) {
            throw ValidationException::withMessages(['name' => 'Name is too close to another name already in use.']);
        }

        $pill->pet_id = $request->pet_id;
        $pill->name = $request->name;
        $pill->slug = $slug;
        $pill->description = $request->description;
        $pill->dosage = $request->dosage;
        $pill->scheduled_times = $request->scheduled_times;
        $pill->save();

        return redirect()->route('pills.index');
    }

    public function destroy(Pill $pill)
    {
        $pill->delete();
        return redirect()->route('pills.index');
    }
}
