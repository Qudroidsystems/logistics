<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('club_members', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('clubid');
            $table->unsignedBigInteger('studentid');
            $table->string('role')->default('member');
            $table->timestamp('joined_at')->useCurrent();
            $table->timestamps();

            $table->foreign('clubid')->references('id')->on('clubs')->onDelete('cascade');
            $table->foreign('studentid')->references('id')->on('users')->onDelete('cascade');
            $table->unique(['clubid', 'studentid']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('club_members');
    }
};