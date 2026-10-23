<?php

namespace App\Models;

use Laravel\Sanctum\PersonalAccessToken as SanctumPersonalAccessToken;

/**
 * API tokens, each with the timezone the API shows and reads times in.
 */
class PersonalAccessToken extends SanctumPersonalAccessToken
{
    protected $fillable = [
        'name',
        'token',
        'abilities',
        'expires_at',
        'timezone',
    ];
}
