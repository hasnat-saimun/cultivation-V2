<?php

namespace App\Services\ResultCalculation;

use Illuminate\Support\Collection;

final class ComponentRequirementProfileBuilder
{
    /**
     * Build one profile per actual academic scope and address it by student ID.
     *
     * @param array<int, Collection> $subjectsByStudent
     * @return array<int,array<string,array{cq:bool,mcq:bool,practical:bool}>>
     */
    public function buildByStudent(Collection $students, array $subjectsByStudent): array
    {
        $profiles = [];

        $students->groupBy(fn ($student) => implode('|', [
            (string) ($student->sessName ?? ''),
            (string) ($student->className ?? ''),
            (string) ($student->sectionName ?? ''),
            (string) ($student->departmentName ?? ''),
        ]))->each(function (Collection $scopeStudents) use ($subjectsByStudent, &$profiles) {
            $scopeSubjects = $scopeStudents->mapWithKeys(function ($student) use ($subjectsByStudent) {
                $studentId = (int) $student->id;

                return [$studentId => $subjectsByStudent[$studentId] ?? collect()];
            })->all();
            $scopeProfile = $this->build($scopeStudents, $scopeSubjects);

            foreach ($scopeStudents as $student) {
                $profiles[(int) $student->id] = $scopeProfile;
            }
        });

        return $profiles;
    }

    /**
    * A component is required when the subject configuration enables it (full mark > 0).
    * Effective evidence can additionally mark components as required, but never disable
    * configured components.
     *
     * @param array<int, Collection> $subjectsByStudent
     * @return array<string,array{cq:bool,mcq:bool,practical:bool}>
     */
    public function build(Collection $students, array $subjectsByStudent): array
    {
        $profiles = [];

        foreach ($subjectsByStudent as $subjectRows) {
            foreach ($subjectRows as $subject) {
                $subjectId = (string) $subject->id;
                $profiles[$subjectId] = [
                    'cq' => (float) ($subject->CQ ?? 0) > 0,
                    'mcq' => (float) ($subject->MCQ ?? 0) > 0,
                    'practical' => (float) ($subject->Practical ?? 0) > 0,
                ];
            }
        }

        $marksBySubject = $students
            ->flatMap(fn ($student) => $student->marksheet ?? [])
            ->groupBy(fn ($mark) => (string) ($mark->subjectId ?? ''));
        foreach ($marksBySubject as $subjectId => $marks) {
            if ($subjectId !== '' && array_key_exists($subjectId, $profiles)
                && !$marks->contains(fn ($mark) => (bool) ($mark->component_scope_tracked ?? false))) {
                $profiles[$subjectId] = ['cq' => false, 'mcq' => false, 'practical' => false];
            }
        }

        foreach ($students as $student) {
            foreach ($student->marksheet ?? [] as $mark) {
                $subjectId = (string) ($mark->subjectId ?? '');
                if ($subjectId === '' || !array_key_exists($subjectId, $profiles)) {
                    continue;
                }

                $confirmedBlank = (bool) ($mark->confirmed_blank_override ?? false);
                $componentValues = [
                    'cq' => EffectiveComponentMarkResolver::resolve($mark->subjectMarks, true, $confirmedBlank),
                    'mcq' => EffectiveComponentMarkResolver::resolve($mark->objectMarks, true, $confirmedBlank),
                    'practical' => EffectiveComponentMarkResolver::resolve($mark->practicalMarks, true, $confirmedBlank),
                ];

                foreach ($componentValues as $component => $value) {
                    if ($value !== null) {
                        $profiles[$subjectId][$component] = true;
                    }
                }
            }
        }

        return $profiles;
    }
}
