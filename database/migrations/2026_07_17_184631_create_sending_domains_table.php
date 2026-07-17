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
        Schema::create('sending_domains', function (Blueprint $table) {
            $table->id();
            // Null client_id = agency-wide domain (used as the fallback default).
            $table->foreignId('client_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('domain');
            $table->string('label')->nullable();
            $table->string('verification_token')->nullable();
            $table->json('dkim_tokens')->nullable();
            $table->enum('verification_status', ['pending', 'verified', 'failed'])->default('pending');
            $table->enum('dkim_status', ['pending', 'verified', 'failed'])->default('pending');
            $table->timestamp('last_checked_at')->nullable();
            $table->timestamps();

            $table->unique(['client_id', 'domain']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sending_domains');
    }
};
