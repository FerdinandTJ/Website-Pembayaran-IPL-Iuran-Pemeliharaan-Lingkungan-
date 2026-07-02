<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cashflows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('housing_id')->constrained()->restrictOnDelete();
            $table->foreignId('invoice_id')->nullable()->unique()->constrained()->restrictOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('name');
            $table->enum('type', ['income', 'expense']);
            $table->enum('source', ['manual', 'invoice'])->default('manual');
            $table->decimal('total_cashflow', 15, 2);
            $table->timestamps();

            $table->index(['housing_id', 'type', 'source']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cashflows');
    }
};
