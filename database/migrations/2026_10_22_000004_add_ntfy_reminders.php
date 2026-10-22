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
        Schema::table('users', function (Blueprint $table) {
            $table->string('ntfy_topic', 64)->nullable()->after('email');
        });

        Schema::table('chores', function (Blueprint $table) {
            $table->boolean('notifications_enabled')->default(false)->after('assigned_user_id');
        });

        Schema::table('pills', function (Blueprint $table) {
            $table->boolean('notifications_enabled')->default(false)->after('is_active');
        });

        Schema::create('sent_reminders', function (Blueprint $table) {
            $table->id();
            $table->morphs('remindable');
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('for_date');
            // Pills only; '' for chores so the unique index still applies (MySQL treats NULLs as distinct)
            $table->string('scheduled_time', 5)->default('');
            $table->timestamps();
            $table->unique(
                ['remindable_type', 'remindable_id', 'user_id', 'for_date', 'scheduled_time'],
                'sent_reminders_unique'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sent_reminders');

        Schema::table('pills', function (Blueprint $table) {
            $table->dropColumn('notifications_enabled');
        });

        Schema::table('chores', function (Blueprint $table) {
            $table->dropColumn('notifications_enabled');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('ntfy_topic');
        });
    }
};
