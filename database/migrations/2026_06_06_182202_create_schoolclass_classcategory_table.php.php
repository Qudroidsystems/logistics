<?php
// database/migrations/xxxx_xx_xx_xxxxxx_create_schoolclass_classcategory_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSchoolclassClasscategoryTable extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('schoolclass_classcategory')) {
            Schema::create('schoolclass_classcategory', function (Blueprint $table) {
                $table->id();
                $table->foreignId('schoolclass_id')->constrained('schoolclass')->onDelete('cascade');
                $table->foreignId('classcategory_id')->constrained('classcategories')->onDelete('cascade');
                $table->decimal('promotion_pass_average', 5, 2)->nullable(); // This stores the class-specific pass average
                $table->timestamps();
                $table->unique(['schoolclass_id', 'classcategory_id']);
            });
        } else {
            // Add promotion_pass_average column if it doesn't exist
            if (!Schema::hasColumn('schoolclass_classcategory', 'promotion_pass_average')) {
                Schema::table('schoolclass_classcategory', function (Blueprint $table) {
                    $table->decimal('promotion_pass_average', 5, 2)->nullable();
                });
            }
        }
    }

    public function down()
    {
        Schema::dropIfExists('schoolclass_classcategory');
    }
}
