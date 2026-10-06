<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('staffbioinfo', function (Blueprint $table) {
            if (!Schema::hasColumn('staffbioinfo', 'department')) {
                $table->string('department')->nullable()->after('employmentid');
            }
            if (!Schema::hasColumn('staffbioinfo', 'position')) {
                $table->string('position')->nullable()->after('department');
            }
            if (!Schema::hasColumn('staffbioinfo', 'job_title')) {
                $table->string('job_title')->nullable()->after('position');
            }
            if (!Schema::hasColumn('staffbioinfo', 'grade_level')) {
                $table->string('grade_level')->nullable()->after('job_title');
            }
            if (!Schema::hasColumn('staffbioinfo', 'step')) {
                $table->integer('step')->default(1)->after('grade_level');
            }
            if (!Schema::hasColumn('staffbioinfo', 'date_of_employment')) {
                $table->date('date_of_employment')->nullable()->after('step');
            }
            if (!Schema::hasColumn('staffbioinfo', 'date_of_confirmation')) {
                $table->date('date_of_confirmation')->nullable()->after('date_of_employment');
            }
            if (!Schema::hasColumn('staffbioinfo', 'qualification')) {
                $table->string('qualification')->nullable()->after('date_of_confirmation');
            }
            if (!Schema::hasColumn('staffbioinfo', 'bank_name')) {
                $table->string('bank_name')->nullable()->after('qualification');
            }
            if (!Schema::hasColumn('staffbioinfo', 'account_number')) {
                $table->string('account_number')->nullable()->after('bank_name');
            }
            if (!Schema::hasColumn('staffbioinfo', 'account_name')) {
                $table->string('account_name')->nullable()->after('account_number');
            }
            if (!Schema::hasColumn('staffbioinfo', 'pension_id')) {
                $table->string('pension_id')->nullable()->after('account_name');
            }
            if (!Schema::hasColumn('staffbioinfo', 'nhf_number')) {
                $table->string('nhf_number')->nullable()->after('pension_id');
            }
            if (!Schema::hasColumn('staffbioinfo', 'tin_number')) {
                $table->string('tin_number')->nullable()->after('nhf_number');
            }
            if (!Schema::hasColumn('staffbioinfo', 'status')) {
                $table->enum('status', ['active', 'inactive', 'suspended', 'retired'])->default('active')->after('tin_number');
            }
            if (!Schema::hasColumn('staffbioinfo', 'deleted_at')) {
                $table->softDeletes()->after('updated_at');
            }
        });
    }

    public function down()
    {
        Schema::table('staffbioinfo', function (Blueprint $table) {
            $table->dropColumn([
                'department', 'position', 'job_title', 'grade_level', 'step',
                'date_of_employment', 'date_of_confirmation', 'qualification',
                'bank_name', 'account_number', 'account_name', 'pension_id',
                'nhf_number', 'tin_number', 'status', 'deleted_at'
            ]);
        });
    }
};
