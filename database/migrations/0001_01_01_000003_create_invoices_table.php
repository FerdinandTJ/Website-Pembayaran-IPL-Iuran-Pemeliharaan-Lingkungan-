<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('housing_id')->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->string('account_number');
            $table->date('billing_period');
            $table->date('due_date');
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->string('status', 20)->default('unpaid')->index();
            $table->string('payment_proof_path')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('rejection_reason')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'billing_period']);
            $table->index(['housing_id', 'status', 'billing_period']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
