<?php

use App\Support\Database\PartitionManager;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        // Runs before any data exists, so the DEFAULT partitions are still empty and the
        // new range partitions can be attached without moving rows.
        (new PartitionManager())->ensure();
    }

    public function down(): void
    {
        // Partitions are dropped together with their parent tables.
    }
};
