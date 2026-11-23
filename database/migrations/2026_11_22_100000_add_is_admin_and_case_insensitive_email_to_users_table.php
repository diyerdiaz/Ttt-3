<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->boolean('is_admin')->default(false);
            $table->dropUnique('users_email_unique');
        });

        DB::statement('UPDATE users SET email = lower(btrim(email))');
        DB::statement('CREATE UNIQUE INDEX users_email_lower_unique ON users (lower(email))');
    }

    public function down(): void
    {
        DB::statement('DROP INDEX users_email_lower_unique');

        Schema::table('users', function (Blueprint $table): void {
            $table->unique('email');
            $table->dropColumn('is_admin');
        });
    }
};
