<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Font;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use Carbon\Carbon;

class StudentReportExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithEvents
{
    protected $data;
    protected $groupedColumns = [];

    public function __construct(array $data)
    {
        $this->data = $data;
        $this->groupedColumns = $this->determineColumnGroups();
    }

    public function collection()
    {
        return $this->data['students'];
    }

    public function headings(): array
    {
        $headings = [];
        $columns = $this->data['columns'];

        $map = [
            'photo'          => 'Photo',
            'admissionNo'    => 'Admission Number',
            'lastname'       => 'Last Name',
            'firstname'      => 'First Name',
            'othername'      => 'Other Name',
            'gender'         => 'Gender',
            'dateofbirth'    => 'Date of Birth',
            'age'            => 'Age',
            'class'          => 'Class / Arm',
            'status'         => 'Student Status',
            'admission_date' => 'Admission Date',
            'phone_number'   => 'Phone Number',
            'state'          => 'State of Origin',
            'local'          => 'Local Government Area (LGA)',
            'religion'       => 'Religion',
            'blood_group'    => 'Blood Group',
            'father_name'    => "Father's Name",
            'mother_name'    => "Mother's Name",
            'guardian_phone' => 'Guardian Contact Phone',
            'term'           => 'Term',
            'session'        => 'Session',
            'email'          => 'Email Address',
            'city'           => 'City',
            'nationality'    => 'Nationality',
            'placeofbirth'   => 'Place of Birth',
            'mother_tongue'  => 'Mother Tongue',
            'student_category' => 'Student Category',
            'future_ambition' => 'Future Ambition',
            'last_school'    => 'Last School',
            'last_class'     => 'Last Class',
            'father_occupation' => 'Father\'s Occupation',
            'mother_occupation' => 'Mother\'s Occupation',
            'father_city'    => 'Father\'s City',
            'parent_email'   => 'Parent Email',
            'parent_address' => 'Parent Address',
            'nin_number'     => 'NIN Number',
            'school_house'   => 'School House',
            'genotype'                     => 'Genotype',
            'emergency_contact_name'       => 'Emergency Contact Name',
            'emergency_contact_phone'      => 'Emergency Contact Phone',
            'allergies_medical_conditions' => 'Allergies / Medical Conditions',
            'guardian_name'                => 'Guardian Name',
            'guardian_relationship'        => 'Guardian Relationship to Student',
            'whatsapp_number'              => 'Parent/Guardian WhatsApp Number',
        ];

        foreach ($columns as $col) {
            $headings[] = $map[$col] ?? ucwords(str_replace('_', ' ', $col));
        }

        return $headings;
    }

