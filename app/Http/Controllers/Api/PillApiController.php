<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Pet;
use App\Models\Pill;
use App\Models\PillLog;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PillApiController extends Controller
{
    /**
     * List medications for a specific pet.
     *
     * GET /api/pets/{petName}/pills
     */
    public function index(string $petName): JsonResponse
    {
        $pet = $this->findPetByName($petName);

        if (!$pet) {
            return $this->errorResponse("Pet '{$petName}' not found", 404);
        }

        $pills = $pet->activePills->map(function ($pill) {
            return [
                'name' => $pill->name,
                'dosage' => $pill->dosage,
                'description' => $pill->description,
                'scheduled_times' => $pill->scheduled_times,
                'completed_today' => $pill->getTodaysCompletedDoses(),
                'next_scheduled' => $this->getNextUngivenScheduledTime($pill),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => [
                'pet' => $pet->name,
                'pills' => $pills,
            ],
        ]);
    }

    /**
     * Record medication dose with smart logic.
     * Accepts pet name and optional pill name in request body.
     *
     * POST /api/record-medication
     * Body: {"pet": "kage", "pill": "metacam", "notes": "optional"}
     */
    public function recordMedication(Request $request): JsonResponse
    {
        // Validate request
        $request->validate([
            'pet' => ['required', 'string'],
            'pill' => ['nullable', 'string'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $petName = $request->input('pet');
        $pillName = $request->input('pill');

        $pet = $this->findPetByName($petName);

        if (!$pet) {
            return $this->errorResponse("Pet '{$petName}' not found", 404);
        }

        // Get API user
        $apiUser = $this->getApiUser();
        if (!$apiUser) {
            return $this->errorResponse("API user not configured. Run: php artisan db:seed --class=ApiUserSeeder", 500);
        }

        // Determine which pill to log
        if ($pillName) {
            // Specific pill requested
            $pill = $this->findPillByName($pet, $pillName);
            if (!$pill) {
                return $this->errorResponse("Pill '{$pillName}' not found for pet '{$petName}'", 404);
            }
        } else {
            // Smart selection: pet has only one medication?
            $activePills = $pet->activePills;

            if ($activePills->count() === 0) {
                return $this->errorResponse("Pet '{$petName}' has no medications configured", 400);
            } elseif ($activePills->count() > 1) {
                $pillNames = $activePills->pluck('name')->join(', ');
                return $this->errorResponse(
                    "Pet '{$petName}' has multiple medications. Please specify which: {$pillNames}",
                    400
                );
            }

            $pill = $activePills->first();
        }

        // Get next ungiven scheduled time
        $nextScheduledTime = $this->getNextUngivenScheduledTime($pill);

        if (!$nextScheduledTime) {
            return $this->errorResponse(
                "All scheduled doses for '{$pill->name}' have already been given today",
                400
            );
        }

        // Create pill log
        $pillLog = PillLog::create([
            'pill_id' => $pill->id,
            'user_id' => $apiUser->id,
            'administered_at' => now(),
            'scheduled_time' => $nextScheduledTime,
            'notes' => $request->input('notes'),
        ]);

        return response()->json([
            'success' => true,
            'message' => "Recorded {$pill->name} ({$pill->dosage}) for {$pet->name} at {$nextScheduledTime}",
            'data' => [
                'pet' => $pet->name,
                'pill' => $pill->name,
                'dosage' => $pill->dosage,
                'scheduled_time' => $nextScheduledTime,
                'administered_at' => $pillLog->administered_at->toIso8601String(),
                'administered_by' => 'API',
            ],
        ], 201);
    }

    /**
     * Find pet by name (case-insensitive).
     */
    protected function findPetByName(string $name): ?Pet
    {
        return Pet::whereRaw('LOWER(name) = ?', [strtolower($name)])
            ->first();
    }

    /**
     * Find pill by name for specific pet (case-insensitive).
     */
    protected function findPillByName(Pet $pet, string $name): ?Pill
    {
        return $pet->activePills()
            ->whereRaw('LOWER(name) = ?', [strtolower($name)])
            ->first();
    }

    /**
     * Get next scheduled time that hasn't been given today.
     * Returns earliest scheduled time (past or future) that's not logged.
     */
    protected function getNextUngivenScheduledTime(Pill $pill): ?string
    {
        $completedTimes = $pill->getTodaysCompletedDoses();
        $currentTime = now()->format('H:i');

        // Check if all scheduled times are complete
        $remainingTimes = array_filter(
            $pill->scheduled_times,
            fn($time) => !in_array($time, $completedTimes)
        );

        if (empty($remainingTimes)) {
            return null;
        }

        // Sort remaining times
        sort($remainingTimes);

        // Find first past/current time that's not given, or first future time
        foreach ($remainingTimes as $time) {
            if ($time <= $currentTime) {
                return $time; // Past/current time not yet logged
            }
        }

        // All remaining are future - return earliest
        return $remainingTimes[0];
    }

    /**
     * Get the API system user.
     */
    protected function getApiUser(): ?User
    {
        return User::where('email', 'api@system.local')->first();
    }

    /**
     * Standard error response format.
     */
    protected function errorResponse(string $message, int $code = 400): JsonResponse
    {
        return response()->json([
            'success' => false,
            'error' => $message,
        ], $code);
    }
}
