# Result Engine Production Risk Assessment

## Severity scale

| Severity | Meaning |
|---|---|
| Critical | Wrong student/result publication, irreversible academic loss, legal exposure, or uncontrolled corruption |
| High | Deployment failure, material workflow outage, unverifiable history, or likely academic misstatement |
| Medium | Recoverable operational disruption or support burden without known academic corruption |
| Low | Minor controlled inconvenience with documented workaround |

## Current risks

| Domain | Risk | Severity | Current control | Required treatment |
|---|---|---:|---|---|
| Academic | Orphan marks mapped to the wrong student | Critical | Preflight blocks; no automatic mapping | Authoritative identity evidence and dual approval |
| Academic | Missing exam/subject reconstructed with wrong configuration | Critical | No recovery authorized | Official exam/syllabus evidence and specialist approval |
| Academic | Historical rows deleted as assumed demo data | Critical | Preservation policy | Confirmed evidence plus separate retirement authorization |
| Audit | Student hard-delete paths retain no profile snapshot/deletion event | High | PB4 reconciliation evidence | Future prevention work; controlled manual evidence now |
| Legal | Student identity or results altered without authoritative record | Critical | Segregation of duties | Records-owner and change-authority approval |
| Operational | Migration remains blocked | High | Deployment HOLD | Approved remediation and complete rehearsal |
| Operational | Rollback to legacy code misreads lifecycle state | High | Compatibility rollback policy | Read-only compatibility build; preserve additive schema |
| Support | Legitimate users cannot complete result workflows during HOLD | Medium | Communicated freeze/read-only access | Planned window and stakeholder communication |
| Customer | Delayed result publication/deployment | High | Transparent status | Executive decision based on academic risk, not deadline alone |
| Security | One privileged operator performs unreviewed remediation | Critical | Dual-control policy | Separate proposer, approver, executor, verifier |
| Privacy | Evidence packages expose student personal data | High | Data minimization requirement | Restricted encrypted storage and retention controls |
| Audit | Human-memory decision cannot be reproduced | High | Tier 7 prohibited for approval | Retain cited evidence/checksums and signed manifest |

## Residual-risk acceptance

Critical academic or legal risk cannot be accepted solely by the technical team. Any residual exception requires written institution-head, academic-owner, and legal/records-owner acceptance and must still not weaken database integrity rules. If the approved disposition cannot pass preflight, deployment remains HOLD.

## Future prevention recommendations — do not implement in PB5

- Replace hard student deletion with inactive/withdrawn status and prohibit deletion when dependent academic records exist.
- Add an immutable student identity/deletion ledger with profile snapshot and actor/reason.
- Require POST/DELETE methods, CSRF, explicit permission, reason, and confirmation for master deletion.
- Add guarded deletion services for students, exams, and subjects.
- Introduce verified foreign-key or application integrity protections compatible with historical data after remediation.
- Snapshot stable student identity (`id`, `stdId`, official name) in result archives and promotion audit records.
- Preserve exam/subject configuration snapshots used by each historical result.
- Add scheduled read-only orphan monitoring and alerting.
- Require backup/checksum before bulk deletion and retain deletion manifests.
- Add tests proving master deletion cannot orphan marks, archives, attendance, promotion, placement, testimonials, or certificates.

