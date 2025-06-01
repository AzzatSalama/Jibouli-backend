<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('action_on_order', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('user');
            $table->foreignId('order_id')->constrained('order');
            $table->string('action'); // e.g., 'accepted', 'rejected', 'delivered', 'canceled'
            $table->text('details')->nullable(); // Optional details of the action
            $table->timestamp('action_performed_at')->useCurrent();

            $table->index(['user_id', 'order_id']);
            $table->index('action');
        });
    }

    public function down()
    {
        Schema::dropIfExists('actions_on_order');
    }
};