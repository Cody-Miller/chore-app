<?php

namespace App\Http\Middleware;

use App\Models\PersonalAccessToken;
use App\Support\Timezone;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class UseViewerTimezone
{
    /**
     * Show and read times in the API token's timezone, else the user's, else the household's.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $token = $user?->currentAccessToken();

        Timezone::setViewer(
            Timezone::valid($token instanceof PersonalAccessToken ? $token->getAttribute('timezone') : null)
                ?? Timezone::valid($user?->timezone)
        );

        $response = $next($request);

        if ($request->is('api/*')) {
            $response->headers->set('X-Timezone', Timezone::viewer());
        }

        return $response;
    }
}
