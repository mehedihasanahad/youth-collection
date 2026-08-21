<?php

declare(strict_types=1);

use App\Support\PhoneNumber;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Normalise every stored phone number to the canonical 01XXXXXXXXX form.
        DB::table('users')
            ->whereNotNull('phone')
            ->orderBy('id')
            ->chunkById(200, function ($users) {
                foreach ($users as $user) {
                    $normalised = PhoneNumber::normalize($user->phone);

                    if ($normalised !== $user->phone) {
                        DB::table('users')->where('id', $user->id)->update(['phone' => $normalised]);
                    }
                }
            });

        // 2. Clear duplicates so the unique index can be created. The oldest account
        //    keeps the number; newer ones are released and must re-claim it.
        $duplicates = DB::table('users')
            ->select('phone')
            ->whereNotNull('phone')
            ->groupBy('phone')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('phone');

        foreach ($duplicates as $phone) {
            $keepId = DB::table('users')->where('phone', $phone)->orderBy('id')->value('id');

            $releasedIds = DB::table('users')
                ->where('phone', $phone)
                ->where('id', '!=', $keepId)
                ->pluck('id');

            DB::table('users')->whereIn('id', $releasedIds)->update(['phone' => null]);

            Log::warning('Duplicate phone released during phone-login migration', [
                'phone' => $phone,
                'kept_user' => $keepId,
                'cleared_ids' => $releasedIds->all(),
            ]);
        }

        // 3. Email becomes optional — phone is now the primary identifier.
        Schema::table('users', function (Blueprint $table) {
            $table->string('email', 191)->nullable()->change();
        });

        // 4. Replace the plain phone index with a unique one.
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('idx_users_phone');
            $table->unique('phone', 'uniq_users_phone');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique('uniq_users_phone');
            $table->index('phone', 'idx_users_phone');
        });

        // Backfill a placeholder address for accounts created without an email so the
        // NOT NULL + UNIQUE constraint can be restored.
        DB::table('users')
            ->whereNull('email')
            ->orderBy('id')
            ->chunkById(200, function ($users) {
                foreach ($users as $user) {
                    DB::table('users')
                        ->where('id', $user->id)
                        ->update(['email' => 'user'.$user->id.'@placeholder.invalid']);
                }
            });

        Schema::table('users', function (Blueprint $table) {
            $table->string('email', 191)->nullable(false)->change();
        });
    }
};
