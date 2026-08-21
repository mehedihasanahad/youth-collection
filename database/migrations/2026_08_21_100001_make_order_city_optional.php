<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The City / Thana field was removed from checkout, so new orders no longer
     * carry a value for it. Existing rows keep their historical data.
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('ship_city', 100)->nullable()->change();
        });
    }

    public function down(): void
    {
        DB::table('orders')->whereNull('ship_city')->update(['ship_city' => DB::raw('ship_district')]);

        Schema::table('orders', function (Blueprint $table) {
            $table->string('ship_city', 100)->nullable(false)->change();
        });
    }
};
