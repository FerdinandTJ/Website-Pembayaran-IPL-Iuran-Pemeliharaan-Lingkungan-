<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('whatsapp_deliveries', function (Blueprint $table) {
            $table->text('recipient_phone')->nullable();
            $table->string('recipient_name')->nullable();
            $table->text('recipient_house')->nullable();
            $table->timestamp('last_attempt_at')->nullable();
            $table->index(['housing_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::table('whatsapp_deliveries', function (Blueprint $table) {
            $table->dropIndex(['housing_id', 'created_at']);
            $table->dropColumn(['recipient_phone', 'recipient_name', 'recipient_house', 'last_attempt_at']);
        });
    }
};