    public function map($student): array
    {
        $row = [];
        $columns = $this->data['columns'];

        foreach ($columns as $col) {
            switch ($col) {
                case 'photo':
                    // Show student initials or photo status
                    if (isset($student->picture) && $student->picture !== 'unnamed.jpg') {
                        $row[] = '✓ Photo';
                    } else {
                        $row[] = $this->getStudentInitials($student) ?: 'No Photo';
                    }
                    break;

                case 'admissionNo':
                    $admissionNo = $student->admissionNo ?? $student->admission_no ?? 'N/A';
                    $row[] = $admissionNo;
                    break;

                case 'class':
                    $class = $student->schoolclass ?? 'N/A';
                    $arm = $student->arm_name ?? $student->arm ?? '';
                    $row[] = $arm ? "$class - $arm" : $class;
                    break;

                case 'guardian_phone':
                    $phone = $student->guardian_phone ?? $student->father_phone ?? $student->mother_phone ?? '';
                    $row[] = $phone ?: 'N/A';
                    break;

                case 'admission_date':
                    if (isset($student->admission_date) && $student->admission_date) {
                        try {
                            $row[] = Carbon::parse($student->admission_date)->format('d/m/Y');
                        } catch (\Exception $e) {
                            $row[] = $student->admission_date;
                        }
                    } elseif (isset($student->admissionDate) && $student->admissionDate) {
                        try {
                            $row[] = Carbon::parse($student->admissionDate)->format('d/m/Y');
                        } catch (\Exception $e) {
                            $row[] = $student->admissionDate;
                        }
                    } else {
                        $row[] = 'N/A';
                    }
                    break;

                case 'dateofbirth':
                    if (isset($student->dateofbirth) && $student->dateofbirth) {
                        try {
                            $row[] = Carbon::parse($student->dateofbirth)->format('d/m/Y');
                        } catch (\Exception $e) {
                            $row[] = $student->dateofbirth;
                        }
                    } else {
                        $row[] = 'N/A';
                    }
                    break;

                case 'age':
                    if (isset($student->age) && $student->age) {
                        $row[] = $student->age;
                    } elseif (isset($student->dateofbirth) && $student->dateofbirth) {
                        try {
                            $dob = Carbon::parse($student->dateofbirth);
                            $row[] = $dob->age;
                        } catch (\Exception $e) {
                            $row[] = 'N/A';
                        }
                    } else {
                        $row[] = 'N/A';
                    }
                    break;

                case 'status':
                    $status = '';
                    if (isset($student->statusId)) {
                        if ($student->statusId == 1) $status = 'Old Student';
                        if ($student->statusId == 2) $status = 'New Student';
                    }
                    if (isset($student->student_status) && $student->student_status) {
                        $status .= ($status ? ' - ' . $student->student_status : $student->student_status);
                    }
                    $row[] = $status ?: 'N/A';
                    break;

                case 'gender':
                    $gender = $student->gender ?? 'N/A';
                    $row[] = $gender;
                    break;

                case 'term':
                    if (isset($student->current_term_name) && $student->current_term_name) {
                        $row[] = $student->current_term_name;
                    } elseif (isset($student->term_name) && $student->term_name) {
                        $row[] = $student->term_name;
                    } elseif (isset($student->termid) && $student->termid) {
                        try {
                            $term = \App\Models\Schoolterm::find($student->termid);
                            $row[] = $term ? $term->name : 'N/A';
                        } catch (\Exception $e) {
                            $row[] = 'N/A';
                        }
                    } else {
                        $row[] = $this->data['termName'] ?? 'N/A';
                    }
                    break;

                case 'session':
                    if (isset($student->current_session_name) && $student->current_session_name) {
                        $row[] = $student->current_session_name;
                    } elseif (isset($student->session_name) && $student->session_name) {
                        $row[] = $student->session_name;
                    } elseif (isset($student->sessionid) && $student->sessionid) {
                        try {
                            $session = \App\Models\Schoolsession::find($student->sessionid);
                            $row[] = $session ? $session->name : 'N/A';
                        } catch (\Exception $e) {
                            $row[] = 'N/A';
                        }
                    } else {
                        $row[] = $this->data['sessionName'] ?? 'N/A';
                    }
                    break;

                case 'email':
                    $row[] = $student->email ?? 'N/A';
                    break;

                case 'city':
                    $row[] = $student->city ?? 'N/A';
                    break;

                case 'nationality':
                    $row[] = $student->nationality ?? 'N/A';
                    break;

                case 'placeofbirth':
                    $row[] = $student->placeofbirth ?? 'N/A';
                    break;

                case 'mother_tongue':
                    $row[] = $student->mother_tongue ?? 'N/A';
                    break;

                case 'student_category':
                    $row[] = $student->student_category ?? 'N/A';
                    break;

                case 'future_ambition':
                    $row[] = $student->future_ambition ?? 'N/A';
                    break;

                case 'last_school':
                    $row[] = $student->last_school ?? 'N/A';
                    break;

                case 'last_class':
                    $row[] = $student->last_class ?? 'N/A';
                    break;

                case 'father_occupation':
                    $row[] = $student->father_occupation ?? 'N/A';
                    break;

                case 'mother_occupation':
                    $row[] = $student->mother_occupation ?? 'N/A';
                    break;

                case 'father_city':
                    $row[] = $student->father_city ?? 'N/A';
                    break;

                case 'parent_email':
                    $row[] = $student->parent_email ?? 'N/A';
                    break;

                case 'parent_address':
                    $row[] = $student->parent_address ?? 'N/A';
                    break;

                case 'nin_number':
                    $row[] = $student->nin_number ?? 'N/A';
                    break;

                case 'school_house':
                    $row[] = $student->school_house ?? $student->sport_house ?? 'N/A';
                    break;

                case 'blood_group':
                    $row[] = $student->blood_group ?? 'N/A';
                    break;

                case 'genotype':
                    $row[] = $student->genotype ?? 'N/A';
                    break;

                case 'emergency_contact_name':
                    $row[] = $student->emergency_contact_name ?? 'N/A';
                    break;

                case 'emergency_contact_phone':
                    $row[] = $student->emergency_contact_phone ?? 'N/A';
                    break;

                case 'allergies_medical_conditions':
                    $row[] = $student->allergies_medical_conditions ?? 'N/A';
                    break;

                case 'guardian_name':
                    $row[] = $student->guardian_name ?? 'N/A';
                    break;

                case 'guardian_relationship':
                    $row[] = $student->guardian_relationship ?? 'N/A';
                    break;

                case 'whatsapp_number':
                    $row[] = $student->whatsapp_number ?? 'N/A';
                    break;

                case 'religion':
                    $row[] = $student->religion ?? 'N/A';
                    break;

                case 'state':
                    $row[] = $student->state ?? 'N/A';
                    break;

                case 'local':
                    $row[] = $student->local ?? 'N/A';
                    break;

                case 'phone_number':
                    $row[] = $student->phone_number ?? 'N/A';
                    break;

                // Handle separate name fields
                case 'lastname':
                    $row[] = $student->lastname ?? $student->last_name ?? 'N/A';
                    break;

                case 'firstname':
                    $row[] = $student->firstname ?? $student->first_name ?? 'N/A';
                    break;

                case 'othername':
                    $row[] = $student->othername ?? $student->other_name ?? $student->middle_name ?? 'N/A';
                    break;

                case 'father_name':
                    $row[] = $student->father_name ?? 'N/A';
                    break;

                case 'mother_name':
                    $row[] = $student->mother_name ?? 'N/A';
                    break;

                default:
                    // Try different property names
                    $value = $this->getStudentProperty($student, $col);
                    $row[] = $value !== null && $value !== '' ? $value : 'N/A';
                    break;
            }
        }

        return $row;
    }

