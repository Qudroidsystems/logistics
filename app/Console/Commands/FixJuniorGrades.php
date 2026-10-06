<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FixJuniorGrades extends Command
{
    protected $signature = 'grades:fix-junior
                            {--dry-run : Preview changes without saving anything}
                            {--class= : Only fix a specific class ID (for targeted testing)}
                            {--no-backup : Skip creating the backup table (not recommended)}
                            {--force : Skip all confirmation prompts}';

    protected $description = 'Re-grade junior class broadsheet records that were incorrectly saved with senior-style grades (A1/B2/B3/C4/C5/C6/D7/E8/F9 → A/B/C/D/F)';

    // Senior grade → correct junior grade mapping (based on score ranges, not direct conversion)
    // This is used only for display in the preview table.
    // Actual re-grading always uses the raw total score.
    private array $seniorToJuniorDisplay = [
        'A1' => 'A',   // 75+ → 70+ = A
        'B2' => 'B',   // 70-74 → 60-69 range needs score check
        'B3' => 'B',   // 65-69
        'C4' => 'C',   // 60-64
        'C5' => 'C',   // 55-59
        'C6' => 'C',   // 50-54
        'D7' => 'D',   // 45-49
        'E8' => 'D',   // 40-44 → still a pass (D) in junior grading
        'F9' => 'F',   // below 40 → F
    ];

    public function handle(): int
    {
        $isDry     = $this->option('dry-run');
        $targetClass = $this->option('class');
        $noBackup  = $this->option('no-backup');
        $force     = $this->option('force');

        $this->newLine();
        $this->line('╔══════════════════════════════════════════════════════════╗');
        $this->line('║         JUNIOR GRADE FIX — Vite-ESchool                 ║');
        $this->line('╚══════════════════════════════════════════════════════════╝');
        $this->newLine();

        if ($isDry) {
            $this->warn('  ▶ DRY RUN MODE — no changes will be saved.');
            $this->newLine();
        }

        // ── Step 1: Find junior classes ───────────────────────────────────────
        $this->info('STEP 1: Scanning junior classes...');

        $juniorClassQuery = DB::table('schoolclass_classcategory')
            ->join('classcategories', 'classcategories.id', '=', 'schoolclass_classcategory.classcategory_id')
            ->join('schoolclass', 'schoolclass.id', '=', 'schoolclass_classcategory.schoolclass_id')
            ->leftJoin('schoolarm', 'schoolarm.id', '=', 'schoolclass.arm')
            ->where('classcategories.is_senior', false)
            ->select(
                'schoolclass.id as class_id',
                'schoolclass.schoolclass as class_name',
                'schoolarm.arm as arm_name',
                'classcategories.id as category_id',
                'classcategories.category as category_name',
                'classcategories.is_senior'
            );

        if ($targetClass) {
            $juniorClassQuery->where('schoolclass.id', $targetClass);
            $this->warn("  ▶ Filtering to class ID: {$targetClass}");
        }

        $juniorClasses = $juniorClassQuery->get();

        if ($juniorClasses->isEmpty()) {
            $this->warn('  No junior classes found. Nothing to do.');
            return 0;
        }

        $this->info("  Found {$juniorClasses->count()} junior class(es):");
        $this->newLine();
        $this->table(
            ['Class ID', 'Class Name', 'Arm', 'Category', 'is_senior (must be 0)'],
            $juniorClasses->map(fn($c) => [
                $c->class_id,
                $c->class_name,
                $c->arm_name ?? '—',
                $c->category_name,
                $c->is_senior ? '⚠ YES (WRONG!)' : '✓ 0 (correct)',
            ])->toArray()
        );

        // ── Safety gate: abort if any senior class slipped through ────────────
        $badClasses = $juniorClasses->where('is_senior', true);
        if ($badClasses->isNotEmpty()) {
            $this->newLine();
            $this->error('  ABORT: Senior class(es) appeared in the junior query.');
            $this->error('  This means classcategories.is_senior is incorrectly set in your database.');
            $this->error('  Fix your classcategories data before running this command.');
            return 1;
        }

        if (!$force && !$isDry) {
            if (!$this->confirm('  Are ALL of the classes above genuinely junior classes?')) {
                $this->info('  Aborted by user.');
                return 0;
            }
        }

        $juniorClassIds = $juniorClasses->pluck('class_id');

        // ── Step 2: Find affected broadsheet records ───────────────────────────
        $this->newLine();
        $this->info('STEP 2: Finding incorrectly graded records...');

        $affectedQuery = DB::table('broadsheets')
            ->join('broadsheet_records', 'broadsheet_records.id', '=', 'broadsheets.broadSheet_record_id')
            ->join('studentRegistration', 'studentRegistration.id', '=', 'broadsheet_records.student_id')
            ->join('schoolclass', 'schoolclass.id', '=', 'broadsheet_records.schoolclass_id')
            ->leftJoin('schoolarm', 'schoolarm.id', '=', 'schoolclass.arm')
            ->leftJoin('schoolterm', 'schoolterm.id', '=', 'broadsheets.term_id')
            ->leftJoin('schoolsession', 'schoolsession.id', '=', 'broadsheet_records.session_id')
            ->whereIn('broadsheet_records.schoolclass_id', $juniorClassIds)
            ->whereIn('broadsheets.grade', ['A1', 'B2', 'B3', 'C4', 'C5', 'C6', 'D7', 'E8', 'F9'])
            ->select(
                'broadsheets.id as broadsheet_id',
                'broadsheets.total',
                'broadsheets.grade as current_grade',
                'broadsheets.remark as current_remark',
                'broadsheet_records.student_id',
                'broadsheet_records.schoolclass_id',
                'broadsheet_records.session_id',
                'broadsheets.term_id',
                DB::raw("CONCAT(studentRegistration.lastname, ', ', studentRegistration.firstname) as student_name"),
                'studentRegistration.admissionNo as admission_no',
                DB::raw("CONCAT(schoolclass.schoolclass, ' ', COALESCE(schoolarm.arm, '')) as class_name"),
                'schoolterm.term as term_name',
                'schoolsession.session as session_name'
            )
            ->orderBy('broadsheet_records.schoolclass_id')
            ->orderBy('broadsheets.term_id')
            ->orderBy('studentRegistration.lastname');

        $affected = $affectedQuery->get();

        if ($affected->isEmpty()) {
            $this->info('  ✓ No incorrectly graded records found. Database is clean.');
            return 0;
        }

        $this->warn("  Found {$affected->count()} record(s) with senior-style grades in junior classes.");
        $this->newLine();

        // ── Step 3: Preview the changes ────────────────────────────────────────
        $this->info('STEP 3: Preview of changes (first 30 shown):');
        $this->newLine();

        $preview = $affected->take(30)->map(function ($r) {
            $newGrade  = $this->recalcJuniorGrade((float) $r->total);
            $newRemark = $this->remark($newGrade);
            $gradeOk   = $newGrade !== $r->current_grade;
            return [
                $r->broadsheet_id,
                $r->admission_no,
                $r->student_name,
                $r->class_name,
                $r->term_name . ' / ' . $r->session_name,
                number_format((float)$r->total, 1),
                $r->current_grade . ' (' . $r->current_remark . ')',
                $newGrade  . ' (' . $newRemark . ')',
                $gradeOk ? '✓ will change' : '— same',
            ];
        })->toArray();

        $this->table(
            ['BS ID', 'Adm No', 'Student', 'Class', 'Term/Session', 'Total', 'Current Grade', 'New Grade', 'Action'],
            $preview
        );

        if ($affected->count() > 30) {
            $this->line('  ... and ' . ($affected->count() - 30) . ' more record(s) not shown.');
        }

        // ── Grade change summary ───────────────────────────────────────────────
        $this->newLine();
        $this->info('  Summary of grade changes:');
        $changeSummary = [];
        foreach ($affected as $r) {
            $newGrade = $this->recalcJuniorGrade((float) $r->total);
            $key = $r->current_grade . ' → ' . $newGrade;
            $changeSummary[$key] = ($changeSummary[$key] ?? 0) + 1;
        }
        arsort($changeSummary);
        foreach ($changeSummary as $change => $count) {
            $this->line("    {$change}: {$count} student(s)");
        }

        // ── Class breakdown ────────────────────────────────────────────────────
        $this->newLine();
        $this->info('  Breakdown by class:');
        $classSummary = $affected->groupBy('class_name')->map->count();
        foreach ($classSummary as $className => $count) {
            $this->line("    {$className}: {$count} record(s)");
        }

        // ── E8 special note ────────────────────────────────────────────────────
        $e8Count = $affected->where('current_grade', 'E8')->count();
        if ($e8Count > 0) {
            $this->newLine();
            $this->warn("  NOTE: {$e8Count} record(s) have grade E8 (score 40–44).");
            $this->warn("  In junior grading, 40–44 = D (Pass). These students REMAIN passes.");
            $this->warn("  Grade changes from E8 → D.");
        }

        if ($isDry) {
            $this->newLine();
            $this->warn('  DRY RUN complete. No changes were made.');
            $this->line('  Run without --dry-run to apply these changes.');
            return 0;
        }

        // ── Step 4: Create backup ──────────────────────────────────────────────
        if (!$noBackup) {
            $this->newLine();
            $this->info('STEP 4: Creating backup...');

            $backupTable = 'broadsheets_grade_backup_' . now()->format('Ymd_His');

            try {
                $ids = $affected->pluck('broadsheet_id')->implode(',');
                DB::statement("
                    CREATE TABLE `{$backupTable}` AS
                    SELECT
                        b.id                            AS broadsheet_id,
                        b.grade                         AS old_grade,
                        b.remark                        AS old_remark,
                        b.total,
                        b.cum,
                        b.bf,
                        b.term_id,
                        br.student_id,
                        br.schoolclass_id,
                        br.session_id,
                        NOW()                           AS backed_up_at
                    FROM broadsheets b
                    JOIN broadsheet_records br ON br.id = b.broadSheet_record_id
                    WHERE b.id IN ({$ids})
                ");

                $backupCount = DB::table($backupTable)->count();
                $this->info("  ✓ Backup created: `{$backupTable}` ({$backupCount} rows)");
                $this->line("  To rollback if needed, run:");
                $this->line("  UPDATE broadsheets b");
                $this->line("  JOIN `{$backupTable}` bk ON bk.broadsheet_id = b.id");
                $this->line("  SET b.grade = bk.old_grade, b.remark = bk.old_remark;");

            } catch (\Exception $e) {
                $this->error("  Backup failed: " . $e->getMessage());
                $this->error("  Aborting for safety. Use --no-backup to skip (not recommended).");
                return 1;
            }
        } else {
            $this->warn('  STEP 4: Skipping backup (--no-backup flag set).');
        }

        // ── Step 5: Final confirmation ─────────────────────────────────────────
        $this->newLine();
        if (!$force) {
            $this->warn("  You are about to update {$affected->count()} grade record(s) in the broadsheets table.");
            $this->warn("  This will change broadsheets.grade and broadsheets.remark only.");
            $this->warn("  Totals, cumulative scores, and positions are NOT affected.");
            $this->newLine();

            if (!$this->confirm("  Proceed with the fix?")) {
                $this->info('  Aborted by user. No changes made.');
                return 0;
            }
        }

        // ── Step 6: Apply the fix ──────────────────────────────────────────────
        $this->newLine();
        $this->info('STEP 5: Applying grade corrections...');

        $fixed    = 0;
        $skipped  = 0;
        $errors   = 0;
        $bar      = $this->output->createProgressBar($affected->count());
        $bar->start();

        try {
            DB::transaction(function () use ($affected, &$fixed, &$skipped, &$errors, $bar) {
                foreach ($affected as $bs) {
                    try {
                        $newGrade  = $this->recalcJuniorGrade((float) $bs->total);
                        $newRemark = $this->remark($newGrade);

                        // Skip if grade is already correct (shouldn't happen given our WHERE clause, but be safe)
                        if ($newGrade === $bs->current_grade) {
                            $skipped++;
                            $bar->advance();
                            continue;
                        }

                        DB::table('broadsheets')
                            ->where('id', $bs->broadsheet_id)
                            ->update([
                                'grade'  => $newGrade,
                                'remark' => $newRemark,
                            ]);

                        $fixed++;
                        $bar->advance();

                    } catch (\Exception $e) {
                        $errors++;
                        $bar->advance();
                        Log::error('FixJuniorGrades: failed on broadsheet ' . $bs->broadsheet_id, [
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
            });

        } catch (\Exception $e) {
            $bar->finish();
            $this->newLine(2);
            $this->error('  TRANSACTION FAILED — all changes rolled back.');
            $this->error('  Error: ' . $e->getMessage());
            Log::error('FixJuniorGrades: transaction rolled back', ['error' => $e->getMessage()]);
            return 1;
        }

        $bar->finish();
        $this->newLine(2);

        // ── Step 7: Summary ────────────────────────────────────────────────────
        $this->info('STEP 6: Done.');
        $this->newLine();
        $this->line('  ┌─────────────────────────────────┐');
        $this->line("  │  ✓ Fixed    : {$this->padLeft($fixed, 5)} record(s)       │");
        $this->line("  │  ↷ Skipped  : {$this->padLeft($skipped, 5)} record(s)       │");
        $this->line("  │  ✗ Errors   : {$this->padLeft($errors, 5)} record(s)       │");
        $this->line('  └─────────────────────────────────┘');
        $this->newLine();

        if ($errors > 0) {
            $this->warn("  {$errors} error(s) occurred. Check Laravel logs for details.");
        }

        if (!$noBackup) {
            $this->line("  Backup table preserved for rollback if needed.");
        }

        $this->newLine();
        $this->info('  NEXT STEPS:');
        $this->line('  1. Spot-check a few students in the database.');
        $this->line('     SELECT id, total, grade, remark FROM broadsheets WHERE id IN (pick IDs from preview above);');
        $this->line('  2. Open the JSS 1 scoresheet in the browser and verify grades look correct.');
        $this->line('  3. Click "Recalculate All Positions" on the scoresheet to refresh rankings.');
        $this->line('  4. Open the Promotion Management page and verify recommendations are now correct.');
        $this->line('  5. Once confirmed, you may drop the backup table:');
        if (!$noBackup) {
            $this->line("     DROP TABLE `broadsheets_grade_backup_...`;");
        }
        $this->newLine();

        Log::info('FixJuniorGrades completed', [
            'fixed'   => $fixed,
            'skipped' => $skipped,
            'errors'  => $errors,
            'classes' => $juniorClassIds->toArray(),
        ]);

        return $errors > 0 ? 1 : 0;
    }

    // ── Grade helpers ──────────────────────────────────────────────────────────

    private function recalcJuniorGrade(float $score): string
    {
        if ($score >= 70) return 'A';
        if ($score >= 60) return 'B';
        if ($score >= 50) return 'C';
        if ($score >= 40) return 'D';
        return 'F';
    }

    private function remark(string $grade): string
    {
        return match ($grade) {
            'A' => 'Excellent',
            'B' => 'Very Good',
            'C' => 'Good',
            'D' => 'Pass',
            default => 'Fail',
        };
    }

    private function padLeft(int $value, int $width): string
    {
        return str_pad((string) $value, $width, ' ', STR_PAD_LEFT);
    }
}
