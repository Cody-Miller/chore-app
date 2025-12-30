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
        Schema::table('pets', function (Blueprint $table) {
            $table->index('slug');
            $table->index('deleted_at');
        });

        Schema::table('pills', function (Blueprint $table) {
            $table->index('pet_id');
            $table->index('slug');
            $table->index('deleted_at');
        });

        Schema::table('pill_logs', function (Blueprint $table) {
            $table->index('pill_id');
            $table->index('user_id');
            $table->index('administered_at');
            $table->index('deleted_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pets', function (Blueprint $table) {
            $table->dropIndex(['slug']);
            $table->dropIndex(['deleted_at']);
        });

        Schema::table('pills', function (Blueprint $table) {
            $table->dropIndex(['pet_id']);
            $table->dropIndex(['slug']);
            $table->dropIndex(['deleted_at']);
        });

        Schema::table('pill_logs', function (Blueprint $table) {
            $table->dropIndex(['pill_id']);
            $table->dropIndex(['user_id']);
            $table->dropIndex(['administered_at']);
            $table->dropIndex(['deleted_at']);
        });
    }
};
