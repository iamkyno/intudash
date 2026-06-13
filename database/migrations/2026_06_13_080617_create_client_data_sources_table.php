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
        Schema::create('client_data_sources', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->enum('driver', ['mysql', 'pgsql', 'sqlsrv']);
            $table->string('host');
            $table->unsignedSmallInteger('port')->default(3306);
            $table->string('database');
            $table->string('username');
            $table->text('password_encrypted')->nullable();
            $table->string('table_or_view')->nullable();
            $table->text('custom_query')->nullable();
            $table->string('col_name')->nullable();
            $table->string('col_phone')->nullable();
            $table->string('col_email')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('client_data_sources');
    }
};
