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
        Schema::create('delivery_person', function (Blueprint $table) {
            $table->id();
            $table->string('delivery_person_name');
            $table->string('delivery_phone')->unique()->nullable();
            $table->boolean('is_available')->default(false);
            $table->decimal('balance', 10, 2)->default(0.00);
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->foreignId('user_id')->constrained('user');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('delivery_person');
    }
};