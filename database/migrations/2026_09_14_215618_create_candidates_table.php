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
        Schema::create('candidates', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('candidate_number')->unique();
            $table->string('leader_name');
            $table->string('co_leader_name');
            $table->text('vision');
            $table->text('mission');
            $table->string('photo_path')->nullable();
            $table->string('color_tag', 20)->default('#4f46e5');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('candidates');
    }
};