    /**
     * Get student initials for photo placeholder
     */
    private function getStudentInitials($student)
    {
        $first = $student->firstname ?? $student->first_name ?? '';
        $last = $student->lastname ?? $student->last_name ?? '';

        $firstInitial = !empty($first) ? strtoupper(substr($first, 0, 1)) : '';
        $lastInitial = !empty($last) ? strtoupper(substr($last, 0, 1)) : '';

        return $firstInitial . $lastInitial ?: 'ST';
    }

    /**
     * Get student property with multiple fallbacks
     */
    private function getStudentProperty($student, $property)
    {
        // Try direct property access
        if (property_exists($student, $property)) {
            return $student->$property;
        }

        // Try snake_case version
        $snakeCase = strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $property));
        if (property_exists($student, $snakeCase)) {
            return $student->$snakeCase;
        }

        // Try camelCase version
        $camelCase = lcfirst(str_replace(' ', '', ucwords(str_replace('_', ' ', $property))));
        if (property_exists($student, $camelCase)) {
            return $student->$camelCase;
        }

        // Try as method
        if (method_exists($student, $property)) {
            try {
                return $student->$property();
            } catch (\Exception $e) {
                return null;
            }
        }

        // Common property mappings
        $mappings = [
            'admissionNo' => ['admissionNo', 'admission_no', 'admission_number'],
            'phone_number' => ['phone_number', 'phone', 'mobile'],
            'blood_group' => ['blood_group', 'bloodgroup'],
            'genotype' => ['genotype'],
            'mother_tongue' => ['mother_tongue', 'mothertongue'],
            'father_name' => ['father_name', 'father'],
            'mother_name' => ['mother_name', 'mother'],
            'father_phone' => ['father_phone', 'fatherphone'],
            'mother_phone' => ['mother_phone', 'motherphone'],
            'parent_email' => ['parent_email', 'parentemail'],
            'parent_address' => ['parent_address', 'parentaddress'],
            'father_occupation' => ['father_occupation', 'fatheroccupation'],
            'father_city' => ['father_city', 'fathercity'],
            'last_school' => ['last_school', 'lastschool'],
            'last_class' => ['last_class', 'lastclass'],
            'reason_for_leaving' => ['reason_for_leaving', 'reasonforleaving'],
            'school_house' => ['school_house', 'schoolhouse', 'sport_house', 'house'],
            'student_category' => ['student_category', 'category'],
            'future_ambition' => ['future_ambition', 'ambition'],
            'nin_number' => ['nin_number', 'nin', 'national_id'],
            'admission_date' => ['admission_date', 'admissionDate'],
            'dateofbirth' => ['dateofbirth', 'dob', 'birth_date'],
            'placeofbirth' => ['placeofbirth', 'birth_place'],
            'student_status' => ['student_status', 'status'],
            'statusId' => ['statusId', 'status_id', 'student_status_id'],
            'emergency_contact_name' => ['emergency_contact_name'],
            'emergency_contact_phone' => ['emergency_contact_phone'],
            'allergies_medical_conditions' => ['allergies_medical_conditions'],
            'guardian_name' => ['guardian_name'],
            'guardian_relationship' => ['guardian_relationship'],
            'guardian_phone' => ['guardian_phone'],
            'whatsapp_number' => ['whatsapp_number'],
        ];

        if (isset($mappings[$property])) {
            foreach ($mappings[$property] as $mappedProperty) {
                if (property_exists($student, $mappedProperty)) {
                    return $student->$mappedProperty;
                }
                if (method_exists($student, $mappedProperty)) {
                    try {
                        return $student->$mappedProperty();
                    } catch (\Exception $e) {
                        continue;
                    }
                }
            }
        }

        return null;
    }

    /**
     * Determine column groups for better organization
     */
    private function determineColumnGroups()
    {
        $columns = $this->data['columns'];
        $groups = [];

        $groupDefinitions = [
            'Student Information' => ['photo', 'admissionNo', 'firstname', 'lastname', 'othername', 'gender', 'dateofbirth', 'age'],
            'Academic Information' => ['class', 'status', 'term', 'session', 'admission_date', 'student_category'],
            'Contact Information' => ['phone_number', 'email', 'parent_email', 'parent_address', 'guardian_phone', 'whatsapp_number'],
            'Geographical Information' => ['state', 'local', 'city', 'nationality', 'placeofbirth'],
            'Parent & Guardian Information' => ['father_name', 'mother_name', 'father_phone', 'mother_phone', 'father_occupation', 'mother_occupation', 'father_city', 'guardian_name', 'guardian_relationship'],
            'Personal Information' => ['blood_group', 'genotype', 'religion', 'mother_tongue', 'nin_number', 'school_house'],
            'Medical & Emergency Information' => ['allergies_medical_conditions', 'emergency_contact_name', 'emergency_contact_phone'],
            'Additional Information' => ['future_ambition', 'last_school', 'last_class', 'reason_for_leaving'],
        ];

        foreach ($groupDefinitions as $groupName => $groupColumns) {
            $foundColumns = array_intersect($columns, $groupColumns);
            if (!empty($foundColumns)) {
                $groups[$groupName] = $foundColumns;
            }
        }

        return $groups;
    }

    public function styles(Worksheet $sheet)
    {
        $totalRows = $this->data['students']->count() + 6; // Added extra rows for metadata
        $startRow = 7; // Header row starts here

        $styles = [
            $startRow => [ // Header row
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => 'FFFFFF']
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => [
                        'rgb' => isset($this->data['confidential']) && $this->data['confidential'] ? 'DC3545' : '1E40AF'
                    ]
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                    'wrapText' => true
                ]
            ],

            'A' . ($startRow + 1) . ':A' . $totalRows => [
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'F8FAFC']
                ]
            ],

            'A' . $startRow . ':' . $sheet->getHighestColumn() . $totalRows => [
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => 'E5E7EB']
                    ]
                ]
            ]
        ];

        // Alternate row coloring
        for ($i = $startRow + 1; $i <= $totalRows; $i++) {
            if ($i % 2 == 0) {
                $styles['A' . $i . ':' . $sheet->getHighestColumn() . $i] = [
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'F8FAFC']
                    ]
                ];
            }
        }

        return $styles;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                // Insert title rows at the top
                $sheet->insertNewRowBefore(1, 6);

                // Get highest column letter
                $highestColumn = $sheet->getHighestColumn();
                $highestColumnIndex = Coordinate::columnIndexFromString($highestColumn);

                // School header if included
                if ($this->data['include_header'] ?? true) {
                    $schoolInfo = $this->data['school_info'] ?? null;

                    if ($schoolInfo) {
                        $sheet->setCellValue('A1', $schoolInfo->school_name ?? 'STUDENT MASTER LIST REPORT');
                        $sheet->mergeCells('A1:' . $highestColumn . '1');
                        $sheet->getStyle('A1')->applyFromArray([
                            'font' => [
                                'bold' => true,
                                'size' => 16,
                                'color' => [
                                    'rgb' => isset($this->data['confidential']) && $this->data['confidential'] ? 'DC3545' : '1E40AF'
                                ]
                            ],
                            'alignment' => [
                                'horizontal' => Alignment::HORIZONTAL_CENTER,
                                'vertical' => Alignment::VERTICAL_CENTER
                            ]
                        ]);

                        if (isset($schoolInfo->school_motto) && $schoolInfo->school_motto) {
                            $sheet->setCellValue('A2', $schoolInfo->school_motto);
                            $sheet->mergeCells('A2:' . $highestColumn . '2');
                            $sheet->getStyle('A2')->applyFromArray([
                                'font' => [
                                    'italic' => true,
                                    'size' => 12
                                ],
                                'alignment' => [
                                    'horizontal' => Alignment::HORIZONTAL_CENTER
                                ]
                            ]);
                        }

                        $details = 'Class: ' . ($this->data['className'] ?? 'All Classes') .
                                  ' | Term: ' . ($this->data['termName'] ?? 'All Terms') .
                                  ' | Session: ' . ($this->data['sessionName'] ?? 'All Sessions') .
                                  ' | Generated: ' . ($this->data['generated'] ?? now()->format('d/m/Y H:i:s')) .
                                  ' | Total Students: ' . ($this->data['total'] ?? 0);

                        if (isset($this->data['confidential']) && $this->data['confidential']) {
                            $details .= ' | CONFIDENTIAL';
                        }

                        $sheet->setCellValue('A3', $details);
                        $sheet->mergeCells('A3:' . $highestColumn . '3');
                        $sheet->getStyle('A3')->applyFromArray([
                            'font' => ['size' => 10],
                            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
                        ]);
                    } else {
                        $title = 'STUDENT MASTER LIST REPORT';
                        if (isset($this->data['confidential']) && $this->data['confidential']) {
                            $title = 'CONFIDENTIAL - ' . $title;
                        }

                        $sheet->setCellValue('A1', $title);
                        $sheet->mergeCells('A1:' . $highestColumn . '1');
                        $sheet->getStyle('A1')->applyFromArray([
                            'font' => [
                                'bold' => true,
                                'size' => 16,
                                'color' => [
                                    'rgb' => isset($this->data['confidential']) && $this->data['confidential'] ? 'DC3545' : '1E40AF'
                                ]
                            ],
                            'alignment' => [
                                'horizontal' => Alignment::HORIZONTAL_CENTER,
                                'vertical' => Alignment::VERTICAL_CENTER
                            ]
                        ]);

                        $details = 'Class: ' . ($this->data['className'] ?? 'All Classes') .
                                  ' | Term: ' . ($this->data['termName'] ?? 'All Terms') .
                                  ' | Session: ' . ($this->data['sessionName'] ?? 'All Sessions') .
                                  ' | Generated: ' . ($this->data['generated'] ?? now()->format('d/m/Y H:i:s')) .
                                  ' | Total Students: ' . ($this->data['total'] ?? 0);
                        $sheet->setCellValue('A2', $details);
                        $sheet->mergeCells('A2:' . $highestColumn . '2');
                        $sheet->getStyle('A2')->applyFromArray([
                            'font' => ['size' => 11],
                            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
                        ]);
                    }
                } else {
                    $title = 'STUDENT MASTER LIST REPORT - ' . ($this->data['className'] ?? 'All Classes');
                    if (isset($this->data['confidential']) && $this->data['confidential']) {
                        $title = 'CONFIDENTIAL - ' . $title;
                    }

                    $sheet->setCellValue('A1', $title);
                    $sheet->mergeCells('A1:' . $highestColumn . '1');
                    $sheet->getStyle('A1')->applyFromArray([
                        'font' => [
                            'bold' => true,
                            'size' => 16,
                            'color' => [
                                'rgb' => isset($this->data['confidential']) && $this->data['confidential'] ? 'DC3545' : '1E40AF'
                            ]
                        ],
                        'alignment' => [
                            'horizontal' => Alignment::HORIZONTAL_CENTER,
                            'vertical' => Alignment::VERTICAL_CENTER
                        ]
                    ]);

                    $details = 'Generated: ' . ($this->data['generated'] ?? now()->format('d/m/Y H:i:s')) .
                              ' | Total Students: ' . ($this->data['total'] ?? 0) .
                              ' | Males: ' . ($this->data['males'] ?? 0) .
                              ' | Females: ' . ($this->data['females'] ?? 0) .
                              ' | Term: ' . ($this->data['termName'] ?? 'All Terms') .
                              ' | Session: ' . ($this->data['sessionName'] ?? 'All Sessions');
                    $sheet->setCellValue('A2', $details);
                    $sheet->mergeCells('A2:' . $highestColumn . '2');
                    $sheet->getStyle('A2')->applyFromArray([
                        'font' => ['size' => 11],
                        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
                    ]);
                }

                // Add warning message if large report
                if ($this->data['is_large_report'] ?? false) {
                    $sheet->setCellValue('A3', '⚠️ Large report detected. Photos excluded for performance.');
                    $sheet->mergeCells('A3:' . $highestColumn . '3');
                    $sheet->getStyle('A3')->applyFromArray([
                        'font' => [
                            'italic' => true,
                            'color' => ['rgb' => '721C24']
                        ],
                        'fill' => [
                            'fillType' => Fill::FILL_SOLID,
                            'startColor' => ['rgb' => 'F8D7DA']
                        ],
                        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
                    ]);
                }

                // Add generated by information
                $generatedBy = 'Generated by: ' . ($this->data['generated_by'] ?? 'System') .
                              ' | Template: ' . ucfirst($this->data['template'] ?? 'default');
                $sheet->setCellValue('A4', $generatedBy);
                $sheet->mergeCells('A4:' . $highestColumn . '4');
                $sheet->getStyle('A4')->applyFromArray([
                    'font' => [
                        'italic' => true,
                        'size' => 10
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER
                    ]
                ]);

                // Add timestamp
                $timestamp = 'Generated on: ' . date('d/m/Y H:i:s');
                $sheet->setCellValue('A5', $timestamp);
                $sheet->mergeCells('A5:' . $highestColumn . '5');
                $sheet->getStyle('A5')->applyFromArray([
                    'font' => [
                        'italic' => true,
                        'size' => 10
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER
                    ]
                ]);

                // Empty row before header
                $sheet->setCellValue('A6', '');

                // Add column group headers (custom implementation)
                $this->addColumnGroups($sheet, 6, $highestColumnIndex);

                // Auto-size columns
                foreach (range('A', $highestColumn) as $column) {
                    $sheet->getColumnDimension($column)->setAutoSize(true);
                }

                // Set row height for header
                $sheet->getRowDimension(7)->setRowHeight(30);

                // Freeze header row (row 7)
                $sheet->freezePane('A8');

                // Add filter to header row
                $sheet->setAutoFilter('A7:' . $highestColumn . '7');

                // Add page setup for printing
                $sheet->getPageSetup()->setFitToWidth(1);
                $sheet->getPageSetup()->setFitToHeight(0);
                $sheet->getPageSetup()->setOrientation(
                    ($this->data['orientation'] ?? 'portrait') === 'landscape'
                        ? \PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_LANDSCAPE
                        : \PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_PORTRAIT
                );

                // Add header and footer
                $headerText = '&C&"Arial,Bold"STUDENT MASTER LIST REPORT';
                if (isset($this->data['confidential']) && $this->data['confidential']) {
                    $headerText .= ' - CONFIDENTIAL';
                }
                $headerText .= '&RPage &P of &N';

                $sheet->getHeaderFooter()
                    ->setOddHeader($headerText);

                $footerText = '&LGenerated by: ' . ($this->data['generated_by'] ?? 'System') .
                             '&CGenerated on: ' . date('d/m/Y H:i:s') .
                             '&RPage &P of &N';

                if (isset($this->data['confidential']) && $this->data['confidential']) {
                    $footerText .= '&X&"Arial,Bold"CONFIDENTIAL';
                }

                $sheet->getHeaderFooter()
                    ->setOddFooter($footerText);

                // Add confidential watermark
                if (isset($this->data['confidential']) && $this->data['confidential']) {
                    $this->addConfidentialWatermark($sheet);
                }
            }
        ];
    }

    /**
     * Add column group headers for better organization
     */
    private function addColumnGroups($sheet, $rowIndex, $highestColumnIndex)
    {
        $currentColumn = 1;
        $groupRow = $rowIndex; // Row 6 for group headers

        foreach ($this->groupedColumns as $groupName => $columns) {
            $groupColumns = $this->getColumnIndicesForGroup($columns);
            if (empty($groupColumns)) continue;

            $startColumn = $groupColumns[0];
            $endColumn = end($groupColumns);

            // Merge cells for the group header
            if ($startColumn <= $endColumn) {
                $startCell = Coordinate::stringFromColumnIndex($startColumn);
                $endCell = Coordinate::stringFromColumnIndex($endColumn);

                $sheet->mergeCells($startCell . $groupRow . ':' . $endCell . $groupRow);
                $sheet->setCellValue($startCell . $groupRow, $groupName);
                $sheet->getStyle($startCell . $groupRow)->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'size' => 11,
                        'color' => ['rgb' => '333333']
                    ],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'E5E7EB']
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER
                    ],
                    'borders' => [
                        'top' => [
                            'borderStyle' => Border::BORDER_MEDIUM,
                            'color' => ['rgb' => '9CA3AF']
                        ],
                        'bottom' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => 'D1D5DB']
                        ]
                    ]
                ]);
            }
        }

        // Set group header row height
        $sheet->getRowDimension($groupRow)->setRowHeight(20);
    }

    /**
     * Get column indices for a group of columns
     */
    private function getColumnIndicesForGroup($columns)
    {
        $indices = [];
        $headerColumns = $this->data['columns'];

        foreach ($columns as $column) {
            $index = array_search($column, $headerColumns);
            if ($index !== false) {
                // Add 1 because Excel columns are 1-indexed
                $indices[] = $index + 1;
            }
        }

        sort($indices);
        return $indices;
    }

    /**
     * Add confidential watermark to the sheet
     */
    private function addConfidentialWatermark($sheet)
    {
        // Add a background text effect using header/footer
        $sheet->getHeaderFooter()
            ->setOddHeader('&C&"Arial,Bold"&44&KFF0000CONFIDENTIAL');

        // Alternative: Add text box watermark
        $drawing = new \PhpOffice\PhpSpreadsheet\Worksheet\Drawing();
        $drawing->setName('Watermark');
        $drawing->setDescription('Watermark');
        $drawing->setPath(null); // No image
        $drawing->setCoordinates('A1');
        $drawing->setOffsetX(100);
        $drawing->setOffsetY(100);
        $drawing->setWidth(400);
        $drawing->setHeight(200);
        $drawing->setWorksheet($sheet);
    }
}