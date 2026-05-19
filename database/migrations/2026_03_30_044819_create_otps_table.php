<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run migrations.
     */
    public function up(): void
    {
        Schema::create('otps', function (Blueprint $table) {

            $table->id();

            $table->string('mobile');

            $table->string('code');

            $table->string('type')->nullable();

            $table->uuid('tracking_code');

            $table->integer('attempts')->default(0);

            $table->boolean('is_verified')->default(false);

            $table->string('status')->default('pending');

            $table->dateTime('blocked_until')->nullable();

            $table->dateTime('expires_at')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('otps');
    }
};