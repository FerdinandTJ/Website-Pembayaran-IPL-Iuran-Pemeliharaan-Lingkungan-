<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('whatsapp_deliveries', function (Blueprint $table) {
            $table->id();
            $table->uuid('notification_id')->unique();
            $table->foreign('notification_id')->references('id')->on('notifications')->cascadeOnDelete();
            $table->foreignId('housing_id')->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
            $table->string('event', 20);
            $table->date('due_date');
            $table->string('status', 20)->default('pending');
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->timestamp('available_at');
            $table->timestamp('claimed_until')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->string('last_error', 50)->nullable();
            $table->timestamps();
            $table->index(['status', 'available_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_deliveries');
    }
};
