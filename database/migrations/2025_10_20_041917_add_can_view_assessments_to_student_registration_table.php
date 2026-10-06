<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up()
    {
        Schema::table('studentRegistration', function (Blueprint $table) {
            $table->boolean('can_view_assessments')->default(true)->after('student_status');
        });
    }

    public function down()
    {
        Schema::table('studentRegistration', function (Blueprint $table) {
            $table->dropColumn('can_view_assessments');
        });
    }
};