<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement(<<<'SQL'
            CREATE TABLE activities (
                id bigint GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
                lead_id bigint NOT NULL,
                type varchar(20) NULL,
                description text NOT NULL,
                occurred_at timestamptz NOT NULL,
                created_at timestamptz NULL,
                updated_at timestamptz NULL,
                CONSTRAINT activities_lead_id_foreign FOREIGN KEY (lead_id) REFERENCES leads (id) ON DELETE CASCADE,
                CONSTRAINT activities_type_check CHECK (type IN ('call', 'email', 'meeting', 'note')),
                CONSTRAINT activities_description_not_blank_check CHECK (btrim(description) <> '')
            )
            SQL);

        DB::statement('CREATE INDEX activities_lead_id_occurred_at_id_index ON activities (lead_id, occurred_at DESC, id DESC)');
    }

    public function down(): void
    {
        Schema::dropIfExists('activities');
    }
};
