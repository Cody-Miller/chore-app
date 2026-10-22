<?php

namespace App\Http\Controllers;

use App\Notifications\Channels\NtfyChannel;
use App\Notifications\TestNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Throwable;

class NotificationSettingsController extends Controller
{
    /**
     * Save the ntfy topic the user's reminders are published to.
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            // Same rule ntfy uses for topic names
            'ntfy_topic' => ['nullable', 'string', 'max:64', 'regex:/^[A-Za-z0-9_-]+$/'],
        ], [
            'ntfy_topic.regex' => __('Topics can only contain letters, numbers, dashes and underscores.'),
        ]);

        $request->user()->update($validated);

        return $this->backToSettings()->with('status', 'notifications-updated');
    }

    /**
     * Send a test notification to the user's topic, showing ntfy's error if it fails.
     */
    public function test(Request $request): RedirectResponse
    {
        if (! NtfyChannel::isConfigured()) {
            return $this->backToSettings()->with('ntfy_error', __('NTFY_URL is not set in .env, so notifications are turned off.'));
        }

        if (! $request->user()->ntfy_topic) {
            return $this->backToSettings()->with('ntfy_error', __('Save a topic before sending a test.'));
        }

        try {
            $request->user()->notify(new TestNotification());
        } catch (Throwable $e) {
            return $this->backToSettings()->with('ntfy_error', __('The test notification failed: :error', ['error' => $e->getMessage()]));
        }

        return $this->backToSettings()->with('status', 'notification-test-sent');
    }

    private function backToSettings(): RedirectResponse
    {
        return redirect()->to(route('profile.edit').'#notifications');
    }
}
