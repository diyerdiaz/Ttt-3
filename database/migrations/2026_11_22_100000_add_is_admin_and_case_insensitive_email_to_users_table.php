<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const array TIMESTAMP_COLUMNS = ['email_verified_at', 'created_at', 'updated_at'];

    public function up(): void
    {
        $this->assertNoEmailsDifferOnlyByCase();

        Schema::table('users', function (Blueprint $table): void {
            $table->boolean('is_admin')->default(false);
            $table->dropUnique('users_email_unique');
        });

        // Not reverted by down(): lowercasing is lossy and harmless for a case-insensitive identity.
        DB::statement('UPDATE users SET email = lower(btrim(email))');
        DB::statement('ALTER TABLE users ALTER COLUMN name TYPE varchar(120), ALTER COLUMN email TYPE varchar(254)');

        foreach (self::TIMESTAMP_COLUMNS as $column) {
            DB::statement("ALTER TABLE users ALTER COLUMN {$column} TYPE timestamptz USING {$column} AT TIME ZONE 'UTC'");
        }

        DB::statement('CREATE UNIQUE INDEX users_email_lower_unique ON users (lower(email))');
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS users_email_lower_unique');

        foreach (self::TIMESTAMP_COLUMNS as $column) {
            DB::statement("ALTER TABLE users ALTER COLUMN {$column} TYPE timestamp(0) without time zone USING {$column} AT TIME ZONE 'UTC'");
        }

        DB::statement('ALTER TABLE users ALTER COLUMN name TYPE varchar(255), ALTER COLUMN email TYPE varchar(255)');

        Schema::table('users', function (Blueprint $table): void {
            $table->unique('email');
            $table->dropColumn('is_admin');
        });
    }

    private function assertNoEmailsDifferOnlyByCase(): void
    {
        $conflicts = DB::table('users')
            ->selectRaw('lower(btrim(email)) as normalized, count(*) as total')
            ->groupByRaw('lower(btrim(email))')
            ->havingRaw('count(*) > 1')
            ->pluck('normalized');

        if ($conflicts->isNotEmpty()) {
            throw new RuntimeException(
                'Cannot enforce case-insensitive unique emails; resolve these duplicates first: '.$conflicts->implode(', '),
            );
        }
    }
};
