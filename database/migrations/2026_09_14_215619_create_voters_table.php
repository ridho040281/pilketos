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
        Schema::create('voters', function (Blueprint $table) {
            $table->id();
            $table->string('nisn', 30)->nullable()->index();
            $table->string('name');
            $table->string('class', 50)->index();
            $table->enum('gender', ['L', 'P'])->nullable();
            $table->string('passcode', 20)->unique()->index();
            $table->boolean('has_voted')->default(false)->index();
            $table->timestamp('voted_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('voters');
    }
};
