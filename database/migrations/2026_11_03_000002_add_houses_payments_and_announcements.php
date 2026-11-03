<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('houses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('housing_id')->constrained()->restrictOnDelete();
            $table->string('code', 100);
            $table->string('address', 1000);
            $table->string('occupancy', 20)->default('occupied');
            $table->string('owner_name')->nullable();
            $table->string('occupant_name')->nullable();
            $table->foreignId('responsible_user_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->string('vacant_billing', 20)->default('full');
            $table->decimal('vacant_amount', 15, 2)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unique(['housing_id', 'code']);
        });
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('house_id')->nullable()->constrained()->restrictOnDelete();
        });
        Schema::table('invoices', function (Blueprint $table) {
            $table->foreignId('house_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('house_label', 1200)->nullable();
            $table->string('payment_method', 20)->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->text('payment_note')->nullable();
        });
        Schema::create('house_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('house_id')->constrained()->restrictOnDelete();
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->json('data');
            $table->timestamps();
        });
        Schema::create('receipts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->unique()->constrained()->restrictOnDelete();
            $table->string('number')->unique();
            $table->json('snapshot');
            $table->timestamps();
        });
        Schema::create('announcements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('housing_id')->constrained()->restrictOnDelete();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->string('title');
            $table->text('body');
            $table->string('status', 20)->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->timestamp('archived_at')->nullable();
            $table->timestamps();
            $table->index(['housing_id', 'status', 'published_at']);
        });
        Schema::create('resident_imports', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('housing_id')->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->longText('rows');
            $table->timestamp('expires_at');
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
        // Preserve existing addresses and invoice ownership. Never infer owners
        // or merge different addresses using fuzzy matching.
        DB::table('users')->where('role', 'warga')->whereNotNull('housing_id')->orderBy('id')->chunkById(100, function ($users) {
            foreach ($users as $user) {
                $address = trim($user->house_address ?? '');
                $existing = $address !== '' ? DB::table('houses')->where('housing_id', $user->housing_id)->where('address', $address)->first() : null;
                $houseId = $existing?->id ?? DB::table('houses')->insertGetId([
                    'housing_id' => $user->housing_id, 'code' => 'LEGACY-'.$user->id,
                    'address' => $address ?: 'Alamat belum dilengkapi', 'occupancy' => 'occupied',
                    'responsible_user_id' => $user->id, 'vacant_billing' => 'full',
                    'created_at' => now(), 'updated_at' => now(),
                ]);
                DB::table('users')->where('id', $user->id)->update(['house_id' => $houseId]);
                DB::table('invoices')->where('user_id', $user->id)->update(['house_id' => $houseId, 'house_label' => $address ?: 'Alamat belum dilengkapi']);
            }
        });
        DB::table('invoices')->where('status', 'verified')->update(['payment_method' => 'transfer', 'paid_at' => DB::raw('verified_at')]);
        DB::table('invoices')->whereIn('status', ['pending', 'rejected'])->update(['payment_method' => 'transfer']);
    }

    public function down(): void
    {
        Schema::dropIfExists('resident_imports');
        Schema::dropIfExists('announcements');
        Schema::dropIfExists('receipts');
        Schema::dropIfExists('house_histories');
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropConstrainedForeignId('house_id');
            $table->dropColumn(['house_label', 'payment_method', 'paid_at', 'payment_note']);
        });
        Schema::table('users', fn (Blueprint $table) => $table->dropConstrainedForeignId('house_id'));
        Schema::dropIfExists('houses');
    }
};
