<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use App\Http\Requests\StorePillRequest;
use App\Http\Requests\UpdatePillRequest;
use App\Models\Pill;
use App\Models\Pet;
use App\Models\Tag;
use App\Models\User;

class PillController extends Controller
{
    public function index(Request $request)
    {
        $tag = $request->query('tag');

        $pills = Pill::with(['pet', 'tags'])
            ->when($tag, fn ($q) => $q->whereHas('tags', fn ($t) => $t->where('name', $tag)))
            ->orderByDesc('is_active')
            ->latest()
            ->paginate(50)
            ->withQueryString();

        return view('pills.index', [
            'pills' => $pills,
            'tags' => Tag::whereHas('pills')->orderBy('name')->get(),
            'filters' => ['tag' => $tag],
        ]);
    }

    public function create()
    {
        return view('pills.create', [
            'pets' => Pet::orderBy('name')->get(),
            'allTags' => Pill::tagOptions(),
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

        $pill = Pill::create([
            'pet_id' => $request->pet_id,
            'name' => $request->name,
            'slug' => $slug,
            'description' => $request->description,
            'dosage' => $request->dosage,
            'scheduled_times' => $request->scheduled_times,
            'is_active' => $request->boolean('is_active', true),
            'notifications_enabled' => $request->boolean('notifications_enabled'),
        ]);
        $pill->syncTagNames($request->input('tags', []));

        return redirect()->route('pills.index');
    }

    public function show(Pill $pill)
    {
        return view('pills.show', [
            'pill' => $pill->load('tags'),
            'users' => User::select('id', 'name')->get(),
        ]);
    }

    public function edit(Pill $pill)
    {
        return view('pills.edit', [
            'pill' => $pill->load('tags'),
            'pets' => Pet::orderBy('name')->get(),
            'allTags' => Pill::tagOptions(),
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
        $pill->is_active = $request->boolean('is_active');
        $pill->notifications_enabled = $request->boolean('notifications_enabled');
        $pill->save();
        $pill->syncTagNames($request->input('tags', []));

        return redirect()->route('pills.index');
    }

    public function destroy(Pill $pill)
    {
        $pill->delete();
        return redirect()->route('pills.index');
    }
}
