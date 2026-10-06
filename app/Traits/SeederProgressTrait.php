<?php
// app/Traits/SeederProgressTrait.php

namespace App\Traits;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

trait SeederProgressTrait
{
    /**
     * Show progress with emoji indicators
     */
    protected function showProgress($message, $emoji = '📋')
    {
        $this->command->info("  {$emoji} {$message}");
    }

    /**
     * Show success message
     */
    protected function showSuccess($message)
    {
        $this->command->info("  ✅ {$message}");
    }

    /**
     * Show error message
     */
    protected function showError($message)
    {
        $this->command->error("  ❌ {$message}");
    }

    /**
     * Show warning message
     */
    protected function showWarning($message)
    {
        $this->command->warn("  ⚠️  {$message}");
    }

    /**
     * Show info message
     */
    protected function showInfo($message)
    {
        $this->command->line("  ℹ️  {$message}");
    }

    /**
     * Display a section header
     */
    protected function sectionHeader($title, $emoji = '📦')
    {
        $this->command->info('');
        $this->command->info('┌─────────────────────────────────────────────────────────────────────────────┐');
        $this->command->info("│ {$emoji} {$title}" . str_repeat(' ', 65 - strlen($title)) . '│');
        $this->command->info('└─────────────────────────────────────────────────────────────────────────────┘');
        $this->command->info('');
    }

    /**
     * Display a separator line
     */
    protected function separator()
    {
        $this->command->info('─────────────────────────────────────────────────────────────────────────────────');
    }

    /**
     * Show database statistics
     */
    protected function showDatabaseStats()
    {
        $tables = [
            'users' => '👤 Users',
            'studentRegistration' => '👨‍🎓 Students',
            'school_bill' => '💰 School Bills',
            'scholarships' => '🎓 Scholarships',
            'discounts' => '🏷️ Discounts',
            'payments' => '💵 Payments',
        ];

        $this->command->info('');
        $this->command->info('📊 Database Statistics:');

        foreach ($tables as $table => $label) {
            if (Schema::hasTable($table)) {
                $count = DB::table($table)->count();
                $this->command->info("  {$label}: " . number_format($count));
            }
        }
    }

    /**
     * Time a callback function
     */
    protected function timeOperation($callback, $name)
    {
        $start = microtime(true);
        $result = $callback();
        $end = microtime(true);
        $time = round(($end - $start) * 1000, 2);

        $this->command->info("  ⏱️  {$name} took {$time}ms");

        return $result;
    }
}
