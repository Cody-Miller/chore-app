<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreChoreRequest;
use App\Http\Requests\UpdateChoreRequest;
use App\Models\Chore;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ChoreController extends Controller
{
    public function index(Request $request)
    {
        $occurrence = $request->query('occurrence');
        $assigned = $request->query('assigned');
        $tag = $request->query('tag');

        $chores = Chore::with(['assignedUser', 'tags'])
            ->when($occurrence === 'recurring', fn ($q) => $q->where('occurrence_hours', '>', 0))
            ->when($occurrence === 'one-time', fn ($q) => $q->where('occurrence_hours', 0))
            ->when($assigned === 'everyone', fn ($q) => $q->whereNull('assigned_user_id'))
            ->when($assigned === 'assigned', fn ($q) => $q->whereNotNull('assigned_user_id'))
            ->when(ctype_digit((string) $assigned), fn ($q) => $q->where('assigned_user_id', $assigned))
            ->when($tag, fn ($q) => $q->whereHas('tags', fn ($t) => $t->where('name', $tag)))
            ->latest()
            ->paginate(50)
            ->withQueryString();

        return view('chore.index', [
            'chores' => $chores,
            'users' => User::select('id', 'name')->orderBy('name')->get(),
            'tags' => Tag::whereHas('chores')->orderBy('name')->get(),
            'filters' => ['occurrence' => $occurrence, 'assigned' => $assigned, 'tag' => $tag],
        ]);
    }

    public function create()
    {
        return view('chore.create', [
            'users' => User::select('id', 'name')->orderBy('name')->get(),
            'allTags' => Chore::tagOptions(),
        ]);
    }

    public function store(StoreChoreRequest $request)
    {
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
        $chore = Chore::create([
            'name' => $request->name,
            'slug' => $slug,
            'description' => $request->desc,
            'weight' => $request->weight,
            'occurrence_hours' => $hours,
            'assigned_user_id' => $request->assigned_user_id ?: null,
        ]);
        $chore->syncTagNames($request->input('tags', []));

        return redirect()->route('chores.index');
    }

    public function show(Chore $chore)
    {
        return view('chore.show', [
            'chore' => $chore->load(['assignedUser', 'tags']),
            'users' => User::select('id', 'name')->get(),
        ]);
    }

    public function edit(Chore $chore)
    {
        $choreMonth = floor($chore->occurrence_hours / 720);
        $chore->occurrence_hours -= $choreMonth * 720;
        $choreDay = floor($chore->occurrence_hours / 24);

        return view('chore.edit', [
            'chore' => $chore->load('tags'),
            'choreMonth' => $choreMonth,
            'choreDay' => $choreDay,
            'users' => User::select('id', 'name')->orderBy('name')->get(),
            'allTags' => Chore::tagOptions(),
        ]);
    }

    public function update(UpdateChoreRequest $request, Chore $chore)
    {
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
        $chore->assigned_user_id = $request->assigned_user_id ?: null;
        $chore->save();
        $chore->syncTagNames($request->input('tags', []));

        return redirect()->route('chores.index');
    }

    public function destroy(Chore $chore)
    {
        $chore->delete();

        return redirect()->route('chores.index');
    }
}
