<?php

namespace App\Http\Controllers;

use App\Models\Chore;
use App\Models\ChoreLog;
use App\Models\Pill;
use App\Models\PillLog;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Handles the Done/Given buttons on ntfy reminders. The ntfy app on the phone calls these
 * directly; the signed URL identifies the chore or dose and who the reminder was sent to.
 */
class ReminderCompletionController extends Controller
{
    /**
     * POST /api/reminders/chores/{chore}/users/{user}/complete?date=Y-m-d
     */
    public function chore(Request $request, Chore $chore, User $user): JsonResponse
    {
        $alreadyDone = $chore->choreLogs()
            ->with('user')
            ->where('completed_at', '>=', Carbon::parse($request->query('date'))->startOfDay())
            ->first();

        if ($alreadyDone) {
            return $this->response("'{$chore->name}' was already done by {$alreadyDone->user->name}");
        }

        ChoreLog::create([
            'chore_id' => $chore->id,
            'user_id' => $user->id,
            'completed_at' => now(),
            'weight_percentage' => 100.00,
            'is_split' => false,
        ]);

        return $this->response("Logged '{$chore->name}' for {$user->name}", 201);
    }

    /**
     * POST /api/reminders/pills/{pill}/users/{user}/complete?time=H:i&date=Y-m-d
     *
     * The link expires when the dose's effective day ends, so "today" is always the dose's day.
     */
    public function pill(Request $request, Pill $pill, User $user): JsonResponse
    {
        $time = $request->query('time');

        if ($pill->hasBeenGivenAt($time)) {
            return $this->response("The {$time} dose of {$pill->name} was already given");
        }

        PillLog::create([
            'pill_id' => $pill->id,
            'user_id' => $user->id,
            'administered_at' => now(),
            'scheduled_time' => $time,
        ]);

        return $this->response("Logged the {$time} dose of {$pill->name} for {$user->name}", 201);
    }

    private function response(string $message, int $code = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
        ], $code);
    }
}
