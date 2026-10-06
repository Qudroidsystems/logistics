<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RenameTittleAndNationlityColumnsInStudentRegistrationTable extends Migration
{
    public function up()
    {
        Schema::table('studentRegistration', function (Blueprint $table) {
            $table->renameColumn('tittle', 'title');
            $table->renameColumn('nationlity', 'nationality');
        });
    }

    public function down()
    {
        Schema::table('studentRegistration', function (Blueprint $table) {
            $table->renameColumn('title', 'tittle');
            $table->renameColumn('nationality', 'nationlity');
        });
    }
}

