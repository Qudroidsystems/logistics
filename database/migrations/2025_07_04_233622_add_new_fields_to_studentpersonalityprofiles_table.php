<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddNewFieldsToStudentpersonalityprofilesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('studentpersonalityprofiles', function (Blueprint $table) {
            $table->string('attentiveness_in_class')->nullable()->after('attendance');
            $table->string('class_participation')->nullable()->after('attentiveness_in_class');
            $table->string('relationship_with_others')->nullable()->after('class_participation');
            $table->string('doing_assignment')->nullable()->after('relationship_with_others');
            $table->string('writing_skill')->nullable()->after('doing_assignment');
            $table->string('reading_skill')->nullable()->after('writing_skill');
            $table->string('spoken_english_communication')->nullable()->after('reading_skill');
            $table->string('hand_writing')->nullable()->after('spoken_english_communication');
            $table->string('club')->nullable()->after('hand_writing');
            $table->string('music')->nullable()->after('club');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('studentpersonalityprofiles', function (Blueprint $table) {
            $table->dropColumn([
                'attentiveness_in_class',
                'class_participation',
                'relationship_with_others',
                'doing_assignment',
                'writing_skill',
                'reading_skill',
                'spoken_english_communication',
                'hand_writing',
                'club',
                'music',
            ]);
        });
    }
}