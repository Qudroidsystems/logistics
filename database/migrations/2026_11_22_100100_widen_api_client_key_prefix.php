<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Key prefixes look like  lgk_live_ab12cd34  (17 characters), one more than the column held, so issuing a key failed.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE api_clients ALTER COLUMN key_prefix TYPE varchar(24)');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE api_clients ALTER COLUMN key_prefix TYPE varchar(16)');
    }
};
