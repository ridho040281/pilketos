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
            $table->string('school_api_url')->nullable()->after('show_quick_count');
            $table->string('school_api_key')->nullable()->after('school_api_url');
            $table->string('school_api_client_id')->nullable()->after('school_api_key');
            $table->string('school_api_secret')->nullable()->after('school_api_client_id');
            $table->string('pilketos_api_key', 64)->nullable()->unique()->after('school_api_secret');
            $table->timestamp('last_sync_at')->nullable()->after('pilketos_api_key');
            $table->unsignedInteger('last_sync_count')->default(0)->after('last_sync_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('election_settings', function (Blueprint $table) {
            $table->dropColumn([
                'school_api_url',
                'school_api_key',
                'school_api_client_id',
                'school_api_secret',
                'pilketos_api_key',
                'last_sync_at',
                'last_sync_count',
            ]);
        });
    }
};
