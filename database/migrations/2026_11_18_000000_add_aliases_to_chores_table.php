<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Other names a chore goes by ("litter box", "scoop the litter"), used by the API's name matching.
     */
    public function up(): void
    {
        Schema::table('chores', function (Blueprint $table) {
            $table->json('aliases')->nullable()->after('description');
        });
    }

    public function down(): void
    {
        Schema::table('chores', function (Blueprint $table) {
            $table->dropColumn('aliases');
        });
    }
};
