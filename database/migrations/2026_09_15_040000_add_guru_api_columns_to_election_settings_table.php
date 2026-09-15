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
            $table->string('guru_api_url')->nullable()->default('https://jadwal.mtsn1blitar.sch.id/api/v1/sync/guru')->after('last_sync_count');
            $table->string('guru_api_client_id')->nullable()->default('client_edp3yftse3bxcrcf')->after('guru_api_url');
            $table->string('guru_api_secret')->nullable()->default('EgUmiD5xGq1v6IdUMlTvxGModoUOoCjCIKncKu2I')->after('guru_api_client_id');
            $table->string('guru_api_token', 500)->nullable()->default('UOcvFMOE4fPisQxFDh1W7Q77wx6glE9P87wkcOswAd6RtxVLFvSmV3rrbXGW')->after('guru_api_secret');
            $table->timestamp('last_guru_sync_at')->nullable()->after('guru_api_token');
            $table->unsignedInteger('last_guru_sync_count')->default(0)->after('last_guru_sync_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('election_settings', function (Blueprint $table) {
            $table->dropColumn([
                'guru_api_url',
                'guru_api_client_id',
                'guru_api_secret',
                'guru_api_token',
                'last_guru_sync_at',
                'last_guru_sync_count',
            ]);
        });
    }
};
