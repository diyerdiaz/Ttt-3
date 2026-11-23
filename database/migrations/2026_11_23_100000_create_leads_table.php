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
            CREATE TABLE leads (
                id bigint GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
                name varchar(150) NOT NULL,
                email varchar(254) NULL,
                phone varchar(32) NULL,
                company varchar(150) NULL,
                notes text NULL,
                status varchar(20) NOT NULL DEFAULT 'new',
                position integer NOT NULL,
                created_at timestamptz NULL,
                updated_at timestamptz NULL,
                CONSTRAINT leads_name_not_blank_check CHECK (btrim(name) <> ''),
                CONSTRAINT leads_status_check CHECK (status IN ('new', 'contacted', 'qualified', 'proposal', 'won', 'lost')),
                CONSTRAINT leads_position_check CHECK (position >= 0),
                CONSTRAINT leads_status_position_unique UNIQUE (status, position) DEFERRABLE INITIALLY DEFERRED
            )
            SQL);

        DB::statement('CREATE INDEX leads_email_lower_index ON leads (lower(email))');
    }

    public function down(): void
    {
        Schema::dropIfExists('leads');
    }
};
