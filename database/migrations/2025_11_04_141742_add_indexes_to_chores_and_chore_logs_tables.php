<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('chores', function (Blueprint $table) {
            $table->index('occurrence_hours');
            $table->index('deleted_at');
        });

        Schema::table('chore_logs', function (Blueprint $table) {
            $table->index('completed_at');
            $table->index('deleted_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('chores', function (Blueprint $table) {
            $table->dropIndex(['occurrence_hours']);
            $table->dropIndex(['deleted_at']);
        });

        Schema::table('chore_logs', function (Blueprint $table) {
            $table->dropIndex(['completed_at']);
            $table->dropIndex(['deleted_at']);
        });
    }
};
