<?php
// Database migration for school information table
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up()
    {
        Schema::create('school_information', function (Blueprint $table) {
            $table->id();
            $table->string('school_name');
            $table->string('school_address')->nullable();
            $table->string('school_phone')->nullable();
            $table->string('school_email')->nullable();
            $table->string('school_logo')->nullable(); // path to logo file
            $table->string('school_motto')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Insert default school information
        DB::table('school_information')->insert([
            'school_name' => 'VITE-eSCHOOL',
            'school_address' => 'Your School Address Here',
            'school_phone' => 'Your Phone Number',
            'school_email' => 'info@topclasscollege.edu',
            'school_motto' => 'Excellence in Education',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down()
    {
        Schema::dropIfExists('school_information');
    }
};
?>