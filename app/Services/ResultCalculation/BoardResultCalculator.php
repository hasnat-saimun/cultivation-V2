<?php

namespace App\Services\ResultCalculation;

use Illuminate\Support\Collection;
use InvalidArgumentException;

final class BoardResultCalculator
{
    /**
     * Calculate one entered subject through the same normalization and component rules
     * used by complete student results.
     */
    public function calculateSubject(
        object|array $student,
        object|array $exam,
        object|array $mark,
        object|array $subject,
    ): SubjectResult {
        $result = $this->calculate($student, $exam, [$mark], [$subject]);
        $subjectId = (string) $this->value($subject, 'id');
        $subjectResult = collect($result->subjectResults)->first(
            fn (SubjectResult $item) => in_array($subjectId, $item->sourceSubjectIds, true)
        );

        if (!$subjectResult) {
            throw new InvalidArgumentException("Subject {$subjectId} is not applicable to the selected student.");
        }

        return $subjectResult;
    }

    /** Pure calculation over already-loaded records; performs no queries or writes. */
    public function calculate(object|array $student, object|array $exam, iterable $marks, iterable $subjects): StudentResult
    {
        $warnings = [];
        $subjects = collect($subjects)->values();
        $marksBySubject = collect($marks)->values()->groupBy(fn ($m) => (string) $this->value($m, 'subjectId'));
        $subjectById = $subjects->keyBy(fn ($s) => (string) $this->value($s, 'id'));

        foreach ($marksBySubject as $id => $rows) {
            if ($rows->count() > 1) $warnings[] = "Duplicate marks rows found for subject {$id}.";
        }

        $fourthId = $this->positiveId($this->value($student, 'fourthSubjectId'));
        if ($fourthId !== null) {
            $assigned = $subjectById->get((string) $fourthId);
            if (!$assigned || !$this->isOptional($assigned)) {
                $warnings[] = "Assigned fourth subject {$fourthId} is missing or not Optional.";
            }
        }
        $enteredOptional = $subjects->filter(fn ($s) => $this->isOptional($s))
            ->filter(fn ($s) => $this->hasEnteredMark($marksBySubject->get((string) $this->value($s, 'id'), collect())));
        if ($enteredOptional->count() > 1) {
            $warnings[] = 'Multiple optional-subject marks are present; only the assigned fourth subject can receive a bonus.';
        }

        [$units, $pairWarnings] = $this->subjectUnits($subjects);
        $warnings = array_merge($warnings, $pairWarnings);
        $results = [];
        foreach ($units as $unit) {
            $unitSubjects = collect($unit['subjects']);
            $optional = $unitSubjects->contains(fn ($s) => $this->isOptional($s));
            $ids = $unitSubjects->map(fn ($s) => (string) $this->value($s, 'id'))->all();
            if ($optional && ($fourthId === null || !in_array((string) $fourthId, $ids, true))) continue;
            $results[] = $this->calculateUnit($unit['id'], $unitSubjects, $marksBySubject, $exam, $optional, $warnings);
        }

        $compulsory = array_values(array_filter($results, fn ($r) => $r->isCompulsory));
        $optional = array_values(array_filter($results, fn ($r) => $r->isOptional));
        $failed = array_values(array_map(fn ($r) => $r->subjectId, array_filter($compulsory, fn ($r) => $r->status === 'Fail')));
        $missing = array_values(array_map(fn ($r) => $r->subjectId, array_filter($compulsory, fn ($r) => $r->missing)));
        $sum = array_sum(array_map(fn ($r) => $r->gradePoint, $compulsory));
        $count = count($compulsory);
        $optionalGp = $optional[0]->gradePoint ?? null;
        $bonus = $optionalGp === null ? 0.0 : max($optionalGp - 2.0, 0.0);

        if ($count === 0) {
            $warnings[] = 'No compulsory subjects were supplied for calculation.';
            [$status, $gpa] = ['Incomplete', null];
        } elseif ($missing !== []) {
            [$status, $gpa] = ['Incomplete', null];
        } elseif ($failed !== []) {
            [$status, $gpa] = ['Fail', 0.0];
        } else {
            [$status, $gpa] = ['Pass', min(5.0, round(($sum + $bonus) / $count, 2))];
        }

        return new StudentResult($results, round($sum, 2), $count, $optionalGp, round($bonus, 2), $failed, $missing, $gpa, $status, array_values(array_unique($warnings)));
    }

    /** @return array{0:string,1:float} */
    public function gradeForPercentage(float $percentage): array
    {
        if ($percentage < 0 || $percentage > 100) throw new InvalidArgumentException('Percentage must be between 0 and 100.');
        if ($percentage >= 80) return ['A+', 5.0];
        if ($percentage >= 70) return ['A', 4.0];
        if ($percentage >= 60) return ['A-', 3.5];
        if ($percentage >= 50) return ['B', 3.0];
        if ($percentage >= 40) return ['C', 2.0];
        if ($percentage >= 33) return ['D', 1.0];
        return ['F', 0.0];
    }

