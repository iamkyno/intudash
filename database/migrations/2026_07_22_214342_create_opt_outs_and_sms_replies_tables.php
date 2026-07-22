<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Global do-not-contact list — a STOP from a number applies everywhere.
        Schema::create('opt_outs', function (Blueprint $table) {
            $table->id();
            $table->string('phone_normalized')->unique();
            $table->string('channel')->default('sms');
            $table->string('source')->default('sms_reply'); // sms_reply | manual
            $table->string('keyword')->nullable();           // the word that triggered it
            $table->timestamp('opted_out_at');
            $table->timestamps();
        });

        // Every inbound SMS reply, opt-out or not — so responses are never lost.
        Schema::create('sms_replies', function (Blueprint $table) {
            $table->id();
            $table->string('from_number');
            $table->string('phone_normalized')->nullable();
            $table->text('message')->nullable();
            $table->boolean('is_opt_out')->default(false);
            $table->json('raw_payload')->nullable();
            $table->timestamps();

            $table->index('phone_normalized');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sms_replies');
        Schema::dropIfExists('opt_outs');
    }
};
