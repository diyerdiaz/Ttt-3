<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const array COLUMNS = [
        'failed_jobs' => 'failed_at',
        'password_reset_tokens' => 'created_at',
    ];

    public function up(): void
    {
        foreach (self::COLUMNS as $table => $column) {
            DB::statement("ALTER TABLE {$table} ALTER COLUMN {$column} TYPE timestamptz USING {$column} AT TIME ZONE 'UTC'");
        }
    }

    public function down(): void
    {
        foreach (self::COLUMNS as $table => $column) {
            DB::statement("ALTER TABLE {$table} ALTER COLUMN {$column} TYPE timestamp(0) without time zone USING {$column} AT TIME ZONE 'UTC'");
        }
    }
};
