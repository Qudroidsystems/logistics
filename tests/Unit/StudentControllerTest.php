<?php

namespace Tests\Feature\Http\Controllers;

use App\Http\Controllers\StudentController;
use App\Models\BroadsheetRecord;
use App\Models\BroadsheetRecordMock;
use App\Models\Broadsheets;
use App\Models\BroadsheetsMock;
use App\Models\Classcategory;
use App\Models\Schoolarm;
use App\Models\Schoolclass;
use App\Models\Schoolsession;
use App\Models\Schoolterm;
use App\Models\Student;
use App\Models\StudentBatchModel;
use App\Models\Subject;
use App\Models\Subjectclass;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class StudentControllerTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    protected function setUp()  : void
    {
        parent::setUp();

        // Disable middleware to bypass permission checks
        $this->withoutMiddleware();

        // Create a user for authentication
        $this->user = User::factory()->create();
        $this->actingAs($this->user);

        // Mock Log facade to prevent actual logging
        Log::shouldReceive('debug')->andReturn(null);
        Log::shouldReceive('info')->andReturn(null);
        Log::shouldReceive('error')->andReturn(null);
        Log::shouldReceive('warning')->andReturn(null);
    }

    public function test_it_updates_class_successfully_for_valid_request()
    {
        // Arrange
        $term = Schoolterm::factory()->create();
        $session = Schoolsession::factory()->create();
        $batch = StudentBatchModel::factory()->create(array(
            'termid' => $term->id,
            'session' => $session->id,
        ));
        $schoolarm = Schoolarm::factory()->create();
        $classcategory = Classcategory::factory()->create();
        $schoolclass = Schoolclass::factory()->create(array(
            'arm' => $schoolarm->id,
            'classcategoryid' => $classcategory->id,
        ));

        // Create students associated with the batch
        $students = Student::factory()->count(2)->create(array('batchid' => $batch->id));

        // Create broadsheet records for each student
        $broadsheetRecords = array();
        foreach ($students as $student) {
            $broadsheetRecords[] = BroadsheetRecord::factory()->create(array(
                'student_id' => $student->id,
                'schoolclass_id' => $schoolclass->id,
                'session_id' => $session->id,
            ));
        }

        // Create broadsheet mock records
        $broadsheetMockRecords = array();
        foreach ($students as $student) {
            $broadsheetMockRecords[] = BroadsheetRecordMock::factory()->create(array(
                'student_id' => $student->id,
                'schoolclass_id' => $schoolclass->id,
                'session_id' => $session->id,
            ));
        }

        // Create subjectclass records
        $subjectclasses = Subjectclass::factory()->count(2)->create(array(
            'schoolclassid' => $schoolclass->id,
            // 'termid' => $term->id,
            // 'sessionid' => $session->id,
        ));

        // Create Broadsheets and BroadsheetsMock records
        foreach ($broadsheetRecords as $index => $record) {
            Broadsheets::factory()->create(array(
                'broadsheet_record_id' => $record->id,
                'subjectclass_id' => $subjectclasses[$index % 2]->id,
                'term_id' => $term->id,
            ));
        }

        foreach ($broadsheetMockRecords as $index => $record) {
            BroadsheetsMock::factory()->create(array(
                'broadsheet_records_mock_id' => $record->id,
                'subjectclass_id' => $subjectclasses[$index % 2]->id,
                'term_id' => $term->id,
            ));
        }

        $newSchoolclass = Schoolclass::factory()->create(array(
            'arm' => $schoolarm->id,
            'classcategoryid' => $classcategory->id,
            'description'=>'test'
        ));

        $requestData = array(
            'batch_id' => $batch->id,
            'schoolclass' => 'New Class',
            'arm' => $schoolarm->id,
            'schoolclassid' => $newSchoolclass->id,
            'armid' => $schoolarm->id,
            'classcategoryid' => $classcategory->id,
        );

        // Act
        $response = $this->putJson(route('student.updateclass'), $requestData);

        // Assert
        $response->assertStatus(200)
                 ->assertJson(array(
                     'success' => true,
                     'message' => 'Class updated successfully',
                 ));

        // Verify schoolclass was updated
        $this->assertDatabaseHas('schoolclass', array(
            'id' => $newSchoolclass->id,
            'schoolclass' => 'New Class',
            'arm' => $schoolarm->id,
            'classcategoryid' => $classcategory->id,
        ));

        // Verify batch was updated
        $this->assertDatabaseHas('student_batch_upload', array(
            'id' => $batch->id,
            'schoolclassid' => $newSchoolclass->id,
        ));

        // Verify studentclass was not updated
        $this->assertDatabaseMissing('studentclass', array(
            'schoolclassid' => $newSchoolclass->id,
        ));

        // Verify BroadsheetRecord updates
        foreach ($students as $student) {
            $this->assertDatabaseHas('broadsheet_records', array(
                'student_id' => $student->id,
                'schoolclass_id' => $newSchoolclass->id,
                'session_id' => $session->id,
            ));
        }

        // Verify BroadsheetRecordMock updates
        foreach ($students as $student) {
            $this->assertDatabaseHas('broadsheet_records_mock', array(
                'student_id' => $student->id,
                'schoolclass_id' => $newSchoolclass->id,
                'session_id' => $session->id,
            ));
        }

        // Verify Subjectclass updates
        foreach ($subjectclasses as $subjectclass) {
            $this->assertDatabaseHas('subjectclass', array(
                'id' => $subjectclass->id,
                'schoolclassid' => $newSchoolclass->id,
                // 'termid' => $term->id,
                // 'session' => $session->id,
            ));
        }
    }

    // public function test_it_fails_validation_for_invalid_batch_id()
    // {
    //     $requestData = array(
    //         'batch_id' => 999, // Non-existent batch_id
    //         'schoolclass' => 'New Class',
    //         'arm' => Schoolarm::factory()->create()->id,
    //         'schoolclassid' => Schoolclass::factory()->create()->id,
    //         'armid' => Schoolarm::factory()->create()->id,
    //         'classcategoryid' => Classcategory::factory()->create()->id,
    //     );

    //     $response = $this->putJson(route('student.updateclass'), $requestData);

    //     $response->assertStatus(422)
    //              ->assertJsonValidationErrors(array('batch_id'));
    // }

    // public function test_it_handles_missing_batch_id()
    // {
    //     $requestData = array(
    //         // Missing batch_id
    //         'schoolclass' => 'New Class',
    //         'arm' => Schoolarm::factory()->create()->id,
    //         'schoolclassid' => Schoolclass::factory()->create()->id,
    //         'armid' => Schoolarm::factory()->create()->id,
    //         'classcategoryid' => Classcategory::factory()->create()->id,
    //     );

    //     $response = $this->putJson(route('student.updateclass'), $requestData);

    //     $response->assertStatus(422)
    //              ->assertJsonValidationErrors(array('batch_id'));
    // }

    // public function test_it_handles_batch_not_found()
    // {
    //     $requestData = array(
    //         'batch_id' => 999, // Non-existent batch
    //         'schoolclass' => 'New Class',
    //         'arm' => Schoolarm::factory()->create()->id,
    //         'schoolclassid' => Schoolclass::factory()->create()->id,
    //         'armid' => Schoolarm::factory()->create()->id,
    //         'classcategoryid' => Classcategory::factory()->create()->id,
    //     );

    //     $response = $this->putJson(route('student.updateclass'), $requestData);

    //     $response->assertStatus(404)
    //              ->assertJson(array(
    //                  'success' => false,
    //                  'message' => 'Batch not found',
    //              ));
    // }

    // public function test_it_handles_nullable_subjectclass_id_in_broadsheets()
    // {
    //     $term = Schoolterm::factory()->create();
    //     $session = Schoolsession::factory()->create();
    //     $batch = StudentBatchModel::factory()->create(array(
    //         'termid' => $term->id,
    //         'session' => $session->id,
    //     ));
    //     $schoolarm = Schoolarm::factory()->create();
    //     $classcategory = Classcategory::factory()->create();
    //     $schoolclass = Schoolclass::factory()->create(array(
    //         'arm' => $schoolarm->id,
    //         'classcategoryid' => $classcategory->id,
    //     ));

    //     $student = Student::factory()->create(array('batchid' => $batch->id));
    //     $broadsheetRecord = BroadsheetRecord::factory()->create(array(
    //         'student_id' => $student->id,
    //         'schoolclass_id' => $schoolclass->id,
    //         'session_id' => $session->id,
    //     ));
    //     $broadsheetMockRecord = BroadsheetRecordMock::factory()->create(array(
    //         'student_id' => $student->id,
    //         'schoolclass_id' => $schoolclass->id,
    //         'session_id' => $session->id,
    //     ));

    //     // Create Broadsheets and BroadsheetsMock with null subjectclass_id
    //     Broadsheets::factory()->create(array(
    //         'broadsheet_record_id' => $broadsheetRecord->id,
    //         'subjectclass_id' => null,
    //         'term_id' => $term->id,
    //     ));
    //     BroadsheetsMock::factory()->create(array(
    //         'broadsheet_records_mock_id' => $broadsheetMockRecord->id,
    //         'subjectclass_id' => null,
    //         'term_id' => $term->id,
    //     ));

    //     $newSchoolclass = Schoolclass::factory()->create(array(
    //         'arm' => $schoolarm->id,
    //         'classcategoryid' => $classcategory->id,
    //     ));

    //     $requestData = array(
    //         'batch_id' => $batch->id,
    //         'schoolclass' => 'New Class',
    //         'arm' => $schoolarm->id,
    //         'schoolclassid' => $newSchoolclass->id,
    //         'armid' => $schoolarm->id,
    //         'classcategoryid' => $classcategory->id,
    //     );

    //     $response = $this->putJson(route('student.updateclass'), $requestData);

    //     $response->assertStatus(200)
    //              ->assertJson(array(
    //                  'success' => true,
    //                  'message' => 'Class updated successfully',
    //              ));

    //     // Verify no subjectclass updates occurred
    //     $this->assertDatabaseMissing('subjectclass', array(
    //         'schoolclassid' => $newSchoolclass->id,
    //     ));

    //     // Verify broadsheet records updated
    //     $this->assertDatabaseHas('broadsheet_records', array(
    //         'student_id' => $student->id,
    //         'schoolclass_id' => $newSchoolclass->id,
    //         'session_id' => $session->id,
    //     ));
    //     $this->assertDatabaseHas('broadsheet_records_mock', array(
    //         'student_id' => $student->id,
    //         'schoolclass_id' => $newSchoolclass->id,
    //         'session_id' => $session->id,
    //     ));
    // }
}