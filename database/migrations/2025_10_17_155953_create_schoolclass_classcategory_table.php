<?php
// Migration file: database/migrations/xxxx_xx_xx_create_schoolclass_classcategory_table.php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('schoolclass_classcategory', function (Blueprint $table) {
            $table->id();
            $table->foreignId('schoolclass_id')->constrained('schoolclass')->onDelete('cascade');
            $table->foreignId('classcategory_id')->constrained('classcategories')->onDelete('cascade');
            $table->timestamps();

            $table->unique(['schoolclass_id', 'classcategory_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('schoolclass_classcategory');
    }
};