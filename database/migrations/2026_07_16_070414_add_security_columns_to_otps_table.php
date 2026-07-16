<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('otps', function (Blueprint $table) {
            $table->string('ip_address', 45)->nullable()->after('status');
            $table->string('user_agent')->nullable()->after('ip_address');
            $table->string('fingerprint', 64)->nullable()->after('user_agent');
            $table->string('country', 100)->nullable()->after('fingerprint');
            $table->string('city', 100)->nullable()->after('country');
        });
    }

    public function down(): void
    {
        Schema::table('otps', function (Blueprint $table) {
            $table->dropColumn(['ip_address', 'user_agent', 'fingerprint', 'country', 'city']);
        });
    }
};
