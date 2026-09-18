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
            $table->string('headmaster_name')->nullable()->after('end_time');
            $table->string('headmaster_nip', 50)->nullable()->after('headmaster_name');
            $table->string('pembina_name')->nullable()->after('headmaster_nip');
            $table->string('pembina_nip', 50)->nullable()->after('pembina_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('election_settings', function (Blueprint $table) {
            $table->dropColumn([
                'headmaster_name',
                'headmaster_nip',
                'pembina_name',
                'pembina_nip',
            ]);
        });
    }
};
