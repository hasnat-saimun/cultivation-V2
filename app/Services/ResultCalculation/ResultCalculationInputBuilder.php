<?php

namespace App\Services\ResultCalculation;

use App\Models\newAdmission;
use App\Models\ReligiousSubjectDefault;
use App\Models\Subject;
use Illuminate\Support\Collection;

class ResultCalculationInputBuilder
{
    /**
     * Load applicable subjects once for a set of students whose exam-scoped
     * marks have already been eager-loaded.
     *
     * @return array<int, Collection>
     */
    public function subjectsForStudents(iterable $students): array
    {
        $students = collect($students)->values();
        if ($students->isEmpty()) return [];

        $classIds = $students->pluck('className')->filter(fn ($id) => is_numeric($id) && (int) $id > 0)
            ->map(fn ($id) => (int) $id)->unique()->values();
        $markedSubjectIds = $students->flatMap(fn ($student) => $student->marksheet?->pluck('subjectId') ?? collect())
            ->filter(fn ($id) => is_numeric($id))->map(fn ($id) => (int) $id)->unique()->values();

        $subjects = Subject::query()->where(function ($query) use ($classIds, $markedSubjectIds) {
            $query->where('assign_class', '0');
            if ($classIds->isNotEmpty()) $query->orWhereIn('assign_class', $classIds->map(fn ($id) => (string) $id));
            if ($markedSubjectIds->isNotEmpty()) $query->orWhereIn('id', $markedSubjectIds);
        })->orderBy('id')->get();

        $religiousDefaults = ReligiousSubjectDefault::query()->whereIn('classId', $classIds)->pluck('subjectId', 'classId');
        $result = [];
        foreach ($students as $student) {
            $classId = (int) ($student->className ?? 0);
            $religiousId = (int) ($student->religiousSubjectId ?? 0)
                ?: (int) ($religiousDefaults[$classId] ?? 0)
                ?: $this->fallbackReligiousId($subjects, $classId);
            $result[(int) $student->id] = $subjects->filter(function ($subject) use ($student, $classId, $religiousId) {
                $assignedClass = (string) ($subject->assign_class ?? '0');
                $marked = $student->marksheet?->contains(fn ($mark) => (int) $mark->subjectId === (int) $subject->id) ?? false;
                if (!$marked && $assignedClass !== '0' && $assignedClass !== (string) $classId) return false;
                if (($subject->isReligious ?? false)) return $religiousId > 0 && (int) $subject->id === $religiousId;
                if (strcasecmp((string) $subject->subjectType, 'Optional') === 0) {
                    return (int) $subject->id === (int) ($student->fourthSubjectId ?? 0);
                }
                return true;
            })->values();
        }

        return $result;
    }

    public function subjectsForStudent(newAdmission $student): Collection
    {
        return $this->subjectsForStudents([$student])[(int) $student->id] ?? collect();
    }

    private function fallbackReligiousId(Collection $subjects, int $classId): int
    {
        $eligible = $subjects->filter(fn ($subject) => ($subject->isReligious ?? false)
            && in_array((string) ($subject->assign_class ?? '0'), ['0', (string) $classId], true));
        $preferred = $eligible->first(fn ($subject) => str_contains(strtolower(($subject->subjectName ?? '').' '.($subject->alias ?? '')), 'islam'));
        return (int) (($preferred ?? $eligible->first())?->id ?? 0);
    }
}
