<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Existing tags all belong to chores
        Schema::table('tags', function (Blueprint $table) {
            $table->string('type', 20)->default('chore')->after('id');
            $table->dropUnique(['name']);
            $table->unique(['type', 'name']);
        });

        Schema::create('pill_tag', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pill_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tag_id')->constrained()->cascadeOnDelete();
            $table->unique(['pill_id', 'tag_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pill_tag');

        // Pill tag names may clash with chore tags once names are unique again
        DB::table('tags')->where('type', '!=', 'chore')->delete();

        Schema::table('tags', function (Blueprint $table) {
            $table->dropUnique(['type', 'name']);
            $table->unique('name');
            $table->dropColumn('type');
        });
    }
};
