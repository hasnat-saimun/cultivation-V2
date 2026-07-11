<?php

namespace App\Exports;

use Illuminate\Database\Eloquent\Builder;
use App\Models\newAdmission;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\StringValueBinder;

class StudentsExport extends StringValueBinder implements FromQuery, WithMapping, WithHeadings, WithTitle, ShouldAutoSize, WithEvents, WithCustomValueBinder
{
    private Builder $query;
    private int $rowCount;

    public function __construct(Builder $query)
    {
        $this->query = $query;
        $countQuery = clone $query;
        $this->rowCount = (int) $countQuery->count('new_admissions.id');
    }

    public function headings(): array
    {
        return [
            'Student ID',
            'Student Name',
            'Class',
            'Section',
            'Session',
            'Class Roll',
            'Department',
            'Gender',
            'Date of Birth',
            'Father Name',
            'Mother Name',
            'Guardian Name',
            'Guardian Phone',
            'Student Phone',
            'Address',
        ];
    }

    public function query()
    {
        return $this->query;
    }

    public function map($student): array
    {
        /** @var newAdmission $student */
        $studentName = trim(((string) ($student->fullName ?? '')) . ' ' . ((string) ($student->sureName ?? '')));
        $studentName = $studentName !== '' ? preg_replace('/\s+/', ' ', $studentName) : 'N/A';

        return [
            $this->textValue($student->stdId),
            $studentName,
            $student->classInfo?->className ?? 'N/A',
            $student->sectionInfo?->section ?? 'N/A',
            $student->sessionInfo?->session ?? 'N/A',
            $this->textValue($student->rollNumber),
            $student->departmentInfo?->departmentName ?? 'N/A',
            $this->genderText($student->gender),
            $this->dateValue($student->dob),
            $student->father ?? 'N/A',
            $student->mother ?? 'N/A',
            $student->gurdianName ?? 'N/A',
            $this->textValue($student->gurdianMobile),
            $this->textValue($student->phone),
            $student->address ?? 'N/A',
        ];
    }

    public function title(): string
    {
        return 'Students';
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet;
                $lastColumn = 'O';
                $lastRow = $this->rowCount + 1;

                $sheet->getStyle("A1:{$lastColumn}1")->applyFromArray([
                    'font' => ['bold' => true],
                ]);

                $sheet->freezePane('A2');
                $sheet->setAutoFilter("A1:{$lastColumn}{$lastRow}");
                $sheet->getStyle("A1:{$lastColumn}{$lastRow}")->getAlignment()->setWrapText(true);
                $sheet->getStyle("A:O")->getFont()->setName('Calibri')->setSize(11);
                $sheet->getStyle("A:A")->getAlignment()->setHorizontal('center');
                $sheet->getStyle("C:F")->getAlignment()->setHorizontal('center');

                $sheet->getColumnDimension('B')->setWidth(28);
                $sheet->getColumnDimension('J')->setWidth(24);
                $sheet->getColumnDimension('K')->setWidth(24);
                $sheet->getColumnDimension('L')->setWidth(24);
                $sheet->getColumnDimension('N')->setWidth(18);
                $sheet->getColumnDimension('O')->setWidth(36);

                // Keep important identifiers as text to preserve leading zeros.
                for ($row = 2; $row <= $lastRow; $row++) {
                    foreach (['A', 'F', 'M', 'N'] as $col) {
                        $value = (string) $sheet->getCell($col . $row)->getValue();
                        $sheet->getCell($col . $row)->setValueExplicit($value, DataType::TYPE_STRING);
                    }
                }
            },
        ];
    }

    private function genderText($gender): string
    {
        $map = [
            '1' => 'Male',
            '2' => 'Female',
            '3' => 'Others',
        ];

        if ($gender === null || $gender === '') {
            return 'N/A';
        }

        return $map[(string) $gender] ?? (string) $gender;
    }

    private function dateValue($dob): string
    {
        if ($dob === null || $dob === '') {
            return 'N/A';
        }

        $ts = strtotime((string) $dob);
        if ($ts === false) {
            return (string) $dob;
        }

        return date('Y-m-d', $ts);
    }

    private function textValue($value): string
    {
        if ($value === null || $value === '') {
            return 'N/A';
        }

        return (string) $value;
    }
}
