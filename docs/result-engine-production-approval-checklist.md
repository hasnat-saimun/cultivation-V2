# Result Engine Production Approval Checklist

This checklist records approval; checking boxes without attached evidence is invalid.

## Case governance

- [ ] Every finding has a reconciliation case ID.
- [ ] Evidence sources and tiers are recorded.
- [ ] Evidence copies/checksums are retained securely.
- [ ] Student identities were independently verified twice.
- [ ] Exam recovery was approved by the Examination Controller.
- [ ] Subject recovery was approved by the academic/curriculum owner.
- [ ] Conflicts and unknowns have an explicit disposition.
- [ ] Proposer, approver, executor, and verifier are different as required.
- [ ] No mapping relies only on roll, marks, timestamp, or memory.
- [ ] No preflight or lifecycle rule was weakened.

## Pre-deployment

- [ ] Latest full production backup identity, source, timestamp, size, checksum, and operator are recorded.
- [ ] Backup restores successfully into two isolated databases.
- [ ] Approved remediation package passes on a restored copy.
- [ ] Read-only preflight and JSON preflight both PASS.
- [ ] Baseline/post-remediation row counts and raw-mark aggregates match approved expectations.
- [ ] Per-migration duration and lock behavior are measured on restored data.
- [ ] Full automated, lifecycle, authorization, security, concurrency, and report-parity tests pass.
- [ ] Maintenance window and mutation freeze are approved.
- [ ] Compatible application revision and rollback build are identified.
- [ ] Stakeholder notice and emergency contacts are confirmed.

## Deployment

- [ ] Announce window and freeze marks/result mutations.
- [ ] Verify deployed code revision.
- [ ] Take and checksum the final production backup.
- [ ] Run production preflight; stop on any blocker.
- [ ] Enable approved maintenance/read-only mode.
- [ ] Execute only the approved schema/application sequence.
- [ ] Record each migration start/end, warning, and lock observation.
- [ ] Run postconditions before application activation.
- [ ] Rebuild configuration/route/view caches.
- [ ] Keep legacy bypass paths unavailable.

## Immediate verification

- [ ] Login, Marks Entry, reports, transcript, tabulation, summary, archive, promotion, and placement read paths open.
- [ ] Designated safe Draft/no-change/Confirm/Reopen/Publish/Unpublish lifecycle succeeds.
- [ ] Idempotency, stale revision, unauthorized role, and cross-scope denial succeed.
- [ ] Audit events contain correct actor/scope/reason and no secrets.
- [ ] Published/Unpublished visibility is correct.
- [ ] Row counts, duplicates, scopes, statuses, revisions, UUIDs, and orphan checks pass.
- [ ] Application, database, queue, and security logs are reviewed.

## First 24 hours

- [ ] Monitor exceptions, deadlocks, lock waits, duplicate keys, audit failures, stale revisions, denials, readiness blocks, and latency.
- [ ] Review user-reported workflow issues without weakening rules.
- [ ] Repeat read-only integrity checks at agreed intervals.
- [ ] Classify each incident: informational, workflow, security, academic risk, or rollback candidate.
- [ ] Academic owner signs final acceptance after the monitoring window.

## Rollback triggers

Initiate emergency halt and rollback decision for:

- unexpected academic output or student association;
- partial migration/transaction or count/checksum mismatch;
- retained Unpublished data exposed as Published;
- lifecycle state/audit mismatch;
- unrecoverable database errors or unacceptable lock duration;
- security bypass or sensitive-data exposure.

## Rollback governance

- Application rollback is allowed only to a tested compatibility/read-only build.
- Destructive schema rollback after lifecycle activity is prohibited.
- Partial data rollback is prohibited when it separates marks, state, publication, or audit evidence.
- Data rollback is prohibited without a verified restore point and change-authority approval.
- Emergency halt may freeze mutations immediately; it must not improvise a repair.

## Sign-off

| Role | Name | Decision | Date/time | Evidence/ticket |
|---|---|---|---|---|
| Records officer |  |  |  |  |
| Admissions registrar |  |  |  |  |
| Examination Controller |  |  |  |  |
| Academic/curriculum owner |  |  |  |  |
| Technical owner |  |  |  |  |
| Independent verifier |  |  |  |  |
| Deployment operator |  |  |  |  |
| Institution change authority |  |  |  |  |

No blank sign-off row may be interpreted as approval.

