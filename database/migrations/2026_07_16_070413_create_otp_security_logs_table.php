<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('otp_security_logs', function (Blueprint $table) {
            $table->id();
            $table->string('mobile')->nullable();
            $table->string('ip_address', 45);
            $table->string('user_agent')->nullable();
            $table->string('fingerprint', 64)->nullable();
            $table->string('country', 100)->nullable();
            $table->string('city', 100)->nullable();
            $table->string('region', 100)->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('event_type'); // send_otp, verify_otp, blocked, failed
            $table->string('status');     // success, failed, blocked
            $table->text('meta')->nullable();
            $table->timestamps();

            $table->index(['ip_address', 'event_type']);
            $table->index('fingerprint');
            $table->index('mobile');
        });

        Schema::create('blocked_ips', function (Blueprint $table) {
            $table->id();
            $table->string('ip_address', 45)->unique();
            $table->string('fingerprint', 64)->nullable();
            $table->string('reason')->nullable();
            $table->integer('hit_count')->default(1);
            $table->timestamp('blocked_until')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('blocked_ips');
        Schema::dropIfExists('otp_security_logs');
    }
};
