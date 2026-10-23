<?php

use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Times used to be written as New York wall-clock times. Rewrite every stored
 * time as UTC, which the app now uses for storage.
 *
 * Rows are read in the session timezone they were written with (the server's
 * global setting) and written back in UTC, so this is correct whatever timezone
 * the MySQL server runs in, for both DATETIME and TIMESTAMP columns.
 * Times in the repeated hour when clocks fall back in November are ambiguous
 * and are read as the first (daylight saving) occurrence.
 */
return new class extends Migration
{
    /**
     * The app's timezone before this migration.
     */
    const OLD_TIMEZONE = 'America/New_York';

    /**
     * Each table's key column and the time columns to convert.
     */
    const COLUMNS = [
        'users' => ['id', ['email_verified_at', 'created_at', 'updated_at']],
        'chores' => ['id', ['created_at', 'updated_at', 'deleted_at']],
        'chore_logs' => ['id', ['completed_at', 'created_at', 'updated_at', 'deleted_at']],
        'chore_snoozes' => ['id', ['snoozed_until', 'created_at', 'updated_at']],
        'pets' => ['id', ['created_at', 'updated_at', 'deleted_at']],
        'pills' => ['id', ['created_at', 'updated_at', 'deleted_at']],
        'pill_logs' => ['id', ['administered_at', 'created_at', 'updated_at', 'deleted_at']],
        'tags' => ['id', ['created_at', 'updated_at']],
        'personal_access_tokens' => ['id', ['last_used_at', 'expires_at', 'created_at', 'updated_at']],
        'password_reset_tokens' => ['email', ['created_at']],
        'failed_jobs' => ['id', ['failed_at']],
    ];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $this->convert(self::OLD_TIMEZONE, 'UTC', '@@global.time_zone', "'+00:00'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $this->convert('UTC', self::OLD_TIMEZONE, "'+00:00'", '@@global.time_zone');
    }

    private function convert(string $from, string $to, string $readSessionZone, string $writeSessionZone): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        try {
            foreach (self::COLUMNS as $table => [$key, $columns]) {
                if (!Schema::hasTable($table)) {
                    continue;
                }

                DB::statement("SET time_zone = {$readSessionZone}");
                $rows = DB::table($table)->get([$key, ...$columns]);

                DB::statement("SET time_zone = {$writeSessionZone}");
                foreach ($rows as $row) {
                    $changes = [];
                    foreach ($columns as $column) {
                        if ($row->$column !== null) {
                            $changes[$column] = Carbon::parse($row->$column, $from)->setTimezone($to)->format('Y-m-d H:i:s');
                        }
                    }

                    if ($changes) {
                        DB::table($table)->where($key, $row->$key)->update($changes);
                    }
                }
            }
        } finally {
            // Back to the connection's configured timezone
            DB::statement("SET time_zone = '".config('database.connections.mysql.timezone', '+00:00')."'");
        }
    }
};
