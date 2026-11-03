<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropConstrainedForeignId('payment_account_id');
            $table->dropColumn(['account_number', 'bank_name', 'account_holder']);
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->foreignId('payment_account_id')->nullable()->after('created_by')->constrained()->nullOnDelete();
            $table->string('account_number')->nullable();
            $table->string('bank_name')->nullable();
            $table->string('account_holder')->nullable();
        });
    }
};
