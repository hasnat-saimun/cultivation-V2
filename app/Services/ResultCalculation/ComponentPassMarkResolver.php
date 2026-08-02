<?php

namespace App\Services\ResultCalculation;

final class ComponentPassMarkResolver
{
    public function resolve(float $fullMarks, object|array|null $subject = null, ?string $component = null): int
    {
        if ($fullMarks <= 0) return 0;

        $explicit = $this->explicitPassMark($subject, $component);
        if ($explicit !== null) {
            return max(0, min((int) round($fullMarks), $explicit));
        }

        $percentage = (float) config('result_engine.pass_threshold.default_percentage', 0.33);
        return max(0, (int) round($fullMarks * $percentage, 0, PHP_ROUND_HALF_UP));
    }

    private function explicitPassMark(object|array|null $subject, ?string $component): ?int
    {
        if ($subject === null || $component === null) return null;

        $subjectId = $this->value($subject, 'id');
        $configured = $subjectId === null
            ? null
            : config("result_engine.pass_threshold.subjects.{$subjectId}.{$component}");
        if (is_numeric($configured)) return (int) $configured;

        $fields = (array) config("result_engine.pass_threshold.component_fields.{$component}", []);
        foreach ($fields as $field) {
            $value = $this->value($subject, (string) $field);
            if (is_numeric($value)) return (int) $value;
        }

        return null;
    }

    private function value(object|array $subject, string $field): mixed
    {
        return is_array($subject) ? ($subject[$field] ?? null) : ($subject->{$field} ?? null);
    }
}
