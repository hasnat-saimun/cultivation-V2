<?php

namespace App\Imports;

use App\Models\TeacherManagement;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class TeachersImport implements ToModel, WithHeadingRow, WithValidation
{
    private $created = 0;
    private $updated = 0;

    public function model(array $row)
    {
        // Skip completely empty rows
        if ($this->isRowEmpty($row)) {
            return null;
        }

        // Map flexible column names with aliases
        $row = $this->flexibleColumnMap($row);

        // Normalize all incoming values; blank/whitespace becomes null
        $teacherId = $this->nv($row, 'teacher_id');
        $firstName = $this->nv($row, 'first_name');
        $lastName = $this->nv($row, 'last_name');
        $fathersName = $this->nv($row, 'fathers_name');
        $mothersName = $this->nv($row, 'mothers_name');
        $genderRaw = $this->nv($row, 'gender');
        $dobRaw = $this->nv($row, 'dob');
        $designationRaw = $this->nv($row, 'designation');
        $blGroupRaw = $this->nv($row, 'blood_group');
        $religionRaw = $this->nv($row, 'religion');
        $email = $this->nv($row, 'email');
        $joinDateRaw = $this->nv($row, 'join_date');
        $mobile = $this->nv($row, 'mobile');
        $address = $this->nv($row, 'address');
        $mpoIndex = $this->nv($row, 'mpo_index');
        $pdsId = $this->nv($row, 'pds_id');
        $rank = $this->nv($row, 'rank');

        // Parse dates
        $dob = null;
        if ($dobRaw) {
            $ts = strtotime($dobRaw);
            $dob = $ts ? date('Y-m-d', $ts) : null;
        }

        $joinDate = null;
        if ($joinDateRaw) {
            $ts = strtotime($joinDateRaw);
            $joinDate = $ts ? date('Y-m-d', $ts) : null;
        }

        // Generate teacherId if not provided
        if (!$teacherId) {
            $lastRecord = TeacherManagement::latest('id')->first();
            $nextId = $lastRecord ? ($lastRecord->id + 1) : 1;
            $currentYear = date('y');
            $sixDigitId = str_pad($nextId, 3, "0", STR_PAD_LEFT);
            $teacherId = $currentYear . $sixDigitId;
        }

        // Resolve designation to ID
        $designationId = null;
        $designationName = null;
        if ($designationRaw) {
            $desig = \App\Models\Designation::where('type', 'teacher')
                ->whereRaw('LOWER(name) = ?', [strtolower($designationRaw)])
                ->first();
            if ($desig) {
                $designationId = $desig->id;
                $designationName = $desig->name;
            } else {
                // If designation not found, store the raw value
                $designationName = $designationRaw;
            }
        }

        // Check if teacher already exists (by teacherId or email)
        $existing = null;
        if ($teacherId) {
            $existing = TeacherManagement::where('teacherId', $teacherId)->first();
        }
        if (!$existing && $email) {
            $existing = TeacherManagement::where('email', $email)->first();
        }

        if ($existing) {
            // Update existing teacher - only update non-null values
            if ($firstName !== null) $existing->firstName = $firstName;
            if ($lastName !== null) $existing->lastName = $lastName;
            if ($fathersName !== null) $existing->fathersName = $fathersName;
            if ($mothersName !== null) $existing->mothersName = $mothersName;
            if ($genderRaw !== null) {
                $genderValue = $this->getGenderValue($genderRaw);
                if ($genderValue !== null) $existing->gender = $genderValue;
            }
            if ($dob !== null) $existing->dob = $dob;
            if ($designationId !== null) $existing->designation_id = $designationId;
            if ($designationName !== null) $existing->designation = $designationName;
            if ($blGroupRaw !== null) {
                $blValue = $this->getBloodGroupValue($blGroupRaw);
                if ($blValue !== null) $existing->blGroup = $blValue;
            }
            if ($religionRaw !== null) {
                $religionValue = $this->getReligionValue($religionRaw);
                if ($religionValue !== null) $existing->religion = $religionValue;
            }
            if ($email !== null) $existing->email = $email;
            if ($joinDate !== null) $existing->joinDate = $joinDate;
            if ($mobile !== null) $existing->mobile = $mobile;
            if ($address !== null) $existing->address = $address;
            if ($mpoIndex !== null) $existing->mpoIndex = $mpoIndex;
            if ($pdsId !== null) $existing->pdsId = $pdsId;
            if ($rank !== null) $existing->rank = $rank;
            
            $existing->updated_at = now();
            $existing->save();
            $this->updated++;
            return null; // Don't create new record
        }

        // Create new teacher
        $this->created++;
        return new TeacherManagement([
            'teacherId' => $teacherId,
            'firstName' => $firstName,
            'lastName' => $lastName,
            'fathersName' => $fathersName,
            'mothersName' => $mothersName,
            'gender' => $this->getGenderValue($genderRaw),
            'dob' => $dob,
            'designation_id' => $designationId,
            'designation' => $designationName,
            'blGroup' => $this->getBloodGroupValue($blGroupRaw),
            'religion' => $this->getReligionValue($religionRaw),
            'email' => $email,
            'joinDate' => $joinDate,
            'mobile' => $mobile,
            'address' => $address,
            'mpoIndex' => $mpoIndex,
            'pdsId' => $pdsId,
            'rank' => $rank,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function getCreated()
    {
        return $this->created;
    }

    public function getUpdated()
    {
        return $this->updated;
    }

    public function rules(): array
    {
        return [
            // Make all fields nullable so blank cells import as null
            'teacher_id' => 'nullable|string',
            'first_name' => 'nullable|string',
            'last_name' => 'nullable|string',
            'fathers_name' => 'nullable|string',
            'mothers_name' => 'nullable|string',
            'gender' => 'nullable|string',
            'dob' => 'nullable|date_format:Y-m-d,m/d/Y,d-m-Y',
            'designation' => 'nullable|string',
            'blood_group' => 'nullable|string',
            'religion' => 'nullable|string',
            'email' => 'nullable|email',
            'join_date' => 'nullable|date_format:Y-m-d,m/d/Y,d-m-Y',
            'mobile' => 'nullable|string',
            'address' => 'nullable|string',
            'mpo_index' => 'nullable|string',
            'pds_id' => 'nullable|string',
            'rank' => 'nullable|string',
        ];
    }

    private function getGenderValue($gender)
    {
        if (!$gender) {
            return null;
        }
        $genders = [
            'male' => 1,
            'm' => 1,
            'female' => 2,
            'f' => 2,
            'others' => 3,
            'other' => 3,
            'o' => 3,
        ];
        $key = strtolower(trim($gender));
        return $genders[$key] ?? null;
    }

    private function getBloodGroupValue($bloodGroup)
    {
        if (!$bloodGroup) {
            return null;
        }
        $groups = [
            'a+' => 1, 'a-' => 2,
            'b+' => 3, 'b-' => 4,
            'o+' => 5, 'o-' => 6,
            'ab+' => 7, 'ab-' => 8,
        ];
        $key = strtolower(trim($bloodGroup));
        return $groups[$key] ?? null;
    }

    private function getReligionValue($religion)
    {
        if (!$religion) {
            return null;
        }
        $religions = [
            'islam' => 1,
            'muslim' => 1,
            'hindu' => 2,
            'christian' => 3,
            'christianity' => 3,
            'buddish' => 4,
            'buddhist' => 4,
            'others' => 5,
            'other' => 5,
        ];
        $key = strtolower(trim($religion));
        return $religions[$key] ?? null;
    }

    // Normalize a value: trim string, empty => null
    private function nv(array $row, string $key)
    {
        if (!isset($row[$key])) {
            return null;
        }
        return $this->nvRaw($row[$key]);
    }

    private function nvRaw($value)
    {
        if ($value === null) {
            return null;
        }
        if (is_string($value)) {
            $v = trim($value);
            return $v === '' ? null : $v;
        }
        return $value === '' ? null : $value;
    }

    private function isRowEmpty(array $row): bool
    {
        foreach ($row as $val) {
            if ($this->nvRaw($val) !== null) {
                return false;
            }
        }
        return true;
    }

    private function flexibleColumnMap(array $row): array
    {
        /**
         * Maps alternative column names to standard ones
         * Handles camelCase, snake_case, and spaces
         */
        $mapping = [
            'teacherid' => 'teacher_id',
            'teacher id' => 'teacher_id',
            'firstname' => 'first_name',
            'first name' => 'first_name',
            'lastname' => 'last_name',
            'last name' => 'last_name',
            'lastname' => 'last_name',
            'fathersname' => 'fathers_name',
            'fathers name' => 'fathers_name',
            'fathername' => 'fathers_name',
            'father name' => 'fathers_name',
            'mothersname' => 'mothers_name',
            'mothers name' => 'mothers_name',
            'mothername' => 'mothers_name',
            'mother name' => 'mothers_name',
            'bloodgroup' => 'blood_group',
            'blood group' => 'blood_group',
            'blgroup' => 'blood_group',
            'joindate' => 'join_date',
            'join date' => 'join_date',
            'mpoindex' => 'mpo_index',
            'mpo index' => 'mpo_index',
            'mpo' => 'mpo_index',
            'pdsid' => 'pds_id',
            'pds id' => 'pds_id',
            'pds' => 'pds_id',
        ];

        $mapped = [];
        foreach ($row as $key => $value) {
            $normalizedKey = strtolower(trim(str_replace(['-', '_'], ' ', (string)$key)));
            $standardKey = $mapping[$normalizedKey] ?? $key;
            $mapped[$standardKey] = $value;
        }
        return $mapped;
    }
}
