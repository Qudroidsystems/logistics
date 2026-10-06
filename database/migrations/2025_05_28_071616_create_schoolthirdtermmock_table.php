<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('schoolthirdtermmock', function (Blueprint $table) {
            $table->id();
            $table->string('schoolbroadsheetId')->nullable();
            $table->string('studentId')->nullable();
            $table->string('subjectclassid')->nullable();
            $table->string('staffid')->nullable();
            $table->double('exam',5, 2)->default('0');
            $table->double('total',5, 2)->default('0');
            $table->string('grade')->nullable();
            $table->double('allsubjectstotalscores',5, 2)->default('0');
            $table->string('subjectpositionclass')->nullable();
            $table->double('cmin', 5, 2)->default('0');
            $table->double('cmax', 5, 2)->default('0');
            $table->double('avg', 5, 2)->default('0');
            $table->string('remark')->nullable();
            $table->string('termid')->nullable();
            $table->string('session')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('schoolthirdtermmock');
    }
};
