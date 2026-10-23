<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;

class ApiTokenController extends Controller
{
    /**
     * Generate a new API token for the user.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'token_name' => ['required', 'string', 'max:255'],
            'timezone' => ['required', 'timezone:all'],
        ]);

        $token = $request->user()->createToken($request->token_name);
        $token->accessToken->update(['timezone' => $request->timezone]);

        // Store the plain-text token in session to display once
        session()->flash('api_token', $token->plainTextToken);
        session()->flash('token_name', $request->token_name);

        return redirect()->route('profile.edit')
            ->with('status', 'api-token-created');
    }

    /**
     * Change the timezone an API token shows and reads times in.
     */
    public function update(Request $request, $tokenId): RedirectResponse
    {
        $request->validate([
            'timezone' => ['required', 'timezone:all'],
        ]);

        $request->user()->tokens()->where('id', $tokenId)->update(['timezone' => $request->timezone]);

        return redirect()->route('profile.edit')
            ->with('status', 'api-token-updated')
            ->with('token_id', (int) $tokenId);
    }

    /**
     * Revoke an API token.
     */
    public function destroy(Request $request, $tokenId): RedirectResponse
    {
        $request->user()->tokens()->where('id', $tokenId)->delete();

        return redirect()->route('profile.edit')
            ->with('status', 'api-token-revoked');
    }
}
