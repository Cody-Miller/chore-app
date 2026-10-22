<?php

namespace Database\Seeders;

use App\Models\Chore;
use App\Models\ChoreLog;
use App\Models\ChoreSnooze;
use App\Models\Tag;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Seeds chores with a year of completion history so every dashboard tab
 * (due, upcoming, one-time, snoozed) and every chart has data.
 */
class ChoreSeeder extends Seeder
{
    // Hours between occurrences
    const DAILY = 24;
    const WEEKLY = 168;
    const MONTHLY = 720;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user1 = User::where('email', 'test@example.com')->firstOrFail();
        $user2 = User::where('email', 'test2@example.com')->firstOrFail();

        // state: due = overdue now, upcoming = due within 72 hours, done = recently completed,
        //        never = no completions yet. skip = chance a scheduled completion was missed.
        $chores = [
            ['Wash Dishes', 2, self::DAILY, null, 'upcoming', 0.10],
            ['Feed the Pets', 1, self::DAILY, null, 'done', 0.02],
            ['Wipe Kitchen Counters', 1, self::DAILY, null, 'due', 0.35],
            ['Make the Bed', 1, self::DAILY, $user1, 'upcoming', 0.30],
            ['Take Out Trash', 2, 48, null, 'due', 0.15],
            ['Scoop Litter Box', 2, 48, $user2, 'upcoming', 0.05],
            ['Water Plants', 1, 72, $user2, 'due', 0.25],
            ['Vacuum Living Room', 3, self::WEEKLY, null, 'upcoming', 0.15],
            ['Mop Kitchen Floor', 3, self::WEEKLY, null, 'due', 0.30],
            ['Clean Bathrooms', 5, self::WEEKLY, null, 'due', 0.20],
            ['Do Laundry', 3, self::WEEKLY, null, 'done', 0.05],
            ['Change Bed Sheets', 2, self::WEEKLY * 2, $user1, 'upcoming', 0.20],
            ['Grocery Shopping', 4, self::WEEKLY, null, 'done', 0.00],
            ['Take Out Recycling', 1, self::WEEKLY, $user2, 'upcoming', 0.10],
            ['Mow the Lawn', 4, self::WEEKLY, $user1, 'done', 0.25],
            ['Dust Furniture', 2, self::WEEKLY * 2, null, 'due', 0.35],
            ['Clean Microwave', 1, self::WEEKLY * 2, null, 'done', 0.20],
            ['Clean Refrigerator', 3, self::MONTHLY, null, 'due', 0.25],
            ['Clean Oven', 4, self::MONTHLY, $user2, 'due', 0.30], // snoozed below
            ['Wash Windows', 3, self::MONTHLY, null, 'upcoming', 0.15],
            ['Wipe Baseboards', 3, self::MONTHLY, null, 'done', 0.30],
            ['Groom the Dog', 3, self::MONTHLY, $user1, 'done', 0.10],
            ['Descale Coffee Maker', 1, self::MONTHLY * 2, null, 'due', 0.00], // snoozed below
            ['Replace HVAC Filter', 1, self::MONTHLY * 3, $user1, 'done', 0.00],
            ['Clean Gutters', 5, self::MONTHLY * 6, null, 'done', 0.00],
            ['Flip Mattress', 2, self::MONTHLY * 6, null, 'upcoming', 0.00],
            ['Clean Dryer Vent', 2, self::MONTHLY * 3, null, 'never', 0.00, 14],
            ['Organize Garage', 5, 0, null, 'never', 0.00],
            ['Fix Leaky Faucet', 3, 0, $user2, 'never', 0.00],
            ['Hang Picture Frames', 1, 0, $user1, 'never', 0.00],
            ['Donate Old Clothes', 2, 0, null, 'done', 0.00],
        ];

        foreach ($chores as $chore) {
            [$name, $weight, $occurrenceHours, $assignee, $state, $skipChance] = $chore;
            $createdDaysAgo = $chore[6] ?? 400;

            $model = $this->createChore($name, $weight, $occurrenceHours, $assignee, now()->subDays($createdDaysAgo));
            $this->createHistory($model, $state, $skipChance, $assignee, [$user1, $user2]);
        }

        $tags = [
            'Kitchen' => ['Wash Dishes', 'Wipe Kitchen Counters', 'Mop Kitchen Floor', 'Clean Microwave', 'Clean Refrigerator', 'Clean Oven', 'Descale Coffee Maker'],
            'Pets' => ['Feed the Pets', 'Scoop Litter Box', 'Groom the Dog'],
            'Outdoor' => ['Mow the Lawn', 'Water Plants', 'Clean Gutters', 'Wash Windows'],
            'Bathroom' => ['Clean Bathrooms'],
            'Bedroom' => ['Make the Bed', 'Change Bed Sheets', 'Flip Mattress'],
            'Maintenance' => ['Replace HVAC Filter', 'Clean Dryer Vent', 'Fix Leaky Faucet', 'Clean Gutters'],
            'Errands' => ['Grocery Shopping', 'Donate Old Clothes'],
        ];
        foreach ($tags as $tagName => $choreNames) {
            Tag::create(['name' => $tagName])->chores()->attach(Chore::whereIn('name', $choreNames)->pluck('id'));
        }

