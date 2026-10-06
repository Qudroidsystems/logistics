<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\Models\Permission;

class DatabaseSeeder extends Seeder
{
    /**
     * Track which seeders have been run.
     */
    protected array $runSeeders = [];

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Load already run seeders from the database
        $this->loadRunSeeders();

        // Start timing
        $startTime = microtime(true);

        $seededCount = 0;
        $failedCount = 0;
        $skippedCount = 0;

        $this->command->info('');
        $this->command->info(
            '╔═══════════════════════════════════════════════════════════════════════════════╗'
        );
        $this->command->info(
            '║                         🚀 DATABASE SEEDING PROCESS                          ║'
        );
        $this->command->info(
            '║                         Starting at: ' . now()->format('Y-m-d H:i:s') . '                         ║'
        );
        $this->command->info(
            '╚═══════════════════════════════════════════════════════════════════════════════╝'
        );
        $this->command->info('');

        // ============================================================
        // PART 1: CORE PERMISSIONS & FOUNDATION
        // ============================================================

        $this->printSection(
            '🔐 PART 1: CORE PERMISSIONS & FOUNDATION'
        );

        $result = $this->safeCall(
            PermissionTableSeeder::class,
            'PermissionTableSeeder',
            '🔐 Seeding permission tables...'
        );
        $this->updateStats(
            $result,
            $seededCount,
            $failedCount,
            $skippedCount
        );

        $result = $this->safeCall(
            UserTableSeeder::class,
            'UserTableSeeder',
            '👤 Seeding user data...'
        );
        $this->updateStats(
            $result,
            $seededCount,
            $failedCount,
            $skippedCount
        );

        $result = $this->safeCall(
            TermTableSeeder::class,
            'TermTableSeeder',
            '📅 Seeding term data...'
        );
        $this->updateStats(
            $result,
            $seededCount,
            $failedCount,
            $skippedCount
        );

        $this->command->info('');

        // ============================================================
        // PART 2: ACADEMIC PERMISSIONS
        // ============================================================

        $this->printSection(
            '🎓 PART 2: ACADEMIC PERMISSIONS'
        );

        $academicSeeders = [
            'ViewClassPermissionTableSeeder' =>
                '📖 Seeding class view permissions...',

            'CompulsorySubjectsPermissionTableSeeder' =>
                '📚 Seeding compulsory subjects permissions...',

            'MockSubjectVettingsPermissionTableSeeder' =>
                '✏️ Seeding mock subject vettings permissions...',

            'MyClassMySubjectPermissionTableSeeder' =>
                '🏫 Seeding my class/subject permissions...',

            'MyMockSubjectVettingsPermissionTableSeeder' =>
                '📝 Seeding my mock subject vettings...',

            'MySubjectVettingsPermissionTableSeeder' =>
                '📋 Seeding my subject vettings...',

            'PrincipalscommentPermissionTableSeeder' =>
                '👔 Seeding principal comments permissions...',

            'SchoolInformationPermissionTableSeeder' =>
                '🏢 Seeding school information permissions...',

            'StudentMockReportPermissionTableSeeder' =>
                '📊 Seeding student mock report permissions...',

            'StudentPermissionTableSeeder' =>
                '👨‍🎓 Seeding student permissions...',

            'StudentReportPermissionTableSeeder' =>
                '📈 Seeding student report permissions...',

            'StudentStatusTableSeeder' =>
                '🏷️ Seeding student status data...',

            'SubjectClassResultRoomOperationPermissionTableSeeder' =>
                '🔬 Seeding subject class result permissions...',

            'SubjectUploadForStaffPermissionTableSeeder' =>
                '💾 Seeding subject upload permissions...',

            'SubjectVettedPermissionTableSeeder' =>
                '✅ Seeding subject vetted permissions...',

            'SubjectVettingsPermissionTableSeeder' =>
                '🔍 Seeding subject vettings...',

            'StudentAssessmentPermissionTableSeeder' =>
                '📝 Seeding student assessment permissions...',

            'IdCardPermissionSeeder' =>
                '🪪 Seeding student ID Card permissions...',

            'AdminScoreEntryPermissionSeeder' =>
                '📝 Seeding Admin score entry...',
        ];

