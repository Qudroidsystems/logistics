<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('school_information', function (Blueprint $table) {
            $table->string('school_website')->nullable()->after('school_motto');
        });

       
    }

    public function down()
    {
        Schema::table('school_information', function (Blueprint $table) {
            $table->dropColumn('school_website');
        });
    }
};
