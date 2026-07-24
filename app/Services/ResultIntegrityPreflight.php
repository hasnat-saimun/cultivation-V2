<?php

namespace App\Services;

use Illuminate\Database\ConnectionInterface;

class ResultIntegrityPreflight
{
    public function __construct(
        private readonly ConnectionInterface $db,
        private readonly ResultHistoricalExceptionManifest $historicalManifest,
    ) {}

    /** @return array<int,array{code:string,table:string,count:int,blocking:bool,samples:array}> */
    public function inspect(): array
    {
        $findings = [];
        $manifest = $this->historicalManifest->verify();
        $required = ['studentId', 'sessionId', 'classId', 'examId', 'subjectId'];

        foreach ($required as $column) {
            $this->finding($findings, 'marks_required_'.$column, 'marksheets',
                "SELECT COUNT(*) aggregate FROM marksheets WHERE {$column} IS NULL",
                "SELECT id FROM marksheets WHERE {$column} IS NULL ORDER BY id LIMIT 5");
            $this->finding($findings, 'marks_oversize_'.$column, 'marksheets',
                "SELECT COUNT(*) aggregate FROM marksheets WHERE CHAR_LENGTH(TRIM({$column})) > 64",
                "SELECT id, CHAR_LENGTH({$column}) length FROM marksheets WHERE CHAR_LENGTH(TRIM({$column})) > 64 ORDER BY id LIMIT 5");
            $this->finding($findings, 'marks_trim_'.$column, 'marksheets',
                "SELECT COUNT(*) aggregate FROM marksheets WHERE {$column}<>TRIM({$column})",
                "SELECT id, {$column} value FROM marksheets WHERE {$column}<>TRIM({$column}) ORDER BY id LIMIT 5",
                false);
        }

        foreach (['studentId', 'classId', 'examId', 'subjectId'] as $column) {
            $this->finding($findings, 'marks_nonnumeric_'.$column, 'marksheets',
                "SELECT COUNT(*) aggregate FROM marksheets WHERE TRIM({$column}) NOT REGEXP '^[0-9]+$'",
                "SELECT id, {$column} value FROM marksheets WHERE TRIM({$column}) NOT REGEXP '^[0-9]+$' ORDER BY id LIMIT 5");
        }
        foreach ([
            'studentId' => ['new_admissions', 'id'],
            'classId' => ['class_manages', 'id'],
            'examId' => ['exams', 'id'],
            'subjectId' => ['subjects', 'id'],
        ] as $column => [$targetTable, $targetColumn]) {
            $this->finding($findings, 'marks_invalid_reference_'.$column, 'marksheets',
                "SELECT COUNT(*) aggregate FROM marksheets m LEFT JOIN {$targetTable} r ON r.{$targetColumn}=CAST(TRIM(m.{$column}) AS UNSIGNED) WHERE r.{$targetColumn} IS NULL",
                "SELECT m.id, m.{$column} value FROM marksheets m LEFT JOIN {$targetTable} r ON r.{$targetColumn}=CAST(TRIM(m.{$column}) AS UNSIGNED) WHERE r.{$targetColumn} IS NULL ORDER BY m.id LIMIT 5");
        }
        $this->finding($findings, 'marks_unresolvable_session', 'marksheets',
            "SELECT COUNT(*) aggregate FROM marksheets m LEFT JOIN session_manages sid ON sid.id=CAST(TRIM(m.sessionId) AS UNSIGNED) WHERE sid.id IS NULL AND (SELECT COUNT(*) FROM session_manages slabel WHERE slabel.session=TRIM(m.sessionId))<>1",
            "SELECT m.id, m.sessionId value FROM marksheets m LEFT JOIN session_manages sid ON sid.id=CAST(TRIM(m.sessionId) AS UNSIGNED) WHERE sid.id IS NULL AND (SELECT COUNT(*) FROM session_manages slabel WHERE slabel.session=TRIM(m.sessionId))<>1 ORDER BY m.id LIMIT 5");

        $this->finding($findings, 'marks_oversize_groupId', 'marksheets',
            "SELECT COUNT(*) aggregate FROM marksheets WHERE CHAR_LENGTH(TRIM(groupId)) > 64",
            "SELECT id, CHAR_LENGTH(groupId) length FROM marksheets WHERE CHAR_LENGTH(TRIM(groupId)) > 64 ORDER BY id LIMIT 5");
        $this->finding($findings, 'marks_invalid_groupId', 'marksheets',
            "SELECT COUNT(*) aggregate FROM marksheets WHERE groupId IS NOT NULL AND TRIM(groupId) NOT IN ('','0') AND TRIM(groupId) NOT REGEXP '^[1-9][0-9]*$'",
            "SELECT id, groupId value FROM marksheets WHERE groupId IS NOT NULL AND TRIM(groupId) NOT IN ('','0') AND TRIM(groupId) NOT REGEXP '^[1-9][0-9]*$' ORDER BY id LIMIT 5");
        $this->finding($findings, 'marks_invalid_section', 'marksheets',
            "SELECT COUNT(*) aggregate FROM marksheets m LEFT JOIN section_manages s ON s.id=CAST(TRIM(m.groupId) AS UNSIGNED) WHERE m.groupId IS NOT NULL AND TRIM(m.groupId) NOT IN ('','0') AND TRIM(m.groupId) REGEXP '^[1-9][0-9]*$' AND s.id IS NULL",
            "SELECT m.id, m.groupId value FROM marksheets m LEFT JOIN section_manages s ON s.id=CAST(TRIM(m.groupId) AS UNSIGNED) WHERE m.groupId IS NOT NULL AND TRIM(m.groupId) NOT IN ('','0') AND TRIM(m.groupId) REGEXP '^[1-9][0-9]*$' AND s.id IS NULL ORDER BY m.id LIMIT 5");
        $this->finding($findings, 'marks_session_label_mappings', 'marksheets',
            "SELECT COUNT(DISTINCT TRIM(m.sessionId)) aggregate FROM marksheets m LEFT JOIN session_manages sid ON sid.id=CAST(TRIM(m.sessionId) AS UNSIGNED) WHERE sid.id IS NULL AND (SELECT COUNT(*) FROM session_manages s WHERE s.session=TRIM(m.sessionId))=1",
            "SELECT DISTINCT TRIM(m.sessionId) old_value,(SELECT CAST(MIN(id) AS CHAR) FROM session_manages s WHERE s.session=TRIM(m.sessionId)) new_value FROM marksheets m LEFT JOIN session_manages sid ON sid.id=CAST(TRIM(m.sessionId) AS UNSIGNED) WHERE sid.id IS NULL AND (SELECT COUNT(*) FROM session_manages s WHERE s.session=TRIM(m.sessionId))=1 LIMIT 5",
            false);
        $this->finding($findings, 'marks_classwide_group_normalizations', 'marksheets',
            "SELECT COUNT(*) aggregate FROM marksheets WHERE groupId IS NOT NULL AND TRIM(groupId) IN ('','0')",
            "SELECT id, groupId value FROM marksheets WHERE groupId IS NOT NULL AND TRIM(groupId) IN ('','0') ORDER BY id LIMIT 5",
            false);

        $marksGroup = "CASE WHEN groupId IS NULL OR TRIM(groupId) IN ('','0') THEN 'class' ELSE CONCAT('section:',CAST(CAST(TRIM(groupId) AS UNSIGNED) AS CHAR)) END";
        $session = "CASE WHEN EXISTS(SELECT 1 FROM session_manages sid WHERE sid.id=CAST(TRIM(marksheets.sessionId) AS UNSIGNED)) THEN TRIM(sessionId) ELSE (SELECT CAST(MIN(id) AS CHAR) FROM session_manages WHERE session=TRIM(marksheets.sessionId) HAVING COUNT(*)=1) END";
        $this->collision($findings, 'marks_exact_duplicates', 'marksheets',
            'studentId,sessionId,classId,groupId,examId,subjectId');
        $this->collision($findings, 'marks_normalized_collisions', 'marksheets',
            "TRIM(studentId),{$session},TRIM(classId),{$marksGroup},TRIM(examId),TRIM(subjectId)");

        foreach (['examId', 'sessionId', 'classId'] as $column) {
            $this->finding($findings, 'publication_oversize_'.$column, 'result_publishes',
                "SELECT COUNT(*) aggregate FROM result_publishes WHERE CHAR_LENGTH(TRIM({$column})) > 64",
                "SELECT id, CHAR_LENGTH({$column}) length FROM result_publishes WHERE CHAR_LENGTH(TRIM({$column})) > 64 ORDER BY id LIMIT 5");
            $this->finding($findings, 'publication_trim_'.$column, 'result_publishes',
                "SELECT COUNT(*) aggregate FROM result_publishes WHERE {$column}<>TRIM({$column})",
                "SELECT id, {$column} value FROM result_publishes WHERE {$column}<>TRIM({$column}) ORDER BY id LIMIT 5",
                false);
        }
        $this->finding($findings, 'publication_oversize_groupId', 'result_publishes',
            'SELECT COUNT(*) aggregate FROM result_publishes WHERE CHAR_LENGTH(TRIM(groupId)) > 64',
            'SELECT id, CHAR_LENGTH(groupId) length FROM result_publishes WHERE CHAR_LENGTH(TRIM(groupId)) > 64 ORDER BY id LIMIT 5');
        foreach ([
            'examId' => 'exams',
            'sessionId' => 'session_manages',
            'classId' => 'class_manages',
        ] as $column => $targetTable) {
            $this->finding($findings, 'publication_invalid_reference_'.$column, 'result_publishes',
                "SELECT COUNT(*) aggregate FROM result_publishes p LEFT JOIN {$targetTable} r ON r.id=CAST(TRIM(p.{$column}) AS UNSIGNED) WHERE r.id IS NULL",
                "SELECT p.id, p.{$column} value FROM result_publishes p LEFT JOIN {$targetTable} r ON r.id=CAST(TRIM(p.{$column}) AS UNSIGNED) WHERE r.id IS NULL ORDER BY p.id LIMIT 5");
        }
        $this->finding($findings, 'publication_invalid_section', 'result_publishes',
            "SELECT COUNT(*) aggregate FROM result_publishes p LEFT JOIN section_manages s ON s.id=CAST(TRIM(p.groupId) AS UNSIGNED) WHERE p.groupId IS NOT NULL AND TRIM(p.groupId) NOT IN ('','0') AND s.id IS NULL",
            "SELECT p.id, p.groupId value FROM result_publishes p LEFT JOIN section_manages s ON s.id=CAST(TRIM(p.groupId) AS UNSIGNED) WHERE p.groupId IS NOT NULL AND TRIM(p.groupId) NOT IN ('','0') AND s.id IS NULL ORDER BY p.id LIMIT 5");
        $this->finding($findings, 'publication_invalid_groupId', 'result_publishes',
            "SELECT COUNT(*) aggregate FROM result_publishes WHERE groupId IS NOT NULL AND TRIM(groupId) NOT IN ('','0') AND TRIM(groupId) NOT REGEXP '^[1-9][0-9]*$'",
            "SELECT id, groupId value FROM result_publishes WHERE groupId IS NOT NULL AND TRIM(groupId) NOT IN ('','0') AND TRIM(groupId) NOT REGEXP '^[1-9][0-9]*$' ORDER BY id LIMIT 5");
        $this->finding($findings, 'publication_invalid_actor', 'result_publishes',
            'SELECT COUNT(*) aggregate FROM result_publishes p LEFT JOIN cultivation_admins a ON a.id=p.published_by WHERE p.published_by IS NOT NULL AND a.id IS NULL',
            'SELECT p.id, p.published_by value FROM result_publishes p LEFT JOIN cultivation_admins a ON a.id=p.published_by WHERE p.published_by IS NOT NULL AND a.id IS NULL ORDER BY p.id LIMIT 5');

        $pubGroup = "CASE WHEN groupId IS NULL OR TRIM(groupId) IN ('','0') THEN 'class' ELSE CONCAT('section:',CAST(CAST(TRIM(groupId) AS UNSIGNED) AS CHAR)) END";
        $this->collision($findings, 'publication_exact_duplicates', 'result_publishes',
            'examId,sessionId,classId,groupId');
        $this->collision($findings, 'publication_normalized_collisions', 'result_publishes',
            "TRIM(examId),TRIM(sessionId),TRIM(classId),{$pubGroup}");

        if ($manifest['applicable']) {
            foreach ($findings as &$finding) {
                if (in_array($finding['code'], [
                    'marks_invalid_reference_studentId',
                    'marks_invalid_reference_examId',
                    'marks_invalid_reference_subjectId',
                ], true)) {
                    $finding['blocking'] = !$manifest['verified'];
                    $finding['category'] = $manifest['verified']
                        ? 'historical_legacy_exception'
                        : 'integrity_violation';
                }
            }
            unset($finding);
            $findings[] = [
                'code' => 'historical_exception_manifest',
                'table' => 'marksheets',
                'count' => $manifest['verified'] ? 0 : 1,
                'blocking' => !$manifest['verified'],
                'category' => $manifest['verified']
                    ? 'historical_legacy_exception'
                    : 'integrity_violation',
                'samples' => $manifest['verified'] ? [] : [[
                    'expected' => $manifest['expected'],
                    'actual' => $manifest['actual'],
                ]],
                'details' => $manifest['verified'] ? $manifest['actual'] : null,
            ];
        }

        return $findings;
    }

    public function blockingFindings(): array
    {
        return array_values(array_filter($this->inspect(), fn (array $finding) => $finding['blocking'] && $finding['count'] > 0));
    }

    private function finding(
        array &$findings,
        string $code,
        string $table,
        string $countSql,
        string $sampleSql,
        bool $blocking = true
    ): void
    {
        $count = (int) ($this->db->selectOne($countSql)->aggregate ?? 0);
        $findings[] = [
            'code' => $code,
            'table' => $table,
            'count' => $count,
            'blocking' => $blocking,
            'category' => $blocking ? 'integrity_violation' : 'normalization',
            'samples' => $count > 0 ? array_map(fn ($row) => (array) $row, $this->db->select($sampleSql)) : [],
        ];
    }

    private function collision(array &$findings, string $code, string $table, string $groupExpression): void
    {
        $sql = "SELECT COUNT(*) aggregate FROM (SELECT 1 FROM {$table} GROUP BY {$groupExpression} HAVING COUNT(*)>1) duplicate_groups";
        $this->finding($findings, $code, $table, $sql,
            "SELECT MIN(id) sample_id, COUNT(*) duplicate_count FROM {$table} GROUP BY {$groupExpression} HAVING COUNT(*)>1 LIMIT 5");
    }
}