        $this->runSeederList(
            $academicSeeders,
            $seededCount,
            $failedCount,
            $skippedCount
        );

        // ============================================================
        // PART 3: EXAM & ASSESSMENT PERMISSIONS
        // ============================================================

        $this->printSection(
            '📝 PART 3: EXAM & ASSESSMENT PERMISSIONS'
        );

        $examSeeders = [
            'ExamPermissionTableSeeder' =>
                '📋 Seeding exam permissions...',

            'QuestionPermissionTableSeeder' =>
                '❓ Seeding question permissions...',

            'CBTExamPermissionTableSeeder' =>
                '💻 Seeding CBT exam permissions...',
        ];

        $this->runSeederList(
            $examSeeders,
            $seededCount,
            $failedCount,
            $skippedCount
        );

        // ============================================================
        // PART 4: PARENT PORTAL PERMISSIONS
        // ============================================================

        $this->printSection(
            '👨‍👩‍👧‍👦 PART 4: PARENT PORTAL PERMISSIONS'
        );

        $result = $this->safeCall(
            ParentPermissionTableSeeder::class,
            'ParentPermissionTableSeeder',
            '👪 Seeding parent portal permissions...'
        );

        $this->updateStats(
            $result,
            $seededCount,
            $failedCount,
            $skippedCount
        );

        // ============================================================
        // PART 5: TIMETABLE & SCHEDULING PERMISSIONS
        // ============================================================

        $this->printSection(
            '🕐 PART 5: TIMETABLE & SCHEDULING PERMISSIONS'
        );

        $timetableSeeders = [
            'TimetablePermissionTableSeeder' =>
                '📅 Seeding timetable permissions...',

            'RoomPermissionTableSeeder' =>
                '🚪 Seeding room permissions...',

            'HolidayPermissionTableSeeder' =>
                '🎉 Seeding holiday permissions...',

            'ExamTimetablePermissionTableSeeder' =>
                '📋 Seeding exam timetable permissions...',

            'TimetableReportsPermissionTableSeeder' =>
                '📊 Seeding timetable reports permissions...',
        ];

        $this->runSeederList(
            $timetableSeeders,
            $seededCount,
            $failedCount,
            $skippedCount
        );

        // ============================================================
        // PART 6: PROMOTION & SCHOOL BILL PERMISSIONS
        // ============================================================

        $this->printSection(
            '🎯 PART 6: PROMOTION & SCHOOL BILL PERMISSIONS'
        );

        $result = $this->safeCall(
            PromotionPermissionTableSeeder::class,
            'PromotionPermissionTableSeeder',
            '🚀 Seeding promotion permissions...'
        );

        $this->updateStats(
            $result,
            $seededCount,
            $failedCount,
            $skippedCount
        );

        $result = $this->safeCall(
            SchoolBillTermSessionPermissionTableSeeder::class,
            'SchoolBillTermSessionPermissionTableSeeder',
            '💰 Seeding school bill term session permissions...'
        );

        $this->updateStats(
            $result,
            $seededCount,
            $failedCount,
            $skippedCount
        );

        // ============================================================
        // PART 7: FINANCE PERMISSIONS
        // ============================================================

        $this->printSection(
            '💰 PART 7: FINANCE PERMISSIONS'
        );

        $financePermissionSeeders = [
            'ScholarshipPermissionSeeder' =>
                '🎓 Seeding scholarship permissions...',

            'FinancePermissionSeeder' =>
                '💵 Seeding finance permissions...',

            'SiblingGroupPermissionSeeder' =>
                '👨‍👩‍👧 Seeding sibling group permissions...',

            'StudentPaymentPermissionTableSeeder' =>
                '💳 Seeding student payment permissions...',

            'FinancialReportPermissionSeeder' =>
                '📊 Seeding financial report permissions...',

            'PayrollPermissionSeeder' =>
                '💰 Seeding payroll permissions...',

            'StaffPaymentPermissionSeeder' =>
                '👨‍🏫 Seeding staff payment permissions...',

            'SchoolPaymentPermissionTableSeeder' =>
                '🏫 Seeding school payment permissions...',

            'AllFinancePermissionsSeeder' =>
                '💰 Seeding all finance permissions...',

            'StaffAttendancePermissionTableSeeder' =>
                '📋 Seeding staff attendance permissions...',
        ];

