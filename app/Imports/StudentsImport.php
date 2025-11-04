<?php

namespace App\Imports;

use App\Models\newAdmission;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class StudentsImport implements ToModel, WithHeadingRow, WithValidation
{
    public function model(array $row)
    {
        // Get the last record ID to generate new student ID
        $lastRecord = newAdmission::latest('id')->first();
        $nextId = $lastRecord ? ($lastRecord->id + 1) : 1;
        $currentYear = date('Y');
        $sixDigitId = str_pad($nextId, 6, "0", STR_PAD_LEFT);
        $stdId = $currentYear . $sixDigitId;

        return new newAdmission([
            'stdId' => $stdId,
            'fullName' => $row['full_name'],
            'sureName' => $row['sure_name'],
            'father' => $row['father_name'],
            'mother' => $row['mother_name'],
            'gender' => $this->getGenderValue($row['gender']),
            'dob' => $row['dob'] ? date('Y-m-d', strtotime($row['dob'])) : null,
            'blGroup' => $this->getBloodGroupValue($row['blood_group']),
            'religion' => $this->getReligionValue($row['religion']),
            'mail' => $row['email'],
            'phone' => $row['phone'],
            'address' => $row['address'],
            'sessName' => $this->getSessionId($row['session']),
            'className' => $this->getClassId($row['class']),
            'departmentName' => $this->getDepartmentId($row['department']),
            'sectionName' => $this->getSectionId($row['section']),
            'rollNumber' => $row['roll'],
            'guardianName' => $row['guardian'],
            'guardianPhone' => $row['guardian_phone'],
            'relationGuardian' => $this->getRelationValue($row['relation']),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function rules(): array
    {
        return [
            'full_name' => 'required|string',
            'father_name' => 'required|string',
            'mother_name' => 'required|string',
            'gender' => 'required|string',
            'religion' => 'required|string',
        ];
    }

    private function getGenderValue($gender)
    {
        $genders = ['Male' => 1, 'Female' => 2, 'Others' => 3];
        return $genders[$gender] ?? 1;
    }

    private function getBloodGroupValue($bloodGroup)
    {
        $groups = ['A+' => 1, 'A-' => 2, 'B+' => 3, 'B-' => 4, 'O+' => 5, 'O-' => 6, 'AB+' => 7, 'AB-' => 8];
        return $groups[$bloodGroup] ?? 1;
    }

    private function getReligionValue($religion)
    {
        $religions = ['Islam' => 1, 'Hindu' => 2, 'Christian' => 3, 'Buddish' => 4, 'Others' => 5];
        return $religions[$religion] ?? 1;
    }

    private function getRelationValue($relation)
    {
        $relations = ['Father' => 1, 'Mother' => 2, 'Brother' => 3, 'Sister' => 4, 'Uncle' => 5, 'Aunty' => 6, 'Other' => 7];
        return $relations[$relation] ?? 1;
    }

    private function getSessionId($sessionName)
    {
        $session = \App\Models\sessionManage::where('session', $sessionName)->first();
        return $session ? $session->id : null;
    }

    private function getClassId($className)
    {
        $class = \App\Models\classManage::where('className', $className)->first();
        return $class ? $class->id : null;
    }

    private function getDepartmentId($departmentName)
    {
        $department = \App\Models\Department::where('departmentName', $departmentName)->first();
        return $department ? $department->id : null;
    }

    private function getSectionId($sectionName)
    {
        $section = \App\Models\sectionManage::where('section', $sectionName)->first();
        return $section ? $section->id : null;
    }
}