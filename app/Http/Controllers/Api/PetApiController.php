<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Pet;
use Illuminate\Http\JsonResponse;

class PetApiController extends Controller
{
    /**
     * List all pets with their active pills.
     *
     * GET /api/pets
     */
    public function index(): JsonResponse
    {
        $pets = Pet::with(['activePills' => function ($query) {
            $query->orderBy('name');
        }])
        ->orderBy('name')
        ->get()
        ->map(function ($pet) {
            return [
                'name' => $pet->name,
                'species' => $pet->species,
                'breed' => $pet->breed,
                'age' => $pet->getAge(),
                'pills' => $pet->activePills->map(function ($pill) {
                    return [
                        'name' => $pill->name,
                        'dosage' => $pill->dosage,
                        'scheduled_times' => $pill->scheduled_times,
                        'completed_today' => $pill->getTodaysCompletedDoses(),
                    ];
                }),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $pets,
        ]);
    }
}
