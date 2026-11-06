<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('push_deliveries');
        Schema::dropIfExists('push_subscriptions');
    }

    public function down(): void
    {
        // Restore only the previous schema; removed subscription data is not recoverable.
        (require database_path('migrations/2026_11_03_000003_create_web_push_tables.php'))->up();
    }
};
