<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up()
    {
        Schema::table('options', function (Blueprint $table) {
            $table->string('label', 1)->nullable()->after('is_correct'); // 'a'-'e' or null
        });
    }

    public function down()
    {
        Schema::table('options', function (Blueprint $table) {
            $table->dropColumn('label');
        });
    }
};