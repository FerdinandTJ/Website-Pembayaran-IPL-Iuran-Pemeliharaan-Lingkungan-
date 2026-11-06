<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('whatsapp_deliveries', function (Blueprint $table) {
            $table->foreignId('receipt_id')->nullable()->unique()->constrained()->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('whatsapp_deliveries', function (Blueprint $table) {
            $table->dropUnique(['receipt_id']);
            $table->dropConstrainedForeignId('receipt_id');
        });
    }
};