        $this->runSeederList(
            $financePermissionSeeders,
            $seededCount,
            $failedCount,
            $skippedCount
        );

        // ============================================================
        // PART 8: ANALYSIS & TRANSCRIPT PERMISSIONS
        // ============================================================

        $this->printSection(
            '📊 PART 8: ANALYSIS & TRANSCRIPT PERMISSIONS'
        );

        $analysisSeeders = [
            'AnalysisPermissionTableSeeder' =>
                '📊 Seeding analysis permissions...',

            'TranscriptPermissionTableSeeder' =>
                '📄 Seeding transcript permissions...',

            'MyPrincipalsCommentPermissionTableSeeder' =>
                '👔 Seeding my principals comment permissions...',

            'AdminStudentResultManagerPermissionSeeder' =>
                '📝 Seeding admin student result manager...',
        ];

        $this->runSeederList(
            $analysisSeeders,
            $seededCount,
            $failedCount,
            $skippedCount
        );

        // ============================================================
        // PART 9: UPDATED PERMISSION SEEDERS
        // ============================================================

        $this->printSection(
            '🔄 PART 9: UPDATED PERMISSION SEEDERS'
        );

        $updatedSeeders = [
            'UpdatedAttendancePermissionTableSeeder' =>
                '📋 Seeding updated attendance permissions...',

            'UpdatedFinancialReportPermissionTableSeeder' =>
                '📊 Seeding updated financial report permissions...',

            'UpdatedScholarshipPermissionTableSeeder' =>
                '🎓 Seeding updated scholarship permissions...',

            'UpdatedAdminScoreEntryPermissionTableSeeder' =>
                '📝 Seeding updated admin score entry permissions...',

            'UpdatedPromotionPermissionTableSeeder' =>
                '🚀 Seeding updated promotion permissions...',

            'UpdatedTranscriptPermissionTableSeeder' =>
                '📄 Seeding updated transcript permissions...',

            'UpdatedFinancePermissionTableSeeder' =>
                '💰 Seeding updated finance permissions...',

            'UpdatedTimetablePermissionTableSeeder' =>
                '📅 Seeding updated timetable permissions...',

            'UpdatedTimetableReportsPermissionTableSeeder' =>
                '📊 Seeding updated timetable reports permissions...',
        ];

        $this->runSeederList(
            $updatedSeeders,
            $seededCount,
            $failedCount,
            $skippedCount
        );

        // ============================================================
        // PART 10: ATTENDANCE PERMISSIONS
        // ============================================================

        $this->printSection(
            '📋 PART 10: ATTENDANCE PERMISSIONS'
        );

        $result = $this->safeCall(
            AttendancePermissionTableSeeder::class,
            'AttendancePermissionTableSeeder',
            '📋 Seeding attendance permissions...'
        );

        $this->updateStats(
            $result,
            $seededCount,
            $failedCount,
            $skippedCount
        );

        // ============================================================
        // PART 11: FINANCE LOOKUP & REFERENCE DATA
        // ============================================================

        $this->printSection(
            '📚 PART 11: FINANCE LOOKUP & REFERENCE DATA'
        );

        $financeLookupSeeders = [
            'ScholarshipTypeSeeder' =>
                '🎓 Seeding scholarship types...',

            'DiscountTypeSeeder' =>
                '🏷️ Seeding discount types...',

            'ChartOfAccountsSeeder' =>
                '📊 Seeding chart of accounts...',

            'ExpenseCategorySeeder' =>
                '💸 Seeding expense categories...',

            'PaymentMethodSeeder' =>
                '💳 Seeding payment methods...',

            'BillCategorySeeder' =>
                '📋 Seeding bill categories...',
        ];

        $this->runSeederList(
            $financeLookupSeeders,
            $seededCount,
            $failedCount,
            $skippedCount
        );

        // ============================================================
        // PART 12: PAYMENT GATEWAYS
        // ============================================================

        $this->printSection(
            '🌐 PART 12: PAYMENT GATEWAYS'
        );

