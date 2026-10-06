<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Check if the unique index already exists
        $indexExists = $this->indexExists('device_attendance_logs', 'uniq_device_punch');
        
        if (!$indexExists) {
            // First, clean any duplicate records
            $this->cleanDuplicateRecords();
            
            // Then add the unique index
            Schema::table('device_attendance_logs', function (Blueprint $table) {
                $table->unique(
                    ['device_serial', 'device_pin', 'punch_time'],
                    'uniq_device_punch'
                );
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('device_attendance_logs', function (Blueprint $table) {
            $table->dropUnique('uniq_device_punch');
        });
    }

    /**
     * Check if an index exists on a table.
     */
    private function indexExists(string $table, string $indexName): bool
    {
        $driver = DB::connection()->getDriverName();
        
        if ($driver === 'mysql') {
            $result = DB::select("
                SHOW INDEX FROM {$table} 
                WHERE Key_name = ?
            ", [$indexName]);
            
            return count($result) > 0;
        }
        
        if ($driver === 'pgsql') {
            $result = DB::select("
                SELECT indexname 
                FROM pg_indexes 
                WHERE tablename = ? AND indexname = ?
            ", [$table, $indexName]);
            
            return count($result) > 0;
        }
        
        if ($driver === 'sqlite') {
            $result = DB::select("
                SELECT name 
                FROM sqlite_master 
                WHERE type = 'index' 
                AND tbl_name = ? 
                AND name = ?
            ", [$table, $indexName]);
            
            return count($result) > 0;
        }
        
        // For other drivers, assume index doesn't exist
        return false;
    }

    /**
     * Clean duplicate records before adding the unique index.
     */
    private function cleanDuplicateRecords(): void
    {
        // Find duplicate combinations
        $duplicates = DB::table('device_attendance_logs')
            ->select('device_serial', 'device_pin', 'punch_time', DB::raw('COUNT(*) as count'))
            ->groupBy('device_serial', 'device_pin', 'punch_time')
            ->having('count', '>', 1)
            ->get();

        if ($duplicates->isEmpty()) {
            return;
        }

        // For each duplicate group, keep only the most recent record
        foreach ($duplicates as $duplicate) {
            // Get the IDs of records to keep (the most recent one)
            $keepIds = DB::table('device_attendance_logs')
                ->where('device_serial', $duplicate->device_serial)
                ->where('device_pin', $duplicate->device_pin)
                ->where('punch_time', $duplicate->punch_time)
                ->orderBy('created_at', 'desc')
                ->limit(1)
                ->pluck('id')
                ->toArray();

            // Delete all other duplicates
            DB::table('device_attendance_logs')
                ->where('device_serial', $duplicate->device_serial)
                ->where('device_pin', $duplicate->device_pin)
                ->where('punch_time', $duplicate->punch_time)
                ->whereNotIn('id', $keepIds)
                ->delete();
        }
    }
};