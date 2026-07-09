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
        $currentYear = date('y');
        $sixDigitId = str_pad($nextId, 6, "0", STR_PAD_LEFT);
        $stdId = $currentYear . $sixDigitId;

        // Normalize all incoming values; blank/whitespace becomes null
        $fullName   = $this->nv($row, 'full_name');
        $sureName   = $this->nv($row, 'sure_name');
        $fatherName = $this->nv($row, 'father');
        $motherName = $this->nv($row, 'mother');
        $dobRaw     = $this->nv($row, 'dob');
        $mail       = $this->nv($row, 'email');
        $phone      = $this->nv($row, 'phone');
        $address    = $this->nv($row, 'address');
        $roll       = $this->nv($row, 'roll');
        $fourthSubRaw = $this->nv($row, '4th_subject') ?? $this->nv($row, 'fourth_subject');
        $guardian   = $this->nv($row, 'guardian');
        $guardianPhone = $this->nv($row, 'guardian_phone');
        $relation   = $this->nv($row, 'relation');

        $dob = null;
        if ($dobRaw) {
            $ts = strtotime($dobRaw);
            $dob = $ts ? date('Y-m-d', $ts) : null;
        }

        return new newAdmission([
            'stdId' => $stdId,
            'fullName' => $fullName,
            'sureName' => $sureName,
            'father' => $fatherName,
            'mother' => $motherName,
            'gender' => $this->getGenderValue($this->nv($row, 'gender')),
            'dob' => $dob,
            'blGroup' => $this->getBloodGroupValue($this->nv($row, 'blood_group')),
            'religion' => $this->getReligionValue($this->nv($row, 'religion')),
            'mail' => $mail,
            'phone' => $phone,
            'address' => $address,
            'sessName' => $this->getSessionId($this->nv($row, 'session')),
            'className' => $this->getClassId($this->nv($row, 'class')),
            'departmentName' => $this->getDepartmentId($this->nv($row, 'department')),
            'sectionName' => $this->getSectionId($this->nv($row, 'section')),
            'fourthSubjectId' => $this->getOptionalSubjectId($fourthSubRaw),
            'rollNumber' => $roll,
            'gurdianName' => $guardian,
            'gurdianMobile' => $guardianPhone,
            'relationGurdian' => $this->getRelationValue($relation),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function rules(): array
    {
        return [
            // Make all fields nullable so blank cells import as null
            'full_name' => 'nullable|string',
            'father' => 'nullable|string',
            'mother' => 'nullable|string',
            'gender' => 'nullable|string',
            'religion' => 'nullable|string',
            '4th_subject' => 'nullable|string',
            'fourth_subject' => 'nullable|string',
        ];
    }

    private function getGenderValue($gender)
    {
        if(!$gender){ return null; }
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
        if(!$bloodGroup){ return null; }
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
        if(!$religion){ return null; }
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

    private function getRelationValue($relation)
    {
        if(!$relation){ return null; }
        $relations = [
            'father' => 1,
            'mother' => 2,
            'brother' => 3,
            'sister' => 4,
            'uncle' => 5,
            'aunty' => 6,
            'aunt' => 6,
            'other' => 7,
        ];
        $key = strtolower(trim($relation));
        return $relations[$key] ?? null;
    }

    private function getSessionId($sessionName)
    {
        $name = $this->nvRaw($sessionName);
        if(!$name){ return null; }
        $session = \App\Models\sessionManage::whereRaw('LOWER(session) = ?', [strtolower($name)])->first();
        return $session ? $session->id : null;
    }

    private function getClassId($className)
    {
        $name = $this->nvRaw($className);
        if(!$name){ return null; }
        static $map = null;
        if($map === null){
            $map = [];
            $classes = \App\Models\classManage::all();
            foreach($classes as $c){
                $slug = $this->classSlug($c->className);
                $map[$slug] = $c->id;
                $map[strtolower(trim($c->className))] = $c->id;
            }
        }
        $slugInput = $this->classSlug($name);
        if(isset($map[$slugInput])){ return $map[$slugInput]; }
        $lower = strtolower(trim($name));
        return $map[$lower] ?? null;
    }

    private function getDepartmentId($departmentName)
    {
        $name = $this->nvRaw($departmentName);
        if(!$name){ return null; }
        $department = \App\Models\Department::whereRaw('LOWER(departmentName) = ?', [strtolower($name)])->first();
        return $department ? $department->id : null;
    }

    private function getSectionId($sectionName)
    {
        $name = $this->nvRaw($sectionName);
        if(!$name){ return null; }
        $section = \App\Models\sectionManage::whereRaw('LOWER(section) = ?', [strtolower($name)])->first();
        return $section ? $section->id : null;
    }

    private function getOptionalSubjectId($subjectName)
    {
        $name = $this->nvRaw($subjectName);
        if(!$name){ return null; }

        if(is_numeric($name)){
            $id = (int)$name;
            $subject = \App\Models\Subject::where('id', $id)->where('subjectType', 'Optional')->first();
            return $subject ? $subject->id : null;
        }

        $subject = \App\Models\Subject::whereRaw('LOWER(subjectName) = ?', [strtolower($name)])
            ->where('subjectType', 'Optional')
            ->first();
        return $subject ? $subject->id : null;
    }

    // Normalize a value: trim string, empty => null
    private function nv(array $row, string $key)
    {
        if(!isset($row[$key])){ return null; }
        return $this->nvRaw($row[$key]);
    }

    private function nvRaw($value)
    {
        if($value === null){ return null; }
        if(is_string($value)){
            $v = trim($value);
            return $v === '' ? null : $v;
        }
        return $value === '' ? null : $value;
    }

    // Build a canonical slug for class names so "Six", "Class six", "6", "VI" all match
    private function classSlug($name)
    {
        $v = strtolower(trim((string)$name));
        // strip common prefixes
        $v = preg_replace('/\b(class|grade|std|standard)\b/','', $v);
        $v = preg_replace('/[^a-z0-9 ]+/',' ', $v);
        $v = preg_replace('/\s+/',' ', $v);
        $v = trim($v);
        $mapWord = [
            'one'=>1,'two'=>2,'three'=>3,'four'=>4,'five'=>5,'six'=>6,'seven'=>7,'eight'=>8,'nine'=>9,'ten'=>10,
            'eleven'=>11,'twelve'=>12,
            'i'=>1,'ii'=>2,'iii'=>3,'iv'=>4,'v'=>5,'vi'=>6,'vii'=>7,'viii'=>8,'ix'=>9,'x'=>10,'xi'=>11,'xii'=>12,
            'kg'=>0,'nursery'=>0,'play'=>0,
        ];
        $parts = explode(' ', $v);
        $out = [];
        foreach($parts as $p){
            if($p === ''){ continue; }
            if(isset($mapWord[$p])){ $out[] = (string)$mapWord[$p]; continue; }
            // numeric stays numeric string
            if(is_numeric($p)) { $out[] = (string)(int)$p; continue; }
            $out[] = $p;
        }
        $slug = implode('-', $out);
        return $slug !== '' ? $slug : $v;
    }
}