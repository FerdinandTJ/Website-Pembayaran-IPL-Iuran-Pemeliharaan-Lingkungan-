<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cashflows', function (Blueprint $table) {
            $table->string('category', 30)->default('uncategorized');
            $table->index(['housing_id', 'category', 'type']);
        });
        DB::table('cashflows')->where('source', 'invoice')->update(['category' => 'ipl']);
    }

    public function down(): void
    {
        Schema::table('cashflows', function (Blueprint $table) {
            $table->dropIndex(['housing_id', 'category', 'type']);
            $table->dropColumn('category');
        });
    }
};
