<?php

namespace App\Services;

use Illuminate\Database\ConnectionInterface;

class ResultHistoricalExceptionManifest
{
    private const FINGERPRINT_COLUMNS = [
        'id', 'studentId', 'sessionId', 'classId', 'groupId', 'examId', 'subjectId',
        'subjectMarks', 'objectMarks', 'practicalMarks', 'totalMarks', 'laterGrade',
        'gradePoint', 'created_at', 'updated_at',
    ];

    public function __construct(private readonly ConnectionInterface $db)
    {
    }

    /** @return array<string,mixed> */
    public function verify(): array
    {
        $expected = (array) config('result_engine.historical_exception_manifest', []);
        $database = $this->db->getDatabaseName();
        $applicable = in_array($database, (array) ($expected['databases'] ?? []), true);
        if (!$applicable) {
            return ['applicable' => false, 'verified' => false, 'database' => $database];
        }

        $orphanRows = $this->db->table('marksheets as m')
            ->leftJoin('new_admissions as n', fn ($join) => $join->on('n.id', '=', $this->db->raw('CAST(TRIM(m.studentId) AS UNSIGNED)')))
            ->whereNull('n.id')->orderBy('m.id')->get(array_map(fn ($column) => 'm.'.$column, self::FINGERPRINT_COLUMNS));
        $missingMasterRows = $this->db->table('marksheets as m')
            ->leftJoin('exams as e', fn ($join) => $join->on('e.id', '=', $this->db->raw('CAST(TRIM(m.examId) AS UNSIGNED)')))
            ->leftJoin('subjects as s', fn ($join) => $join->on('s.id', '=', $this->db->raw('CAST(TRIM(m.subjectId) AS UNSIGNED)')))
            ->where(fn ($query) => $query->whereNull('e.id')->orWhereNull('s.id'))
            ->orderBy('m.id')->get(array_map(fn ($column) => 'm.'.$column, self::FINGERPRINT_COLUMNS));

        $actual = [
            'orphan_student_ids' => $orphanRows->pluck('studentId')->map(fn ($id) => (int) $id)->unique()->sort()->values()->all(),
            'orphan_marks_count' => $orphanRows->count(),
            'missing_exam_ids' => $this->missingIds('examId', 'exams'),
            'missing_subject_ids' => $this->missingIds('subjectId', 'subjects'),
            'orphan_marks_sha256' => $this->fingerprint($orphanRows),
            'missing_master_marks_sha256' => $this->singleRecordFingerprint($missingMasterRows),
        ];
        $expectedComparable = collect($actual)->mapWithKeys(fn ($value, $key) => [$key => $expected[$key] ?? null])->all();

        return [
            'applicable' => true,
            'verified' => $actual === $expectedComparable,
            'database' => $database,
            'expected' => $expectedComparable,
            'actual' => $actual,
        ];
    }

    private function missingIds(string $column, string $masterTable): array
    {
        return $this->db->table('marksheets as m')
            ->leftJoin($masterTable.' as master', fn ($join) => $join->on('master.id', '=', $this->db->raw("CAST(TRIM(m.{$column}) AS UNSIGNED)")))
            ->whereNull('master.id')->pluck('m.'.$column)->map(fn ($id) => (int) $id)
            ->unique()->sort()->values()->all();
    }

    private function fingerprint($rows): string
    {
        return hash('sha256', json_encode($this->payload($rows), JSON_PRESERVE_ZERO_FRACTION | JSON_UNESCAPED_SLASHES));
    }

    private function singleRecordFingerprint($rows): string
    {
        $payload = $this->payload($rows);
        return hash('sha256', json_encode(
            count($payload) === 1 ? $payload[0] : $payload,
            JSON_PRESERVE_ZERO_FRACTION | JSON_UNESCAPED_SLASHES
        ));
    }

    private function payload($rows): array
    {
        return $rows->map(fn ($row) => array_combine(
            self::FINGERPRINT_COLUMNS,
            array_map(fn ($column) => $row->{$column}, self::FINGERPRINT_COLUMNS)
        ))->all();
    }
}
