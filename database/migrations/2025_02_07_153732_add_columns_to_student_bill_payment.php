<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('student_bill_payment', function (Blueprint $table) {
            $table->string('invoiceNo')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('student_bill_payment', function (Blueprint $table) {
             $table->dropColumn(['invoiceNo']);
        });
    }
};