        // A retired chore with history, to exercise soft-deleted handling
        $retired = $this->createChore('Wash the Car', 3, self::WEEKLY, null, now()->subDays(400));
        $this->createHistory($retired, 'done', 0.20, null, [$user1, $user2], now()->subDays(90));
        $retired->delete();

        // Active snoozes show on the dashboard's snoozed tab; the expired one should not
        $snoozes = [
            ['Clean Oven', $user2, now()->addDays(5)],
            ['Descale Coffee Maker', $user1, now()->addDays(12)],
            ['Dust Furniture', $user1, now()->subDays(3)],
        ];
        foreach ($snoozes as [$name, $user, $until]) {
            ChoreSnooze::create([
                'chore_id' => Chore::where('name', $name)->value('id'),
                'user_id' => $user->id,
                'snoozed_until' => $until,
            ]);
        }
    }

    private function createChore(string $name, int $weight, int $occurrenceHours, ?User $assignee, Carbon $createdAt): Chore
    {
        $chore = new Chore([
            'name' => $name,
            'slug' => Str::slug($name, '-'),
            'description' => fake()->sentence(12),
            'weight' => $weight,
            'occurrence_hours' => $occurrenceHours,
            'assigned_user_id' => $assignee?->id,
        ]);
        $chore->forceFill(['created_at' => $createdAt, 'updated_at' => $createdAt])->save();

        return $chore;
    }

    /**
     * Walk backwards from the most recent completion to the chore's creation date,
     * logging a completion roughly every occurrence_hours.
     */
    private function createHistory(Chore $chore, string $state, float $skipChance, ?User $assignee, array $users, ?Carbon $lastCompleted = null): void
    {
        if ($state === 'never') {
            return;
        }

        $hours = $chore->occurrence_hours;
        $lastCompleted ??= match ($state) {
            'due' => now()->subHours($hours + rand(30, 96)),
            'upcoming' => $hours > 72 ? now()->subHours($hours - rand(30, 60)) : now()->subHours(rand(2, 10)),
            default => now()->subHours(rand(2, 30)),
        };

        // One-time chores are only completed once
        if ($hours === 0) {
            $this->logCompletion($chore, $lastCompleted, $assignee, $users);

            return;
        }

        $logs = [];
        $completedAt = $lastCompleted->copy();
        $first = true;
        while ($completedAt->gte($chore->created_at)) {
            if ($first || mt_rand() / mt_getrandmax() >= $skipChance) {
                // Keep the most recent completion's time so the dashboard state is predictable
                $at = $first ? $completedAt->copy() : $completedAt->copy()->setTime(rand(7, 22), rand(0, 59), rand(0, 59));
                array_push($logs, ...$this->buildLogRows($chore, $at, $assignee, $users));
            }
            $first = false;
            $completedAt->subMinutes((int) ($hours * 60 * (rand(85, 115) / 100)));
        }

        foreach (array_chunk($logs, 500) as $chunk) {
            ChoreLog::insert($chunk);
        }
    }

    private function logCompletion(Chore $chore, Carbon $completedAt, ?User $assignee, array $users): void
    {
        ChoreLog::insert($this->buildLogRows($chore, $completedAt, $assignee, $users));
    }

    /**
     * Build one completion row, or two rows when the effort was split between users.
     */
    private function buildLogRows(Chore $chore, Carbon $completedAt, ?User $assignee, array $users): array
    {
        $timestamps = ['completed_at' => $completedAt, 'created_at' => $completedAt, 'updated_at' => $completedAt];

        // Heavier chores are occasionally split between both users
        if ($chore->weight >= 3 && rand(1, 100) <= 15) {
            $groupId = (string) Str::uuid();
            $share = [50, 60, 70][array_rand([50, 60, 70])];
            [$a, $b] = rand(0, 1) ? $users : array_reverse($users);

            return [
                ['chore_id' => $chore->id, 'user_id' => $a->id, 'weight_percentage' => $share, 'split_group_id' => $groupId, 'is_split' => true] + $timestamps,
                ['chore_id' => $chore->id, 'user_id' => $b->id, 'weight_percentage' => 100 - $share, 'split_group_id' => $groupId, 'is_split' => true] + $timestamps,
            ];
        }

        // Assigned chores are mostly done by the assignee; the rest skew slightly to user 1
        if ($assignee && rand(1, 100) <= 85) {
            $user = $assignee;
        } else {
            $user = rand(1, 100) <= 55 ? $users[0] : $users[1];
        }

        return [
            ['chore_id' => $chore->id, 'user_id' => $user->id, 'weight_percentage' => 100, 'split_group_id' => null, 'is_split' => false] + $timestamps,
        ];
    }
}
