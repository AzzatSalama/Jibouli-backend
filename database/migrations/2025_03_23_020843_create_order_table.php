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
        Schema::create('order', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('client');
            $table->foreignId('user_id')->constrained('user');
            $table->foreignId('delivery_person_id')->nullable()->constrained('delivery_person');
            $table->enum('status', ['pending', 'accepted', 'delivered', 'canceled'])->default('pending');
            $table->string('request');
            $table->text('client_notes')->nullable();
            $table->text('user_notes')->nullable();
            $table->text('delivery_person_notes')->nullable();
            $table->timestamp('delivered_canceled_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order');
    }
};