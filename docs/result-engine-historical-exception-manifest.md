# Result Engine Historical Exception Manifest

Manifest date: 2026-07-24  
Source environment: restored `cultivation_rhs_rehearsal` database  
Authority: PB4 reconciliation and PB5 governance  
Purpose: identify immutable pre-existing legacy exceptions; this is not remediation or permission to bypass integrity controls.

## Student-reference exception set

| Missing `new_admissions.id` | Marks rows | Disposition |
|---:|---:|---|
| 5 | 15 | Preserve unchanged; historical identity unresolved |
| 6 | 15 | Preserve unchanged; historical identity unresolved |
| 48 | 14 | Preserve unchanged; historical identity unresolved |
| 64 | 14 | Preserve unchanged; historical identity unresolved |
| 257 | 14 | Preserve unchanged; historical identity unresolved |
| 332 | 15 | Preserve unchanged; manual decision unresolved |
| 344 | 4 | Preserve unchanged; manual decision unresolved |
| 347 | 4 | Preserve unchanged; manual decision unresolved |
| **Total** | **95** | **No delete, insert, recreation, or remap authorized** |

Deterministic evidence fingerprint:

```text
Algorithm: SHA-256
Ordering: marksheets.id ascending
Fields: id, studentId, sessionId, classId, groupId, examId, subjectId,
        subjectMarks, objectMarks, practicalMarks, totalMarks, laterGrade,
        gradePoint, created_at, updated_at
Digest: 737830306cae440444fcea0437c4473ab90f1b86d1cfc6b50366cc9e2b1f7f82
```

The fingerprint detects unexpected historical-row changes; it does not validate or recover identity.

## Missing exam/subject exception

`marksheets.id = 1` references:

- valid current internal student ID 1;
- missing `exams.id = 1`;
- missing `subjects.id = 1`.

The row is unique for both missing master IDs and remains an unresolved manual decision. It is not confirmed test/demo data.

Evidence fingerprint:

```text
Algorithm: SHA-256
Fields: same ordered field set used above
Digest: d63713e71e1ffea159b306f30cdf6a58b442a2ba5aedf0d2e79d93f220da478d
```

## Investigation and governance references

- [Production data reconciliation](result-engine-production-data-reconciliation.md)
- [Production governance](result-engine-production-governance.md)
- [Manual remediation policy](result-engine-manual-remediation-policy.md)
- [Risk assessment](result-engine-risk-assessment.md)
- [Production approval checklist](result-engine-production-approval-checklist.md)

## Risk summary

- Wrong remapping can assign results to another student.
- Deletion can erase genuine academic history.
- Recreating an exam or subject without authoritative configuration can fabricate academic meaning.
- Treating the manifest as a wildcard exception would permit new corruption.

## Control boundary

Only the exact pre-deployment rows and identifiers above are historical exceptions. Any new invalid student, exam, subject, class, section, session, or scope reference is Category A deployment-blocking corruption. The manifest cannot authorize a new write, suppress a new finding, change a row, or weaken validation.

