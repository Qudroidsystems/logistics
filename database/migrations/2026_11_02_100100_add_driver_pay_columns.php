<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('driver_profiles', function (Blueprint $table) {
            // Share of the company's net for a job that goes to this driver, in basis points (2500 = 25%). Null/0 = paid outside the platform.
            $table->unsignedSmallInteger('pay_share_bp')->nullable()->after('accepts_documents');
        });
        Schema::table('assignments', function (Blueprint $table) {
            $table->timestampTz('pay_posted_at')->nullable()->after('payout_amount');
        });
    }

    public function down(): void
    {
        Schema::table('assignments', fn (Blueprint $t) => $t->dropColumn('pay_posted_at'));
        Schema::table('driver_profiles', fn (Blueprint $t) => $t->dropColumn('pay_share_bp'));
    }
};
