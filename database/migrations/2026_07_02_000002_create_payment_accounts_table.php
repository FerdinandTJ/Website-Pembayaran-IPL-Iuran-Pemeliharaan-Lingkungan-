<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('housing_id')->constrained()->restrictOnDelete();
            $table->string('bank_name');
            $table->string('account_number');
            $table->string('account_holder');
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();

            $table->unique(['housing_id', 'bank_name', 'account_number']);
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->foreignId('payment_account_id')->nullable()->after('created_by')->constrained()->nullOnDelete();
            $table->string('bank_name')->nullable()->after('account_number');
            $table->string('account_holder')->nullable()->after('bank_name');
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropConstrainedForeignId('payment_account_id');
            $table->dropColumn(['bank_name', 'account_holder']);
        });

        Schema::dropIfExists('payment_accounts');
    }
};
