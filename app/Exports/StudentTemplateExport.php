<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class StudentTemplateExport implements FromArray, WithHeadings
{
    public function array(): array
    {
        return [
            [
                'John Doe',
                'Doe',
                'Robert Doe',
                'Jane Doe',
                'Male',
                '2005-01-15',
                'A+',
                'Islam',
                'john@example.com',
                '01712345678',
                'Dhaka, Bangladesh',
                '2024-2025',
                'Class 10',
                'Science',
                'A',
                '001',
                'Robert Doe',
                '01987654321',
                'Father'
            ]
        ];
    }

    public function headings(): array
    {
        return [
            'Full Name',
            'Sure Name', 
            'Father Name',
            'Mother Name',
            'Gender',
            'DOB',
            'Blood Group',
            'Religion',
            'Email',
            'Phone',
            'Address',
            'Session',
            'Class',
            'Department',
            'Section',
            'Roll',
            'Guardian',
            'Guardian Phone',
            'Relation'
        ];
    }
}