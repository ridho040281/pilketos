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
        Schema::table('election_settings', function (Blueprint $table) {
            $table->string('candidate_format', 30)->default('auto')->after('election_title');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('election_settings', function (Blueprint $table) {
            $table->dropColumn('candidate_format');
        });
    }
};
