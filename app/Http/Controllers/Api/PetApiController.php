<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\PetResource;
use App\Http\Resources\PillResource;
use App\Models\Pet;
use App\Services\Api\NameResolver;
use Illuminate\Http\JsonResponse;

class PetApiController extends ApiController
{
    /**
     * GET /api/pets
     * All pets with their active pills and today's doses.
     */
    public function index(): JsonResponse
    {
        $pets = Pet::with(PetResource::RELATIONS)->orderBy('name')->get();

        return $this->ok(PetResource::collection($pets));
    }

    /**
     * GET /api/pets/{pet}
     */
    public function show(string $pet): JsonResponse
    {
        return $this->ok(new PetResource(NameResolver::pet($pet)->load(PetResource::RELATIONS)));
    }

    /**
     * GET /api/pets/{pet}/pills
     * Kept for older callers; GET /api/pets/{pet} has the same pills.
     */
    public function pills(string $pet): JsonResponse
    {
        $pet = NameResolver::pet($pet)->load(PetResource::RELATIONS);

        return $this->ok([
            'pet' => $pet->name,
            'pills' => PillResource::collection($pet->activePills->sortBy('name')->values()),
        ]);
    }
}
