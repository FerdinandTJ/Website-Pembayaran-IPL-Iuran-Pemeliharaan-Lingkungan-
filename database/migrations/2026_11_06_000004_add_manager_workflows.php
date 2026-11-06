<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoice_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('housing_id')->constrained()->restrictOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('name');
            $table->string('title');
            $table->json('components');
            $table->string('recipient_mode', 20)->default('all');
            $table->json('resident_ids')->nullable();
            $table->unsignedTinyInteger('due_day')->default(10);
            $table->unsignedTinyInteger('due_month_offset')->default(0);
            $table->boolean('apply_house_tariff')->default(true);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['housing_id', 'is_active']);
        });
        Schema::create('invoice_template_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_template_id')->constrained()->restrictOnDelete();
            $table->foreignId('housing_id')->constrained()->restrictOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->date('billing_period');
            $table->unsignedInteger('issued_count')->default(0);
            $table->unsignedInteger('skipped_count')->default(0);
            $table->json('snapshot');
            $table->timestamps();
            $table->unique(['invoice_template_id', 'billing_period']);
        });
        Schema::table('invoices', function (Blueprint $table) {
            $table->foreignId('invoice_template_run_id')->nullable()->constrained()->restrictOnDelete();
        });
        Schema::create('arrears_follow_ups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('housing_id')->constrained()->restrictOnDelete();
            $table->foreignId('resident_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 30);
            $table->date('next_action_date')->nullable();
            $table->text('note');
            $table->timestamps();
            $table->index(['housing_id', 'resident_id', 'id']);
        });
        Schema::create('accounting_periods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('housing_id')->constrained()->restrictOnDelete();
            $table->date('period');
            $table->string('status', 20)->default('open');
            $table->json('snapshot')->nullable();
            $table->foreignId('closed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('closed_at')->nullable();
            $table->text('reopen_reason')->nullable();
            $table->timestamps();
            $table->unique(['housing_id', 'period']);
        });
        Schema::table('cashflows', function (Blueprint $table) {
            $table->date('transaction_date')->nullable();
            $table->string('attachment_path')->nullable();
            $table->index(['housing_id', 'transaction_date']);
        });
        DB::table('cashflows')->whereNotNull('created_at')->update(['transaction_date' => DB::raw('DATE(created_at)')]);
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('housing_id')->constrained()->restrictOnDelete();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('actor_name');
            $table->string('entity_type', 60);
            $table->unsignedBigInteger('entity_id');
            $table->string('event', 20);
            $table->json('before')->nullable();
            $table->json('after')->nullable();
            $table->timestamp('created_at');
            $table->index(['housing_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
        Schema::table('cashflows', function (Blueprint $table) {
            $table->dropIndex(['housing_id', 'transaction_date']);
            $table->dropColumn(['transaction_date', 'attachment_path']);
        });
        Schema::dropIfExists('accounting_periods');
        Schema::dropIfExists('arrears_follow_ups');
        Schema::table('invoices', fn (Blueprint $table) => $table->dropConstrainedForeignId('invoice_template_run_id'));
        Schema::dropIfExists('invoice_template_runs');
        Schema::dropIfExists('invoice_templates');
    }
};
