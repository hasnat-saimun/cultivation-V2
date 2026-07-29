<?php

namespace App\Services\ResultCalculation;

use Illuminate\Support\Collection;

final class ComponentRequirementProfileBuilder
{
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
