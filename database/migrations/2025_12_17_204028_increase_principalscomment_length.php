<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up()
    {
        Schema::table('studentpersonalityprofiles', function (Blueprint $table) {
            // Option 1: Change to TEXT (unlimited length)
            $table->text('principalscomment')->nullable()->change();
            
            // OR Option 2: Increase VARCHAR length
            // $table->string('principalscomment', 2000)->nullable()->change();
        });
    }

    public function down()
    {
        Schema::table('studentpersonalityprofiles', function (Blueprint $table) {
            $table->string('principalscomment', 255)->nullable()->change();
        });
    }
};