    private function calculateUnit(string $id, Collection $subjects, Collection $marksBySubject, object|array $exam, bool $optional, array &$warnings): SubjectResult
    {
        $full = $got = ['cq' => 0.0, 'mcq' => 0.0, 'practical' => 0.0];
        $missing = false; $paperFailures = []; $sourceIds = [];
        $featureWise = (int) $this->value($exam, 'passingSystem') === 1;
        foreach ($subjects as $subject) {
            $sid = (string) $this->value($subject, 'id'); $sourceIds[] = $sid;
            $rows = $marksBySubject->get($sid, collect());
            $mark = $rows->sortBy(fn ($r) => (int) ($this->value($r, 'id') ?? 0))->last();
            if (!$mark) { $missing = true; continue; }
            foreach ($this->components() as $key => [$fullField, $markField]) {
                $max = $this->numeric($this->value($subject, $fullField)) ?? 0.0; $full[$key] += $max;
                if ($max <= 0) continue;
                $value = EffectiveComponentMarkResolver::resolve(
                    $this->value($mark, $markField),
                    true,
                    (bool) $this->value($mark, 'confirmed_blank_override'),
                );
                if ($value === null) { $missing = true; continue; }
                if ($value < 0 || $value > $max) {
                    $warnings[] = "Subject {$sid} {$key} marks are outside 0-{$max}."; $missing = true; continue;
                }
                $got[$key] += $value;
                if (($value / $max) * 100 < 33) $paperFailures[] = "paper:{$sid}:{$key}";
            }
        }
        $fullMarks = array_sum($full); $obtained = array_sum($got);
        if ($fullMarks <= 0) { $warnings[] = "Subject {$id} has no positive configured full marks."; $missing = true; }
        $type = $optional ? 'Optional' : (string) ($this->value($subjects->first(), 'subjectType') ?: 'Compulsory');
        if ($missing) return new SubjectResult($id, $type, null, $fullMarks, null, '-', 0.0, 'Incomplete', $optional, !$optional, array_values(array_unique($paperFailures)), true, $sourceIds);

        $percentage = ($obtained / $fullMarks) * 100;
        [$letter, $point] = $this->gradeForPercentage($percentage);
        $combinedFailures = [];
        if ($featureWise) foreach ($got as $key => $value) if ($full[$key] > 0 && ($value / $full[$key]) * 100 < 33) $combinedFailures[] = $key;
        $fails = $letter === 'F' || ($featureWise && $combinedFailures !== []);
        if ($fails) { $letter = 'F'; $point = 0.0; }
        return new SubjectResult($id, $type, round($obtained, 2), round($fullMarks, 2), round($percentage, 4), $letter, $point, $fails ? 'Fail' : 'Pass', $optional, !$optional, array_values(array_unique(array_merge($combinedFailures, $paperFailures))), false, $sourceIds);
    }

    /** @return array{0:array,1:array} */
    private function subjectUnits(Collection $subjects): array
    {
        $groups = []; $units = []; $warnings = [];
        foreach ($subjects as $subject) {
            $key = $this->configuredPairKey($subject);
            if ($key === null) $units[] = ['id' => (string) $this->value($subject, 'id'), 'subjects' => [$subject]];
            else $groups[$key][] = $subject;
        }
        foreach ($groups as $key => $items) {
            if (count($items) === 2) $units[] = ['id' => 'pair:'.$key, 'subjects' => $items];
            else {
                $warnings[] = "Configured pair {$key} has ".count($items).' subject(s); subjects were not merged.';
                foreach ($items as $subject) $units[] = ['id' => (string) $this->value($subject, 'id'), 'subjects' => [$subject]];
            }
        }
        return [$units, $warnings];
    }

    private function configuredPairKey(object|array $subject): ?string
    {
        $id = $this->value($subject, 'id'); $ids = config('subject_pairs.ids', []);
        if ($id !== null && array_key_exists($id, $ids)) return strtolower(trim((string) $ids[$id]));
        $alias = strtolower(trim((string) $this->value($subject, 'alias'))); $aliases = config('subject_pairs.aliases', []);
        if ($alias !== '' && array_key_exists($alias, $aliases)) return strtolower(trim((string) $aliases[$alias]));
        $name = trim((string) $this->value($subject, 'subjectName')); $names = config('subject_pairs.names', []);
        return $name !== '' && array_key_exists($name, $names) ? strtolower(trim((string) $names[$name])) : null;
    }

    private function components(): array { return ['cq' => ['CQ', 'subjectMarks'], 'mcq' => ['MCQ', 'objectMarks'], 'practical' => ['Practical', 'practicalMarks']]; }
    private function isOptional(object|array $s): bool { return strcasecmp(trim((string) $this->value($s, 'subjectType')), 'Optional') === 0; }
    private function hasEnteredMark(Collection $rows): bool { return $rows->contains(fn ($r) => collect($this->components())->contains(fn ($fields) => $this->numeric($this->value($r, $fields[1])) !== null)); }
    private function value(object|array|null $r, string $key): mixed { return $r === null ? null : (is_array($r) ? ($r[$key] ?? null) : ($r->{$key} ?? null)); }
    private function numeric(mixed $v): ?float { return $v === null || $v === '' || !is_numeric($v) ? null : (float) $v; }
    private function positiveId(mixed $v): ?int { return is_numeric($v) && (int) $v > 0 ? (int) $v : null; }
}
