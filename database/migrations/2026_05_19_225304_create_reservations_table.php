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
        Schema::create('reservations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('clients')->cascadeOnDelete();
            $table->dateTime('check_in')->index();
            $table->dateTime('check_out')->index();
            $table->dateTime('purchase_date')->nullable();
            $table->enum('status', ['not paid', 'paid', 'approaching', 'in process', 'finished', 'cancelled'])->default('not paid')->index();
            $table->decimal('total_price', 10, 2)->nullable();
            $table->string('stripe_session_id')->nullable()->index();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reservations');
    }
};
