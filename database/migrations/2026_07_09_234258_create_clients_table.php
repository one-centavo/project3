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
        Schema::create('clients', function (Blueprint $table) {
            $table->uuid('uuid')->primary();
            $table->string('dni', 10);
            $table->string('first_name', 150);
            $table->string('second_name', 150)->nullable();
            $table->string('first_last_name', 150);
            $table->string('second_last_name', 150)->nullable();
            $table->string('email', 255)->unique();
            $table->string('phone_number', 10)->unique();
            $table->string('address', 255);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clients');
    }
};
