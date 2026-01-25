<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePetRequest;
use App\Http\Requests\UpdatePetRequest;
use App\Models\Pet;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PetController extends Controller
{
    public function index()
    {
        return view('pets.index', [
            'pets' => Pet::latest()->paginate(50),
        ]);
    }

    public function create()
    {
        return view('pets.create');
    }

    public function store(StorePetRequest $request)
    {
        // Get our slug and see if we already have one
        $slug = Str::slug($request->name, '-');
        if (
            Pet::where('slug', '=', $slug)
                ->where('deleted_at', '=', null)
                ->count() > 0
        ) {
            throw ValidationException::withMessages(['name' => 'Name is too close to another name already in use.']);
        }

        $data = [
            'name' => $request->name,
            'slug' => $slug,
            'species' => $request->species,
            'breed' => $request->breed,
            'birth_date' => $request->birth_date,
            'notes' => $request->notes,
        ];

        if ($photoPath = $this->storePhoto($request)) {
            $data['photo_path'] = $photoPath;
        }

        Pet::create($data);

        return redirect()->route('pets.index');
    }

    public function show(Pet $pet)
    {
        return view('pets.show', [
            'pet' => $pet,
            'pills' => $pet->pills()->with('pillLogs')->get(),
        ]);
    }

    public function edit(Pet $pet)
    {
        return view('pets.edit', [
            'pet' => $pet,
        ]);
    }

    public function update(UpdatePetRequest $request, Pet $pet)
    {
        // Get our slug and see if we already have one
        $slug = Str::slug($request->name, '-');
        if (
            Pet::where('slug', '=', $slug)
                ->where('id', '!=', $pet->id)
                ->where('deleted_at', '=', null)
                ->count() > 0
        ) {
            throw ValidationException::withMessages(['name' => 'Name is too close to another name already in use.']);
        }

        $pet->name = $request->name;
        $pet->slug = $slug;
        $pet->species = $request->species;
        $pet->breed = $request->breed;
        $pet->birth_date = $request->birth_date;
        $pet->notes = $request->notes;

        if ($photoPath = $this->storePhoto($request)) {
            $pet->photo_path = $photoPath;
        }

        $pet->save();

        return redirect()->route('pets.index');
    }

    public function destroy(Pet $pet)
    {
        $pet->delete();

        return redirect()->route('pets.index');
    }

    private function storePhoto(Request $request): ?string
    {
        if ($request->hasFile('photo')) {
            return $request->file('photo')->store('pet_photos', 'public');
        }

        return null;
    }
}
