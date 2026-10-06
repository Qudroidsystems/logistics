<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('questions', function (Blueprint $table) {
            // Add marks field (default 1 point per question)
            $table->float('marks')->default(1)->after('image');

            // Add order field for drag-drop ordering
            $table->integer('order')->default(0)->after('marks');

            // Add reusable flag for question reuse across exams
            $table->boolean('is_reusable')->default(false)->after('order');
        });
    }

    public function down()
    {
        Schema::table('questions', function (Blueprint $table) {
            $table->dropColumn(['marks', 'order', 'is_reusable']);
        });
    }
};
