<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // The old "cum" column was actually storing the DIVIDED (averaged)
        // cumulative value despite its name. Rename it to what it really is.
        Schema::table('broadsheets', function (Blueprint $table) {
            $table->renameColumn('cum', 'cum_ave');
        });

        // New "cum" column: the raw, un-divided running SUM
        // (BF + this term's total). cum_ave = cum / termId.
        Schema::table('broadsheets', function (Blueprint $table) {
            $table->decimal('cum', 8, 2)->default(0)->after('bf');
        });
    }

    public function down(): void
    {
        Schema::table('broadsheets', function (Blueprint $table) {
            $table->dropColumn('cum');
        });

        Schema::table('broadsheets', function (Blueprint $table) {
            $table->renameColumn('cum_ave', 'cum');
        });
    }
};
