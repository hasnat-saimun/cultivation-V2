<?php

namespace App\Services;

use App\Models\Subject;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class SubjectClassScopeService
{
    public function selectedClassIds(Subject $subject): array
    {
        $rows = DB::table('subject_class_scopes')->where('subject_id', $subject->id)->pluck('class_id');
        if ($rows->isNotEmpty()) {
            return $rows->contains(null) ? $this->allClassIds() : $rows->map(fn ($id) => (int) $id)->all();
        }

        $legacy = trim((string) $subject->assign_class);
        if ($legacy === '0') {
            return $this->allClassIds();
        }

        return collect(explode(',', $legacy))->filter(fn ($id) => is_numeric($id) && (int) $id > 0)
            ->map(fn ($id) => (int) $id)->unique()->sort()->values()->all();
    }

    public function isAllClasses(Subject $subject): bool
    {
        $rows = DB::table('subject_class_scopes')->where('subject_id', $subject->id)->pluck('class_id');
        return $rows->isNotEmpty() ? $rows->contains(null) : trim((string) $subject->assign_class) === '0';
    }

    public function validate(string $name, array $classIds, bool $allClasses = false, ?int $ignoreSubjectId = null): array
    {
        $selected = $allClasses ? $this->allClassIds() : collect($classIds)->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)->unique()->sort()->values()->all();

        if (!$selected || DB::table('class_manages')->whereIn('id', $selected)->count() !== count($selected)) {
            throw ValidationException::withMessages(['classIds' => 'Select one or more valid classes, or choose All Classes.']);
        }

        $normalized = $this->normalizeName($name);
        $conflicts = Subject::query()->when($ignoreSubjectId, fn ($q) => $q->whereKeyNot($ignoreSubjectId))->get()
            ->filter(fn (Subject $subject) => $this->normalizeName($subject->subjectName) === $normalized)
            ->filter(fn (Subject $subject) => array_intersect($selected, $this->selectedClassIds($subject)))
            ->map(fn (Subject $subject) => $subject->subjectName.' (#'.$subject->id.')')
            ->values();

        if ($conflicts->isNotEmpty()) {
            throw ValidationException::withMessages([
                'classIds' => 'The normalized subject name overlaps an existing class scope: '.$conflicts->implode(', '),
            ]);
        }

        return $selected;
    }

    public function sync(Subject $subject, array $classIds, bool $allClasses = false): void
    {
        DB::table('subject_class_scopes')->where('subject_id', $subject->id)->delete();
        $now = now();

        if ($allClasses) {
            DB::table('subject_class_scopes')->insert([
                'subject_id' => $subject->id, 'class_id' => null, 'created_at' => $now, 'updated_at' => $now,
            ]);
            $subject->assign_class = '0';
        } else {
            foreach ($classIds as $classId) {
                DB::table('subject_class_scopes')->insert([
                    'subject_id' => $subject->id, 'class_id' => $classId, 'created_at' => $now, 'updated_at' => $now,
                ]);
            }
            $subject->assign_class = implode(',', $classIds);
        }

        $subject->save();
    }

    private function allClassIds(): array
    {
        return DB::table('class_manages')->orderBy('id')->pluck('id')->map(fn ($id) => (int) $id)->all();
    }

    private function normalizeName(string $name): string
    {
        return mb_strtolower(preg_replace('/\s+/u', ' ', trim($name)));
    }
}
