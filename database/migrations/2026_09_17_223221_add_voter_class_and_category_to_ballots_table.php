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
        Schema::table('ballots', function (Blueprint $table) {
            $table->string('voter_category', 20)->nullable()->after('candidate_id')->index();
            $table->string('voter_class', 100)->nullable()->after('voter_category')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ballots', function (Blueprint $table) {
            $table->dropColumn(['voter_category', 'voter_class']);
        });
    }
};
