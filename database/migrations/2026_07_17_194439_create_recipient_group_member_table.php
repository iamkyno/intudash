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
        Schema::create('recipient_group_member', function (Blueprint $table) {
            $table->id();
            $table->foreignId('recipient_group_id')->constrained()->cascadeOnDelete();
            $table->foreignId('client_recipient_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['recipient_group_id', 'client_recipient_id'], 'group_member_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recipient_group_member');
    }
};
