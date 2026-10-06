<?php

namespace App\Imports;

use App\Models\Student;
use App\Models\Studentclass;
use App\Models\Studenthouse;
use App\Models\Studentpicture;
use App\Models\PromotionStatus;
use App\Models\ParentRegistration;
use App\Models\Studentpersonalityprofile;
use App\Models\StudentCurrentTerm;
use App\Models\Club;
use App\Models\Sport;
use App\Models\StudentClub;
use App\Models\StudentSport;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\SkipsErrors;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\WithStartRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\WithUpserts;
use Maatwebsite\Excel\Concerns\WithUpsertColumns;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Validators\Failure;

class StudentsImport implements
    ToModel,
    WithStartRow,
    WithValidation,
    SkipsOnFailure,
    SkipsOnError,
    WithUpserts,
    WithUpsertColumns,
    WithMultipleSheets
{
    use Importable, SkipsFailures, SkipsErrors;

    protected int $sclassid;
    protected int $termid;
    protected int $sessionid;
    protected int $batchid;
    protected ?int $userId;

    protected int $rowCounter = 0;
    protected ?string $progressKey = null;
    protected int $totalRows = 0;

    /**
     * Set by prepareForValidation() when the current row contains only
     * the locked Class/Term/Session IDs (cols 15, 16, 17) and nothing
     * else. rules() consults this to no-op its `required` checks on
     * template blank rows.
     */
    protected bool $currentRowSkipped = false;

    public function __construct(
        int $schoolclassid,
        int $termid,
        int $sessionid,
        int $batchid,
        ?int $userId = null
    ) {
        $this->sclassid  = $schoolclassid;
        $this->termid    = $termid;
        $this->sessionid = $sessionid;
        $this->batchid   = $batchid;
        $this->userId    = $userId;

        Log::info('StudentsImport started', [
            'batch_id'         => $this->batchid,
            'expected_class'   => $this->sclassid,
            'expected_term'    => $this->termid,
            'expected_session' => $this->sessionid,
            'user_id'          => $this->userId,
        ]);
    }

    /**
     * ONLY import the "Student Data" sheet.
     */
    public function sheets(): array
    {
        return [
            'Student Data' => $this,
        ];
    }

    public function setProgressTracking(string $progressKey, int $totalRows): void
    {
        $this->progressKey = $progressKey;
        $this->totalRows   = $totalRows;
    }

    /**
     * Runs BEFORE rules() for every row.
     *
     * The batch template pre-fills columns P/Q/R (indexes 15/16/17) with
     * the locked Class/Term/Session IDs on every blank row. That makes
     * those rows non-empty from the validator's point of view, so the
     * `required` rules on admission number / surname / first name fire
     * on all of them.
     *
     * SkipsEmptyRows' isEmptyWhen() is NOT consulted by the
     * ToModel + WithValidation pipeline (it only applies to the
     * toCollection()/toArray() path), so we handle the skip here:
     * if the only populated cells are 15/16/17, flag the row and let
     * rules() no-op.
     */
    public function prepareForValidation($data, $index)
    {
        // Diagnostic — remove once confirmed working.
        Log::info('prepareForValidation called', [
            'index'  => $index,
            'row'    => array_slice($data, 0, 5),
        ]);

        $this->currentRowSkipped = false;

        $editable = $data;
        unset($editable[15], $editable[16], $editable[17]);

        foreach ($editable as $value) {
            if (!is_null($value) && trim((string) $value) !== '') {
                return $data;   // row has real content — validate normally
            }
        }

        $this->currentRowSkipped = true;
        return $data;
    }

    public function model(array $row)
    {
        // If prepareForValidation() flagged this as a blank template row,
        // skip it outright — no import, no logging noise.
        if ($this->currentRowSkipped) {
            $this->currentRowSkipped = false;
            return null;
        }

        $this->rowCounter++;
        $this->reportProgress();

        $rowNumber = $this->startRow() + $this->rowCounter - 1;

        $clean = fn ($v) => (is_null($v) || trim((string) $v) === '') ? null : trim((string) $v);

        // Cast admission number to string (Excel often returns it as int/float)
        $admissionNo  = $clean($row[0] ?? null);
        if ($admissionNo !== null) {
            $admissionNo = (string) $admissionNo;
        }

        $lastname     = $clean($row[1] ?? null);
        $firstname    = $clean($row[2] ?? null);
        $othername    = $clean($row[3] ?? null);
        $gender       = $clean($row[4] ?? null);
        $homeAddress  = $clean($row[5] ?? null);
        $dob          = $clean($row[6] ?? null);
        $age          = $clean($row[7] ?? null);
        $placeOfBirth = $clean($row[8] ?? null);
        $nationality  = $clean($row[9] ?? null);
        $state        = $clean($row[10] ?? null);
        $local        = $clean($row[11] ?? null);
        $religion     = $clean($row[12] ?? null);
        $lastSchool   = $clean($row[13] ?? null);
        $lastClass    = $clean($row[14] ?? null);

        // Normalise DOB to Y-m-d if it parsed as a date; otherwise store as-is.
        if ($dob) {
            try {
                $dob = \Carbon\Carbon::parse($dob)->format('Y-m-d');
            } catch (\Exception $e) {
                // leave as-is — column is varchar
            }
        }

        $fatherTitle      = $clean($row[18] ?? null);
        $fatherName       = $clean($row[19] ?? null);
        $fatherPhone      = $clean($row[20] ?? null);
        $officeAddress    = $clean($row[21] ?? null);
        $fatherOccupation = $clean($row[22] ?? null);
        $motherTitle      = $clean($row[23] ?? null);
        $motherName       = $clean($row[24] ?? null);
        $motherPhone      = $clean($row[25] ?? null);
        $motherOccupation = $clean($row[26] ?? null);
        $motherOfficeAddr = $clean($row[27] ?? null);
        $parentAddress    = $clean($row[28] ?? null);
        $parentReligion   = $clean($row[29] ?? null);

        $bloodGroup            = $clean($row[30] ?? null);
        $genotype              = $clean($row[31] ?? null);
        $emergencyContactName  = $clean($row[32] ?? null);
        $emergencyContactPhone = $clean($row[33] ?? null);
        $allergiesMedical      = $clean($row[34] ?? null);
        $guardianName          = $clean($row[35] ?? null);
        $guardianRelationship  = $clean($row[36] ?? null);
        $guardianPhone         = $clean($row[37] ?? null);
        $whatsappNumber        = $clean($row[38] ?? null);
        $clubName              = $clean($row[39] ?? null);
        $sportName             = $clean($row[40] ?? null);

        // Belt-and-braces skip — if a row somehow reaches here with no
        // name data, drop it.
        if (!$admissionNo && !$lastname && !$firstname) {
            return null;
        }

        if (!$admissionNo || !$lastname || !$firstname) {
            $msg = "Row {$rowNumber}: Admission No, Surname and First Name are required.";
            Log::warning($msg);
            throw new \Exception($msg);
        }

        return DB::transaction(function () use (
            $admissionNo, $lastname, $firstname, $othername, $gender, $homeAddress,
            $dob, $age, $placeOfBirth, $nationality, $state, $local, $religion,
            $lastSchool, $lastClass,
            $fatherTitle, $fatherName, $fatherPhone, $officeAddress, $fatherOccupation,
            $motherTitle, $motherName, $motherPhone, $motherOccupation, $motherOfficeAddr,
            $parentAddress, $parentReligion,
            $bloodGroup, $genotype, $emergencyContactName, $emergencyContactPhone,
            $allergiesMedical, $guardianName, $guardianRelationship, $guardianPhone,
            $whatsappNumber, $clubName, $sportName, $rowNumber
        ) {
            $student = Student::updateOrCreate(
                ['admissionNo' => $admissionNo],
                [
                    // NOT NULL, no default — must always have a value.
                    // 'title' and 'future_ambition' are not collected by
                    // the batch template, so they are hardcoded rather
                    // than referencing undefined variables.
                    'title'            => 'N/A',
                    'firstname'        => $firstname,
                    'lastname'         => $lastname,
                    'othername'        => $othername        ?? 'N/A',
                    'gender'           => $gender           ?? 'N/A',
                    'future_ambition'  => 'N/A',
                    'home_address2'    => $homeAddress      ?? 'N/A',
                    'dateofbirth'      => $dob              ?? 'N/A', // varchar — safe
                    'age'              => $age              ?? 'N/A', // varchar — safe
                    'placeofbirth'     => $placeOfBirth     ?? 'N/A',
                    'religion'         => $religion         ?? 'N/A',
                    'nationality'      => $nationality      ?? 'N/A',
                    'state'            => $state            ?? 'N/A',
                    'local'            => $local            ?? 'N/A',
                    'last_school'      => $lastSchool       ?? 'N/A',
                    'last_class'       => $lastClass        ?? 'N/A',
                    'registeredBy'     => $this->userId     ?? '0',   // NOT NULL

                    // Nullable in DB — null is fine
                    'blood_group'                  => $bloodGroup,
                    'genotype'                     => $genotype,
                    'emergency_contact_name'       => $emergencyContactName,
                    'emergency_contact_phone'      => $emergencyContactPhone,
                    'allergies_medical_conditions' => $allergiesMedical,

                    // Defaults
                    'batchid'          => $this->batchid,
                    'statusId'         => 1,
                    'student_status'   => 'Active',
                    'student_category' => 'Day',
                ]
            );

            ParentRegistration::updateOrCreate(
                ['studentId' => $student->id],
                [
                    'father_title'          => $fatherTitle,
                    'father'                => $fatherName,
                    'father_phone'          => $fatherPhone,
                    'office_address'        => $officeAddress,
                    'father_occupation'     => $fatherOccupation,
                    'mother_title'          => $motherTitle,
                    'mother'                => $motherName,
                    'mother_phone'          => $motherPhone,
                    'mother_occupation'     => $motherOccupation,
                    'mother_office_address' => $motherOfficeAddr,
                    'parent_address'        => $parentAddress,
                    'religion'              => $parentReligion,
                    'guardian_name'         => $guardianName,
                    'guardian_relationship' => $guardianRelationship,
                    'guardian_phone'        => $guardianPhone,
                    'whatsapp_number'       => $whatsappNumber,
                ]
            );

            Studentpicture::firstOrCreate(
                ['studentid' => $student->id],
                ['picture' => 'unnamed.jpg']
            );

            Studentclass::updateOrCreate(
                [
                    'studentId' => $student->id,
                    'termid'    => $this->termid,
                    'sessionid' => $this->sessionid,
                ],
                ['schoolclassid' => $this->sclassid]
            );

            PromotionStatus::updateOrCreate(
                [
                    'studentId'     => $student->id,
                    'schoolclassid' => $this->sclassid,
                    'termid'        => $this->termid,
                    'sessionid'     => $this->sessionid,
                ],
                [
                    'promotionStatus' => 'PROMOTED',
                    'classstatus'     => 'CURRENT',
                ]
            );

            Studenthouse::updateOrCreate(
                [
                    'studentid' => $student->id,
                    'termid'    => $this->termid,
                    'sessionid' => $this->sessionid,
                ],
                ['schoolhouse' => null]
            );

            Studentpersonalityprofile::firstOrCreate([
                'studentid'     => $student->id,
                'schoolclassid' => $this->sclassid,
                'termid'        => $this->termid,
                'sessionid'     => $this->sessionid,
            ]);

            StudentCurrentTerm::registerTerm(
                $student->id,
                $this->sclassid,
                $this->termid,
                $this->sessionid,
                true
            );

            if ($clubName) {
                $club = Club::whereRaw('LOWER(club) = ?', [strtolower($clubName)])->first();
                if ($club) {
                    StudentClub::updateOrCreate(
                        ['studentid' => $student->id],
                        ['clubid' => $club->id, 'termid' => $this->termid, 'sessionid' => $this->sessionid]
                    );
                }
            }

            if ($sportName) {
                $sport = Sport::whereRaw('LOWER(sport) = ?', [strtolower($sportName)])->first();
                if ($sport) {
                    StudentSport::updateOrCreate(
                        ['studentid' => $student->id],
                        ['sportid' => $sport->id, 'termid' => $this->termid, 'sessionid' => $this->sessionid]
                    );
                }
            }

            Log::info("Row {$rowNumber} imported successfully", [
                'admissionNo' => $admissionNo,
                'student_id'  => $student->id,
            ]);

            return $student;
        });
    }

    public function onFailure(Failure ...$failures)
    {
        foreach ($failures as $failure) {
            Log::warning('Import validation failure', [
                'row'       => $failure->row(),
                'attribute' => $failure->attribute(),
                'errors'    => $failure->errors(),
                'values'    => $failure->values(),
            ]);
        }

        $this->failures = array_merge($this->failures ?? [], $failures);
    }

    protected function reportProgress(): void
    {
        if (!$this->progressKey || $this->totalRows <= 0) {
            return;
        }

        if ($this->rowCounter % 5 !== 0 && $this->rowCounter < $this->totalRows) {
            return;
        }

        Cache::put($this->progressKey, [
            'status'   => 'processing',
            'progress' => min($this->rowCounter, $this->totalRows),
            'total'    => $this->totalRows,
            'message'  => "Processed {$this->rowCounter} of {$this->totalRows} rows",
        ], now()->addMinutes(45));
    }

    public function rules(): array
    {
        return [
            '0' => [
                function ($attribute, $value, $fail) {
                    if ($this->currentRowSkipped) return;
                    if (is_null($value) || trim((string) $value) === '') {
                        $fail('Admission number is required.');
                    }
                },
                'max:50',
            ],
            '1' => [
                function ($attribute, $value, $fail) {
                    if ($this->currentRowSkipped) return;
                    if (is_null($value) || trim((string) $value) === '') {
                        $fail('Surname is required.');
                    }
                },
                'max:100',
            ],
            '2' => [
                function ($attribute, $value, $fail) {
                    if ($this->currentRowSkipped) return;
                    if (is_null($value) || trim((string) $value) === '') {
                        $fail('First name is required.');
                    }
                },
                'max:100',
            ],
            '4'  => 'nullable|in:Male,Female',

            '15' => function ($attribute, $value, $fail) {
                if ($this->currentRowSkipped) return;
                if ((int) $value !== $this->sclassid) {
                    $fail("Class ID does not match. Expected {$this->sclassid}, got {$value}");
                }
            },
            '16' => function ($attribute, $value, $fail) {
                if ($this->currentRowSkipped) return;
                if ((int) $value !== $this->termid) {
                    $fail("Term ID does not match. Expected {$this->termid}, got {$value}");
                }
            },
            '17' => function ($attribute, $value, $fail) {
                if ($this->currentRowSkipped) return;
                if ((int) $value !== $this->sessionid) {
                    $fail("Session ID does not match. Expected {$this->sessionid}, got {$value}");
                }
            },

            '30' => 'nullable|in:A+,A-,B+,B-,AB+,AB-,O+,O-',
            '31' => 'nullable|in:AA,AS,SS,AC,SC,CC',
        ];
    }

    public function customValidationMessages()
    {
        return [
            '4.in'  => 'Gender must be Male or Female.',
            '30.in' => 'Blood Group must be one of A+, A-, B+, B-, AB+, AB-, O+, O-.',
            '31.in' => 'Genotype must be one of AA, AS, SS, AC, SC, CC.',
        ];
    }

    public function startRow(): int
    {
        return 2;
    }

    public function uniqueBy()
    {
        return 'admissionNo';
    }

    public function upsertColumns()
    {
        return [
            'title', 'firstname', 'lastname', 'othername', 'gender',
            // NOTE: 'home_address' is NOT a column in studentRegistration —
            // only 'home_address2' exists. Removed to avoid silent upsert
            // failures on a non-existent key.
            'home_address2', 'dateofbirth', 'age', 'placeofbirth',
            'religion', 'nationality', 'state', 'local', 'last_school', 'last_class',
            'blood_group', 'genotype', 'emergency_contact_name', 'emergency_contact_phone',
            'allergies_medical_conditions',
            'registeredBy', 'batchid', 'statusId', 'student_status',
        ];
    }
}