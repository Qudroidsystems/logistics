<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('classcategories', function (Blueprint $table) {
            // Minimum overall average a student must achieve to be promoted.
            // NULL means no average threshold is applied — compulsory subjects only.
            $table->decimal('promotion_pass_average', 5, 2)->nullable()->after('is_senior');
        });
    }

    public function down(): void
    {
        Schema::table('classcategories', function (Blueprint $table) {
            $table->dropColumn('promotion_pass_average');
        });
    }
};
