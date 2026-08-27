<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // One-shot token issued with the checkout form. The unique index is
            // what actually guarantees a resubmitted form cannot create a second
            // order — and therefore a second Purchase event.
            $table->string('idempotency_key', 64)->nullable()->unique()->after('order_number');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropUnique(['idempotency_key']);
            $table->dropColumn('idempotency_key');
        });
    }
};
