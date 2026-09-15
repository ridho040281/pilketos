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
        Schema::create('election_settings', function (Blueprint $table) {
            $table->id();
            $table->string('school_name')->default('SMA Negeri 1 Sekolah Impian');
            $table->string('school_logo')->nullable();
            $table->string('election_title')->default('Pemilihan Ketua & Wakil Ketua OSIS');
            $table->string('academic_year')->default('2026/2027');
            $table->dateTime('start_time')->nullable();
            $table->dateTime('end_time')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('show_quick_count')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('election_settings');
    }
};
