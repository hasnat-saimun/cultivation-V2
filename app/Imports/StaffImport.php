<?php

namespace App\Imports;

use App\Models\StaffManagement;
use App\Models\Designation;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class StaffImport implements ToModel, WithHeadingRow, WithValidation
{
    private $created = 0;
    private $updated = 0;

    public function model(array $row)
    {
        if ($this->isRowEmpty($row)) {
            return null;
        }

        $row = $this->flexibleColumnMap($row);

        $staffId      = $this->nv($row, 'staff_id');
        $firstName    = $this->nv($row, 'first_name');
        $lastName     = $this->nv($row, 'last_name');
        $fathersName  = $this->nv($row, 'fathers_name');
        $mothersName  = $this->nv($row, 'mothers_name');
        $genderRaw    = $this->nv($row, 'gender');
        $dobRaw       = $this->nv($row, 'dob');
        $designationRaw = $this->nv($row, 'designation');
        $blGroupRaw   = $this->nv($row, 'blood_group');
        $religionRaw  = $this->nv($row, 'religion');
        $email        = $this->nv($row, 'email');
        $joinDateRaw  = $this->nv($row, 'join_date');
        $mobile       = $this->nv($row, 'mobile');
        $address      = $this->nv($row, 'address');
        $rank         = $this->nv($row, 'rank');

        $dob = $this->parseDate($dobRaw);
        $joinDate = $this->parseDate($joinDateRaw);

        if (!$staffId) {
            $last = StaffManagement::latest('id')->first();
            $nextId = $last ? ($last->id + 1) : 1;
            $currentYear = date('y');
            $staffId = $currentYear . str_pad($nextId, 3, '0', STR_PAD_LEFT);
        }

        $designationId = null;
        $designationName = null;
        if ($designationRaw) {
            $desig = Designation::where('type', 'staff')
                ->whereRaw('LOWER(name) = ?', [strtolower($designationRaw)])
                ->first();
            if ($desig) {
                $designationId = $desig->id;
                $designationName = $desig->name;
            } else {
                $designationName = $designationRaw;
            }
        }

        $existing = null;
        if ($staffId) {
            $existing = StaffManagement::where('staffId', $staffId)->first();
        }
        if (!$existing && $email) {
            $existing = StaffManagement::where('email', $email)->first();
        }

        if ($existing) {
            if ($firstName !== null) $existing->firstName = $firstName;
            if ($lastName !== null) $existing->lastName = $lastName;
            if ($fathersName !== null) $existing->fathersName = $fathersName;
            if ($mothersName !== null) $existing->mothersName = $mothersName;
            if ($genderRaw !== null) {
                $g = $this->getGenderValue($genderRaw);
                if ($g !== null) $existing->gender = $g;
            }
            if ($dob !== null) $existing->dob = $dob;
            if ($designationId !== null) $existing->designation_id = $designationId;
            if ($designationName !== null) $existing->designation = $designationName;
            if ($blGroupRaw !== null) {
                $bg = $this->getBloodGroupValue($blGroupRaw);
                if ($bg !== null) $existing->blGroup = $bg;
            }
            if ($religionRaw !== null) {
                $rel = $this->getReligionValue($religionRaw);
                if ($rel !== null) $existing->religion = $rel;
            }
            if ($email !== null) $existing->email = $email;
            if ($joinDate !== null) $existing->joinDate = $joinDate;
            if ($mobile !== null) $existing->mobile = $mobile;
            if ($address !== null) $existing->address = $address;
            if ($rank !== null) $existing->rank = $rank;
            $existing->updated_at = now();
            $existing->save();
            $this->updated++;
            return null;
        }

        $this->created++;
        return new StaffManagement([
            'staffId' => $staffId,
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
            'rank' => $rank,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function getCreated() { return $this->created; }
    public function getUpdated() { return $this->updated; }

    public function rules(): array
    {
        return [
            'staff_id' => 'nullable|string',
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
            'rank' => 'nullable|string',
        ];
    }

    private function parseDate($raw)
    {
        if (!$raw) return null;
        $ts = strtotime($raw);
        return $ts ? date('Y-m-d', $ts) : null;
    }

    private function getGenderValue($gender)
    {
        if (!$gender) return null;
        $map = [
            'male' => 1, 'm' => 1,
            'female' => 2, 'f' => 2,
            'others' => 3, 'other' => 3, 'o' => 3,
        ];
        $k = strtolower(trim($gender));
        return $map[$k] ?? null;
    }

    private function getBloodGroupValue($bloodGroup)
    {
        if (!$bloodGroup) return null;
        $map = [
            'a+' => 1, 'a-' => 2,
            'b+' => 3, 'b-' => 4,
            'o+' => 5, 'o-' => 6,
            'ab+' => 7, 'ab-' => 8,
        ];
        $k = strtolower(trim($bloodGroup));
        return $map[$k] ?? null;
    }

    private function getReligionValue($religion)
    {
        if (!$religion) return null;
        $map = [
            'islam' => 1, 'muslim' => 1,
            'hindu' => 2,
            'christian' => 3, 'christianity' => 3,
            'buddish' => 4, 'buddhist' => 4,
            'others' => 5, 'other' => 5,
        ];
        $k = strtolower(trim($religion));
        return $map[$k] ?? null;
    }

    private function nv(array $row, string $key)
    {
        if (!isset($row[$key])) return null;
        return $this->nvRaw($row[$key]);
    }

    private function nvRaw($value)
    {
        if ($value === null) return null;
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
        $mapping = [
            'staffid' => 'staff_id', 'staff id' => 'staff_id',
            'firstname' => 'first_name', 'first name' => 'first_name',
            'lastname' => 'last_name', 'last name' => 'last_name',
            'fathersname' => 'fathers_name', 'fathers name' => 'fathers_name',
            'fathername' => 'fathers_name', 'father name' => 'fathers_name',
            'mothersname' => 'mothers_name', 'mothers name' => 'mothers_name',
            'mothername' => 'mothers_name', 'mother name' => 'mothers_name',
            'bloodgroup' => 'blood_group', 'blood group' => 'blood_group', 'blgroup' => 'blood_group',
            'joindate' => 'join_date', 'join date' => 'join_date',
            'rank' => 'rank',
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
