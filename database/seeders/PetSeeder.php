<?php

namespace Database\Seeders;

use App\Models\Pet;
use App\Models\Pill;
use App\Models\PillLog;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Seeds pets, their medications and dose history so the pill dashboard
 * (due now, upcoming, completed today), pill logs and missed-dose chart have data.
 */
class PetSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = [
            User::where('email', 'test@example.com')->firstOrFail(),
            User::where('email', 'test2@example.com')->firstOrFail(),
        ];
        $apiUser = User::where('email', 'api@system.local')->firstOrFail();

        $biscuit = $this->createPet('Biscuit', 'Dog', 'Golden Retriever', '2016-04-12', 'Allergic to chicken. Hide pills in peanut butter.');
        $mochi = $this->createPet('Mochi', 'Cat', 'Domestic Shorthair', '2012-09-03', 'Hyperthyroid, vet recheck every 3 months.');
        $pickles = $this->createPet('Pickles', 'Cat', 'Maine Coon', '2023-02-14', 'Will spit out pills, watch him swallow.');
        $this->createPet('Sunny', 'Bird', 'Cockatiel', '2021-06-30', null);

        // [pet, name, dosage, times, created days ago, chance a dose was missed, deleted days ago, paused days ago]
        $pills = [
            [$biscuit, 'Carprofen', '75 mg', ['08:00', '20:00'], 120, 0.06, null, null],
            [$biscuit, 'Joint Supplement', '1 chew', ['08:00'], 120, 0.15, null, null],
            [$mochi, 'Methimazole', '2.5 mg', ['07:30', '19:30'], 120, 0.08, null, null],
            [$mochi, 'Prednisolone', '5 mg', ['08:00'], 90, 0.05, null, 20],
            [$pickles, 'Amoxicillin', '50 mg', ['09:00', '21:00'], 10, 0.10, null, null],
            [$pickles, 'Gabapentin', '100 mg', ['22:00'], 80, 0.10, 40, null],
        ];

        foreach ($pills as $index => [$pet, $name, $dosage, $times, $createdDaysAgo, $missChance, $deletedDaysAgo, $pausedDaysAgo]) {
            $pill = new Pill([
                'pet_id' => $pet->id,
                'name' => $name,
                'slug' => Str::slug($name, '-'),
                'description' => fake()->sentence(10),
                'dosage' => $dosage,
                'scheduled_times' => $times,
                'is_active' => $pausedDaysAgo === null,
            ]);
            $createdAt = now()->subDays($createdDaysAgo)->startOfDay();
            $pill->forceFill(['created_at' => $createdAt, 'updated_at' => $createdAt])->save();

            // Paused (inactive) and deleted pills stop getting doses when they ended
            $endedDaysAgo = $deletedDaysAgo ?? $pausedDaysAgo;
            $endAt = $endedDaysAgo ? now()->subDays($endedDaysAgo) : now();
            // Leave today's most recent dose of the first pill ungiven so the dashboard has something due
            $this->createHistory($pill, $missChance, $endAt, $users, $apiUser, $index === 0);

            if ($deletedDaysAgo) {
                $pill->deleted_at = $endAt;
                $pill->save();
            }
        }
    }

    private function createPet(string $name, string $species, string $breed, string $birthDate, ?string $notes): Pet
    {
        return Pet::create([
            'name' => $name,
            'slug' => Str::slug($name, '-'),
            'species' => $species,
            'breed' => $breed,
            'birth_date' => $birthDate,
            'notes' => $notes,
        ]);
    }

    private function createHistory(Pill $pill, float $missChance, Carbon $endAt, array $users, User $apiUser, bool $leaveLatestDue): void
    {
        $today = Pill::getEffectiveDate();
        $period = CarbonPeriod::create(Pill::getEffectiveDate($pill->created_at), Pill::getEffectiveDate($endAt));
        $notes = ['Took it with food', 'Hidden in a treat', 'Spit it out once, second try worked', 'Gave a little late'];

        $logs = [];
        foreach ($period as $date) {
            $isToday = $date->isSameDay($today);
            $dueTimes = [];

            foreach ($pill->scheduled_times as $time) {
                // Times before the cutoff belong to the next calendar day
                $scheduled = $date->copy()->setTimeFromTimeString($time);
                if ($scheduled->hour < Pill::DAY_CUTOFF_HOUR) {
                    $scheduled->addDay();
                }
                if ($scheduled->lt($pill->created_at) || $scheduled->gt($endAt)) {
                    continue;
                }
                $dueTimes[$time] = $scheduled;
            }

            if ($isToday && $leaveLatestDue) {
                array_pop($dueTimes);
            }

            foreach ($dueTimes as $time => $scheduled) {
                // Every dose today is given so "due now" stays predictable
                if (! $isToday && mt_rand() / mt_getrandmax() < $missChance) {
                    continue;
                }

                $administeredAt = $scheduled->copy()->addMinutes(rand(-20, 50))->min(now());
                $user = rand(1, 100) <= 10 ? $apiUser : $users[array_rand($users)];

                $logs[] = [
                    'pill_id' => $pill->id,
                    'user_id' => $user->id,
                    'administered_at' => $administeredAt,
                    'scheduled_time' => $time,
                    'notes' => rand(1, 100) <= 8 ? $notes[array_rand($notes)] : null,
                    'created_at' => $administeredAt,
                    'updated_at' => $administeredAt,
                ];
            }
        }

        foreach (array_chunk($logs, 500) as $chunk) {
            PillLog::insert($chunk);
        }
    }
}