        $result = $this->safeCall(
            DefaultPaymentGatewaysSeeder::class,
            'DefaultPaymentGatewaysSeeder',
            '🌐 Seeding default payment gateways...'
        );

        $this->updateStats(
            $result,
            $seededCount,
            $failedCount,
            $skippedCount
        );

        // ============================================================
        // PART 12B: NEW MODULE PERMISSIONS (always re-run)
        // These seeders only use updateOrCreate, so they run every time:
        // newly added permissions reach existing installs without
        // clearing seeder_log.
        // ============================================================

        $this->printSection(
            '🆕 PART 12B: NEW MODULE PERMISSIONS'
        );

        $moduleSeeders = [
            'ResultAccessPermissionSeeder' =>
                '🔒 Seeding result access permissions...',

            'OnlineFeePaymentPermissionSeeder' =>
                '💳 Seeding online fee payment permissions...',

            'PaymentGatewayPermissionSeeder' =>
                '🌐 Seeding payment gateway permissions...',

            'NoticePermissionSeeder' =>
                '📢 Seeding school notice permissions...',

            'ResultSendPermissionSeeder' =>
                '📨 Seeding result sending permissions...',

            'ParentContactPermissionSeeder' =>
                '📇 Seeding parent contact permissions...',

            'ParentPortalPermissionSeeder' =>
                '👪 Seeding parent portal role & permissions...',

            'InstalmentPlanPermissionSeeder' =>
                '🗓️ Seeding instalment plan permissions...',

            'ReportApprovalPermissionSeeder' =>
                '🛡️ Seeding report card approval permissions...',

            'ManagementDashboardPermissionSeeder' =>
                '📊 Seeding management dashboard permissions...',

            'ClubPermissionSeeder' =>
                '🎭 Seeding club permissions...',

            'SportPermissionSeeder' =>
                '⚽ Seeding sport permissions...',

            'HousePermissionSeeder' =>
                '🏠 Seeding house points permissions...',

            'PayrollPermissionSeeder' =>
                '💰 Seeding payroll permissions (pay profiles, rates, remittances)...',

            'ActivityPermissionSeeder' =>
                '🕵️ Seeding activity log & online staff permissions...',

            'LeavePermissionSeeder' =>
                '🌴 Seeding staff leave permissions...',

            'StudentLeavePermissionSeeder' =>
                '🧒 Seeding student leave permissions...',

            'FinanceOpsPermissionSeeder' =>
                '🏦 Seeding payouts, loans, expenses, assets & accounting permissions...',

            'MaintenancePermissionSeeder' =>
                '🛠️ Seeding maintenance-mode permission...',

            'FeatureFlagPermissionSeeder' =>
                '🔀 Seeding feature-flag (module access) permission...',

            'FinancialAuditPermissionSeeder' =>
                '🔎 Seeding financial-audit permissions...',

            'BackupPermissionSeeder' =>
                '💾 Seeding database-backup permission...',

            'CertificatePermissionSeeder' =>
                '🎓 Seeding certificate module permissions...',

            'CertificateDefaultTemplateSeeder' =>
                '🏅 Adding the default college certificate template...',

            'CertificateTestimonialTemplateSeeder' =>
                '📜 Adding the default leaving-testimonial template...',

            'LessonNotePermissionSeeder' =>
                '📝 Seeding lesson-note permissions...',

            'TeachingMethodSeeder' =>
                '💡 Adding default teaching methods...',

            'CurriculumPermissionSeeder' =>
                '📚 Seeding curriculum (topics) permissions...',

            'ExamPermissionSeeder' =>
                '📝 Seeding exam vetting permissions...',

            'BroadsheetRankingPermissionSeeder' =>
                '🏆 Seeding broadsheet-ranking permission...',

            'LmsPermissionSeeder' =>
                '🎓 Seeding e-learning (LMS) permissions...',

            'CalendarPermissionSeeder' =>
                '📅 Seeding school-calendar permissions & default categories...',

            'AccountingSetupSeeder' =>
                '📒 Adding ledger accounts and linking expense categories...',
        ];

        $this->runSeederList(
            $moduleSeeders,
            $seededCount,
            $failedCount,
            $skippedCount,
            true
        );

