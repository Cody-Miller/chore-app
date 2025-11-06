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
            $table->decimal('weight_percentage', 5, 2)->default(100.00)->after('completed_at');
            $table->uuid('split_group_id')->nullable()->after('weight_percentage');
            $table->boolean('is_split')->default(false)->after('split_group_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('chore_logs', function (Blueprint $table) {
            $table->dropColumn(['weight_percentage', 'split_group_id', 'is_split']);
        });
    }
};
