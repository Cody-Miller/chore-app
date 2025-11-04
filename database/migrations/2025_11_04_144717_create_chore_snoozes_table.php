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
        Schema::create('chore_snoozes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chore_id')->constrained('chores')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->timestamp('snoozed_until');
            $table->timestamps();

            // Ensure a user can only have one active snooze per chore
            $table->unique(['chore_id', 'user_id']);

            // Index for efficient queries
            $table->index('snoozed_until');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chore_snoozes');
    }
};
