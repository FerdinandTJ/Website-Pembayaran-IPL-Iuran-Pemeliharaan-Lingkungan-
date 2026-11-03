<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('invoices', 'title')) {
            Schema::table('invoices', function (Blueprint $table) {
                $table->string('title')->default('Tagihan IPL')->after('created_by');
            });
        }

        if (! Schema::hasIndex('invoices', 'invoices_user_id_billing_period_index')) {
            Schema::table('invoices', function (Blueprint $table) {
                $table->index(['user_id', 'billing_period']);
            });
        }

        if (Schema::hasIndex('invoices', 'invoices_user_id_billing_period_unique')) {
            Schema::table('invoices', function (Blueprint $table) {
                $table->dropUnique('invoices_user_id_billing_period_unique');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasIndex('invoices', 'invoices_user_id_billing_period_unique')) {
            Schema::table('invoices', function (Blueprint $table) {
                $table->unique(['user_id', 'billing_period']);
            });
        }

        if (Schema::hasIndex('invoices', 'invoices_user_id_billing_period_index')) {
            Schema::table('invoices', function (Blueprint $table) {
                $table->dropIndex(['user_id', 'billing_period']);
            });
        }

        if (Schema::hasColumn('invoices', 'title')) {
            Schema::table('invoices', function (Blueprint $table) {
                $table->dropColumn('title');
            });
        }
    }
};