        // Super Admin always holds every permission, including ones added above.
        try {
            $superAdmin = \Spatie\Permission\Models\Role::where('name', 'Super Admin')->where('guard_name', 'web')->first();
            if ($superAdmin) {
                $superAdmin->syncPermissions(Permission::where('guard_name', 'web')->pluck('id')->all());
                $this->command->info('  ✅ Super Admin now has all ' . Permission::where('guard_name', 'web')->count() . ' permissions');
            }
            app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
        } catch (\Throwable $e) {
            $this->command->warn('  ⚠️  Could not sync Super Admin permissions: ' . $e->getMessage());
        }
        $this->command->info('');

        // ============================================================
        // PART 13: DEMO / TEST DATA
        // ============================================================

        if (app()->environment('local', 'development')) {

            $this->printSection(
                '🧪 PART 13: DEMO & TEST DATA (Development Environment)'
            );

            $this->command->warn(
                '⚠️  Running in DEVELOPMENT mode - seeding demo data...'
            );

            $this->command->info('');

            $demoSeeders = [
                // Uncomment when created
                // 'DemoScholarshipSeeder' => '🎓 Seeding demo scholarships...',
                // 'DemoDiscountSeeder' => '🏷️ Seeding demo discounts...',
                // 'DemoStudentPaymentsSeeder' => '💰 Seeding demo payments...',
                // 'DemoUsersSeeder' => '👥 Seeding demo users...',
                // 'DemoStudentsSeeder' => '👨‍🎓 Seeding demo students...',
            ];

            $this->runSeederList(
                $demoSeeders,
                $seededCount,
                $failedCount,
                $skippedCount
            );

        } else {

            $this->printSection(
                '🚀 PART 13: PRODUCTION ENVIRONMENT'
            );

            $this->command->info(
                'Skipping demo data - only seeding essential data'
            );

            $skippedCount += count(
                $this->getDemoSeeders()
            );
        }

        // ============================================================
        // DATABASE STATISTICS
        // ============================================================

        $this->command->info('');

        $this->printSection(
            '📊 DATABASE STATISTICS'
        );

        $this->showDatabaseStats();

        // ============================================================
        // COMPLETION SUMMARY
        // ============================================================

        $endTime = microtime(true);

        $executionTime = round(
            $endTime - $startTime,
            2
        );

        $this->command->info('');

        $this->command->info(
            '╔═══════════════════════════════════════════════════════════════════════════════╗'
        );

        $this->command->info(
            '║                         ✅ SEEDING COMPLETED                                 ║'
        );

        $this->command->info(
            '╠═══════════════════════════════════════════════════════════════════════════════╣'
        );

        $this->command->info(
            '║  📊 Total Seeders Executed: ' .
            str_pad(
                $seededCount,
                45,
                ' ',
                STR_PAD_RIGHT
            ) .
            '║'
        );

        $this->command->info(
            '║  ⏭️  Seeders Skipped: ' .
            str_pad(
                $skippedCount,
                49,
                ' ',
                STR_PAD_RIGHT
            ) .
            '║'
        );

        $this->command->info(
            '║  ❌ Failed Seeders: ' .
            str_pad(
                $failedCount,
                49,
                ' ',
                STR_PAD_RIGHT
            ) .
            '║'
        );

        $this->command->info(
            '║  ⏱️  Execution Time: ' .
            str_pad(
                $executionTime . ' seconds',
                45,
                ' ',
                STR_PAD_RIGHT
            ) .
            '║'
        );

        $this->command->info(
            '║  🕐 Completed at: ' .
            str_pad(
                now()->format('Y-m-d H:i:s'),
                45,
                ' ',
                STR_PAD_RIGHT
            ) .
            '║'
        );

        $this->command->info(
            '╚═══════════════════════════════════════════════════════════════════════════════╝'
        );

        $this->command->info('');

