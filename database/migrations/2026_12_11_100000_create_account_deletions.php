<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Account deletion (app store and privacy-law requirement). The user row is kept but emptied of personal details,
 * so orders, invoices and the money ledger still add up; this table records when and why, with no personal data.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('account_deletions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->index();
            // app | web | staff
            $table->string('via', 8);
            $table->string('reason', 300)->nullable();
            $table->timestampTz('deleted_at');
        });
        Schema::table('users', function (Blueprint $table) {
            $table->timestampTz('deleted_at')->nullable()->index();
        });
    }

    public function down(): void
    {
        Schema::table('users', fn (Blueprint $t) => $t->dropColumn('deleted_at'));
        Schema::dropIfExists('account_deletions');
    }
};
