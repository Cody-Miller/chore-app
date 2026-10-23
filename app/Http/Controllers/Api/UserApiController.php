<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class UserApiController extends ApiController
{
    /**
     * GET /api/users
     * Household members, for the "by", "split_with", "for" and "user" fields.
     */
    public function index(): JsonResponse
    {
        return $this->ok(UserResource::collection(User::humans()->orderBy('name')->get()));
    }
}