        if ($failedCount > 0) {

            $this->command->warn(
                '⚠️  Some seeders failed. Please check the errors above.'
            );

            $this->command->warn(
                '💡 Tip: Run "php artisan db:seed --force" to force seed in production.'
            );

        } else {

            $this->command->info(
                '🎉 Database seeding completed successfully!'
            );

            $this->command->info(
                '💡 You can now run "php artisan serve" to start the application.'
            );

            $this->command->info(
                '💡 Default admin credentials: admin@example.com / password'
            );
        }
    }

    // ================================================================
    // HELPER: PRINT SECTION
    // ================================================================

    protected function printSection(string $title): void
    {
        $this->command->info(
            '┌─────────────────────────────────────────────────────────────────────────────┐'
        );

        $this->command->info(
            '│ ' . str_pad(
                $title,
                75,
                ' ',
                STR_PAD_RIGHT
            ) . '│'
        );

        $this->command->info(
            '└─────────────────────────────────────────────────────────────────────────────┘'
        );

        $this->command->info('');
    }

    // ================================================================
    // HELPER: RUN A LIST OF SEEDERS
    // ================================================================

    protected function runSeederList(
        array $seeders,
        int &$seededCount,
        int &$failedCount,
        int &$skippedCount,
        bool $force = false
    ): void {

        foreach ($seeders as $seeder => $message) {

            $result = $this->safeCall(
                $seeder,
                $seeder,
                $message,
                $force
            );

            $this->updateStats(
                $result,
                $seededCount,
                $failedCount,
                $skippedCount
            );
        }

        $this->command->info('');
    }

    // ================================================================
    // LOAD PREVIOUSLY RUN SEEDERS
    // ================================================================

    protected function loadRunSeeders(): void
    {
        $this->runSeeders = [];

        /*
         * Check if seeder_log table exists.
         */
        if (!Schema::hasTable('seeder_log')) {

            $this->createSeederLogTable();

            return;
        }

        try {

            $this->runSeeders = DB::table('seeder_log')
                ->whereNotNull('completed_at')
                ->where('success', true)
                ->pluck('seeder_name')
                ->toArray();

        } catch (\Throwable $e) {

            Log::warning(
                'Could not load seeder log: ' .
                $e->getMessage()
            );
        }
    }

    // ================================================================
    // CREATE SEEDER LOG TABLE
    // ================================================================

    protected function createSeederLogTable(): void
    {
        try {

            Schema::create(
                'seeder_log',
                function ($table) {

                    $table->id();

                    $table->string(
                        'seeder_name'
                    )->unique();

                    $table->timestamp(
                        'started_at'
                    )->nullable();

                    $table->timestamp(
                        'completed_at'
                    )->nullable();

                    $table->boolean(
                        'success'
                    )->default(false);

                    $table->text(
                        'error_message'
                    )->nullable();

                    $table->timestamps();
                }
            );

        } catch (\Throwable $e) {

            Log::warning(
                'Could not create seeder_log table: ' .
                $e->getMessage()
            );
        }
    }

    // ================================================================
    // NORMALIZE SEEDER CLASS
    // ================================================================

    protected function resolveSeederClass($seeder): string
    {
        /*
         * If a class is already fully qualified,
         * leave it alone.
         *
         * Example:
         * Database\Seeders\PermissionTableSeeder
         */
        if (
            is_string($seeder) &&
            str_contains($seeder, '\\')
        ) {
            return $seeder;
        }

        /*
         * Convert:
         *
         * ViewClassPermissionTableSeeder
         *
         * to:
         *
         * Database\Seeders\ViewClassPermissionTableSeeder
         */
        return __NAMESPACE__ . '\\' . $seeder;
    }

    // ================================================================
    // SEEDERS THAT RUN EVERY TIME
    // ================================================================

    /**
     * Seeders that check before inserting (create-if-missing), so running
     * them again adds anything new without duplicating what exists:
     * every permission seeder, plus the term and admin-user seeders.
     */
    protected array $repeatable = [
        'TermTableSeeder',
        'UserTableSeeder',
    ];

    protected function isRepeatable(string $seederClass): bool
    {
        $short = class_basename($seederClass);

        return str_contains($short, 'Permission')
            || in_array($short, $this->repeatable, true);
    }

    // ================================================================
    // CHECK IF SEEDER ALREADY RAN
    // ================================================================

    protected function hasBeenRun(string $seederName): bool
    {
        $seederClass = $this->resolveSeederClass(
            $seederName
        );

        /*
         * Canonical fully-qualified name.
         */
        if (
            in_array(
                $seederClass,
                $this->runSeeders,
                true
            )
        ) {
            return true;
        }

        /*
         * Backward compatibility:
         *
         * Old logs may contain:
         *
         * ViewClassPermissionTableSeeder
         */
        $shortName = class_basename(
            $seederClass
        );

        return in_array(
            $shortName,
            $this->runSeeders,
            true
        );
    }

    // ================================================================
    // LOG SEEDER EXECUTION
    // ================================================================

    protected function logSeederRun(
        string $seederName,
        bool $success,
        ?string $error = null
    ): void {

        try {

            /*
             * Always store the fully qualified class name.
             */
            $seederClass = $this->resolveSeederClass(
                $seederName
            );

            DB::table('seeder_log')->updateOrInsert(
                [
                    'seeder_name' => $seederClass,
                ],
                [
                    'seeder_name' => $seederClass,

                    'started_at' => now(),

                    'completed_at' => $success
                        ? now()
                        : null,

                    'success' => $success,

                    'error_message' => $error,

                    'updated_at' => now(),
                ]
            );

            /*
             * Update in-memory list immediately.
             */
            if (
                $success &&
                !in_array(
                    $seederClass,
                    $this->runSeeders,
                    true
                )
            ) {

                $this->runSeeders[] =
                    $seederClass;
            }

        } catch (\Throwable $e) {

            /*
             * Logging should NEVER break database seeding.
             */
            Log::warning(
                'Could not log seeder run',
                [
                    'seeder' => $seederName,
                    'error' => $e->getMessage(),
                ]
            );
        }
    }

    // ================================================================
    // UPDATE STATISTICS
    // ================================================================

    protected function updateStats(
        array $result,
        int &$seededCount,
        int &$failedCount,
        int &$skippedCount
    ): void {

        if (
            isset($result['skipped']) &&
            $result['skipped'] === true
        ) {

            $skippedCount++;

        } elseif (
            isset($result['success']) &&
            $result['success'] === true
        ) {

            $seededCount++;

        } else {

            $failedCount++;
        }
    }

    // ================================================================
    // CHECK PERMISSION
    // ================================================================

    protected function permissionExists(
        string $permissionName,
        string $guardName = 'web'
    ): bool {

        return Permission::where(
            'name',
            $permissionName
        )
            ->where(
                'guard_name',
                $guardName
            )
            ->exists();
    }

    // ================================================================
    // CREATE PERMISSION IF NOT EXISTS
    // ================================================================

    protected function createPermissionIfNotExists(
        string $permissionName,
        string $guardName = 'web',
        ?string $title = null
    ): bool {

        if (
            $this->permissionExists(
                $permissionName,
                $guardName
            )
        ) {
            return false;
        }

        $data = [
            'name' => $permissionName,
            'guard_name' => $guardName,
        ];

        if ($title !== null) {
            $data['title'] = $title;
        }

        Permission::create($data);

        return true;
    }

    // ================================================================
    // SAFE SEEDER CALL
    // ================================================================

    protected function safeCall(
        $seeder,
        $name,
        $message = null,
        bool $force = false
    ): array {

        if ($message) {

            $this->command
                ->getOutput()
                ->write($message);
        }

        /*
         * IMPORTANT FIX:
         *
         * Resolve:
         *
         * ViewClassPermissionTableSeeder
         *
         * into:
         *
         * Database\Seeders\ViewClassPermissionTableSeeder
         */
        $seederClass = $this->resolveSeederClass(
            $seeder
        );

        /*
         * Check whether the class exists.
         */
        if (!class_exists($seederClass)) {

            if ($message) {
                $this->command
                    ->getOutput()
                    ->write("\r\033[K");
            }

            $this->command->warn(
                "  ⚠️  Seeder not found: {$name} - skipping"
            );

            return [
                'success' => false,
                'skipped' => true,
            ];
        }

        /*
         * Check whether this seeder has already run.
         */
        if (
            !$force &&
            !$this->isRepeatable($seederClass) &&
            $this->hasBeenRun(
                $seederClass
            )
        ) {

            if ($message) {
                $this->command
                    ->getOutput()
                    ->write("\r\033[K");
            }

            $this->command->info(
                "  ⏭️  {$name} already run - skipping"
            );

            return [
                'success' => true,
                'skipped' => true,
            ];
        }

        /*
         * Execute the seeder.
         */
        try {

            $this->call(
                $seederClass
            );

            /*
             * Log successful execution.
             */
            $this->logSeederRun(
                $seederClass,
                true
            );

            if ($message) {

                $this->command
                    ->getOutput()
                    ->write("\r\033[K");

                $this->command->info(
                    "  ✅ {$name} completed successfully!"
                );
            }

            return [
                'success' => true,
                'skipped' => false,
            ];

        } catch (\Throwable $e) {

            /*
             * Handle duplicate permission situations.
             */
            $errorMessage = $e->getMessage();

            $isDuplicateError =
                str_contains(
                    $errorMessage,
                    'PermissionAlreadyExists'
                )
                ||
                str_contains(
                    strtolower($errorMessage),
                    'already exists'
                )
                ||
                str_contains(
                    strtolower($errorMessage),
                    'duplicate entry'
                );

            if ($isDuplicateError) {

                if ($message) {

                    $this->command
                        ->getOutput()
                        ->write("\r\033[K");

                    $this->command->warn(
                        "  ⚠️  {$name} - some records already exist, continuing"
                    );
                }

                /*
                 * Treat duplicate data as successfully handled.
                 */
                $this->logSeederRun(
                    $seederClass,
                    true,
                    'Some permissions or records already existed'
                );

                return [
                    'success' => true,
                    'skipped' => false,
                ];
            }

            /*
             * Real failure.
             */
            if ($message) {

                $this->command
                    ->getOutput()
                    ->write("\r\033[K");

                $this->command->error(
                    "  ❌ {$name} failed: " .
                    $errorMessage
                );
            }

            Log::error(
                "Seeder failed: {$seederClass}",
                [
                    'seeder' => $seederClass,
                    'error' => $errorMessage,
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ]
            );

            $this->logSeederRun(
                $seederClass,
                false,
                $errorMessage
            );

            return [
                'success' => false,
                'skipped' => false,
            ];
        }
    }

    // ================================================================
    // DATABASE STATISTICS
    // ================================================================

    protected function showDatabaseStats(): void
    {
        $tables = [

            'users' =>
                '👤 Users',

            'studentRegistration' =>
                '👨‍🎓 Students',

            'staff_records' =>
                '👨‍🏫 Staff',

            'school_bill' =>
                '💰 School Bills',

            'scholarships' =>
                '🎓 Scholarships',

            'scholarship_assignments' =>
                '📋 Scholarship Assignments',

            'discounts' =>
                '🏷️ Discounts',

            'discount_assignments' =>
                '📋 Discount Assignments',

            'payment_batches' =>
                '💵 Payment Batches',

            'student_bill_payment' =>
                '💳 Student Payments',

            'chart_of_accounts' =>
                '📊 Chart of Accounts',

            'expense_categories' =>
                '💸 Expense Categories',

            'payment_gateways' =>
                '🌐 Payment Gateways',

            'schoolterm' =>
                '📅 Terms',

            'schoolsession' =>
                '📅 Sessions',

            'schoolclass' =>
                '🏫 Classes',

            'permissions' =>
                '🔐 Permissions',

            'roles' =>
                '👥 Roles',
        ];

        $stats = [];

        foreach ($tables as $table => $label) {

            if (!Schema::hasTable($table)) {
                continue;
            }

            try {

                $count = DB::table(
                    $table
                )->count();

                if ($count > 0) {

                    $stats[] =
                        "  {$label}: " .
                        number_format($count);
                }

            } catch (\Throwable $e) {

                /*
                 * Ignore inaccessible/missing tables.
                 */
            }
        }

        if (count($stats) > 0) {

            $this->command->info(
                implode("\n", $stats)
            );

        } else {

            $this->command->info(
                '  ℹ️  No data found in tables yet.'
            );
        }
    }

    // ================================================================
    // DEMO SEEDERS
    // ================================================================

    protected function getDemoSeeders(): array
    {
        return [
            'DemoScholarshipSeeder',
            'DemoDiscountSeeder',
            'DemoStudentPaymentsSeeder',
            'DemoUsersSeeder',
            'DemoStudentsSeeder',
        ];
    }
}