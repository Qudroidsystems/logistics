<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Looking after the fleet: servicing (by date and by mileage), fuel fills, how many parcels a vehicle carries,
 * and a record of every expiry reminder sent so each one goes out once.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            if (! Schema::hasColumn('vehicles', 'odometer_km')) {
                $table->unsignedInteger('odometer_km')->nullable();
                $table->date('last_serviced_on')->nullable();
                // Service every N days and/or every N km; the next due date and mileage follow from the last service.
                $table->unsignedSmallInteger('service_interval_days')->nullable();
                $table->unsignedInteger('service_interval_km')->nullable();
                $table->date('next_service_on')->nullable();
                $table->unsignedInteger('next_service_km')->nullable();
                // Most parcels it can carry at once (weight is max_weight_g).
                $table->unsignedSmallInteger('capacity_parcels')->nullable();
            }
        });

        Schema::create('vehicle_service_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_id')->constrained('vehicles')->cascadeOnDelete();
            $table->foreignId('operator_id')->constrained('operators');
            $table->date('serviced_on');
            $table->unsignedInteger('odometer_km')->nullable();
            $table->unsignedBigInteger('cost_kobo')->default(0);
            $table->string('description', 300)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->timestamps();

            $table->index(['vehicle_id', 'serviced_on']);
        });

        Schema::create('vehicle_fuel_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_id')->constrained('vehicles')->cascadeOnDelete();
            $table->foreignId('operator_id')->constrained('operators');
            $table->foreignId('driver_profile_id')->nullable()->constrained('driver_profiles')->nullOnDelete();
            $table->date('filled_on');
            $table->decimal('litres', 8, 2);
            $table->unsignedBigInteger('cost_kobo');
            $table->unsignedInteger('odometer_km')->nullable();
            $table->string('note', 200)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->timestamps();

            $table->index(['operator_id', 'filled_on']);
            $table->index(['vehicle_id', 'filled_on']);
        });

        Schema::create('fleet_reminders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('operator_id')->constrained('operators');
            // vehicle | document
            $table->string('subject_type', 16);
            $table->unsignedBigInteger('subject_id');
            // insurance | roadworthiness | service | the document type (drivers_licence, insurance, ...)
            $table->string('kind', 32);
            $table->date('due_on');
            // 30 | 7 | 0 (due today) | overdue
            $table->string('stage', 8);
            $table->timestampTz('sent_at');

            $table->unique(['subject_type', 'subject_id', 'kind', 'due_on', 'stage']);
            $table->index(['operator_id', 'sent_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fleet_reminders');
        Schema::dropIfExists('vehicle_fuel_logs');
        Schema::dropIfExists('vehicle_service_logs');
        Schema::table('vehicles', function (Blueprint $table) {
            if (Schema::hasColumn('vehicles', 'odometer_km')) {
                $table->dropColumn(['odometer_km', 'last_serviced_on', 'service_interval_days', 'service_interval_km', 'next_service_on', 'next_service_km', 'capacity_parcels']);
            }
        });
    }
};
