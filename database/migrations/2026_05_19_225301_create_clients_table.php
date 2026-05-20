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
            $table->id();
            $table->string('first_name', 50);
            $table->string('last_name', 50);
            $table->unsignedInteger('age');
            $table->string('address', 255);
            $table->date('birth_date');
            $table->string('tel', 50)->unique();
            $table->enum('skiing_level', ['beginner', 'medium', 'confirmed']);
            $table->decimal('height', 4, 2);
            $table->unsignedSmallInteger('weight');
            $table->unsignedTinyInteger('shoe_size');
            $table->string('password');
            $table->enum('role', ['client', 'admin'])->default('client');
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
