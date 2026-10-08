<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('agreements', function (Blueprint $table) {
            // What happens to the money and the parcel when the receiver cannot be reached. Frozen from platform settings when the agreement is created.
            $table->jsonb('failed_delivery_policy')->nullable()->after('cancellation_policy');
        });
    }

    public function down(): void
    {
        Schema::table('agreements', fn (Blueprint $t) => $t->dropColumn('failed_delivery_policy'));
    }
};
