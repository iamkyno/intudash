<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            // SHA-256 hash of the API token; plaintext is shown once at generation.
            $table->string('api_token', 64)->nullable()->unique()->after('status');
            $table->string('api_token_last_four', 4)->nullable()->after('api_token');
            $table->timestamp('api_token_generated_at')->nullable()->after('api_token_last_four');
        });
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn(['api_token', 'api_token_last_four', 'api_token_generated_at']);
        });
    }
};
