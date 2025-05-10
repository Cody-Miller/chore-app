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
        Schema::table('chore_logs', function (Blueprint $table) {
            // Change completed_at default to CURRENT_TIMESTAMP dynamically
            $table->dateTime('completed_at')->useCurrent()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('chore_logs', function (Blueprint $table) {
            $table->timestamp('completed_at')->change();
        });
    }
};
