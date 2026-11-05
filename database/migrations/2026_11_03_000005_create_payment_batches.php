<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('housing_id')->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->string('method', 20);
            $table->string('status', 20)->index();
            $table->decimal('total_amount', 15, 2);
            $table->json('snapshot');
            $table->string('proof_path')->nullable();
            $table->text('note')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamp('submitted_at');
            $table->timestamp('verified_at')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['housing_id', 'user_id', 'status']);
        });
        Schema::create('payment_batch_invoice', function (Blueprint $table) {
            $table->foreignId('payment_batch_id')->constrained()->restrictOnDelete();
            $table->foreignId('invoice_id')->constrained()->restrictOnDelete();
            $table->primary(['payment_batch_id', 'invoice_id']);
        });
        Schema::table('invoices', function (Blueprint $table) {
            $table->foreignId('payment_batch_id')->nullable()->constrained()->restrictOnDelete();
        });
        Schema::table('cashflows', function (Blueprint $table) {
            $table->foreignId('payment_batch_id')->nullable()->unique()->constrained()->restrictOnDelete();
        });
        Schema::table('receipts', function (Blueprint $table) {
            $table->unsignedBigInteger('invoice_id')->nullable()->change();
            $table->foreignId('payment_batch_id')->nullable()->unique()->constrained()->restrictOnDelete();
        });
    }

    public function down(): void
    {
        // Group receipts have no individual invoice_id. Refuse a rollback that would discard them.
        if (DB::table('payment_batches')->exists()) {
            throw new RuntimeException('Payment batches exist. Restore a coordinated database backup instead of dropping payment history.');
        }
        foreach (['receipts', 'cashflows', 'invoices'] as $name) {
            Schema::table($name, fn (Blueprint $table) => $table->dropConstrainedForeignId('payment_batch_id'));
        }
        Schema::table('receipts', fn (Blueprint $table) => $table->unsignedBigInteger('invoice_id')->nullable(false)->change());
        Schema::dropIfExists('payment_batch_invoice');
        Schema::dropIfExists('payment_batches');
    }
};
