<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;;

     return new class extends Migration
     {
         public function up(): void
         {
             Schema::table('studentRegistration', function (Blueprint $table) {
                 $table->string('student_category', 50)->nullable()->after('statusId');
             });
         }

         public function down(): void
         {
             Schema::table('studentRegistration', function (Blueprint $table) {
                 $table->dropColumn('student_category');
             });
         }
     };