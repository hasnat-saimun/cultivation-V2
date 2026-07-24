# Result Engine Manual Remediation Policy

## Scope

This policy defines how a future separately authorized remediation may be designed. It does not approve or implement remediation.

## Student master recovery

### Required evidence

For each missing internal ID, the case file must prove:

- the original `new_admissions.id`;
- stable external `stdId` or admission/registration number;
- full official name;
- historical session, class, section, and roll;
- applicable department, religion/religious subject, and fourth subject where relevant;
- status and effective historical period;
- why the master disappeared.

Use the governance evidence hierarchy. Roll, class, marks pattern, timestamp proximity, or a current row occupying the same roll is never enough.

### Verification workflow

1. Records officer inventories evidence without editing it.
2. Registrar prepares a candidate identity statement.
3. A second verifier independently repeats the match without seeing the first conclusion.
4. Differences are documented and escalated.
5. Academic owner confirms the affected scopes.
6. Technical owner prepares an exact transactional plan and postconditions.
7. Independent reviewer confirms no collision with current IDs, `stdId`, roll, archives, promotions, attendance, certificates, or placements.
8. Change authority signs approval.
9. A disposable restored backup is remediated and preflight/migration/full regression rerun.
10. Only after rehearsal evidence passes may a separate production change be scheduled.

### Conflict handling

- Conflicting authoritative evidence: HOLD.
- Same roll assigned to different students: roll is non-identifying; HOLD pending stable identifiers.
- Name spelling differences: require authoritative registration/admission number and documented normalization.
- Missing internal-ID linkage: do not invent an ID.
- Evidence only at Tier 4–7: preserve as orphan; do not recover.

## Missing exam recovery

Recovery requires a verified historical source proving:

- original `exams.id`;
- official exam name/type;
- session/class applicability;
- exam dates and configuration affecting results, including passing system;
- evidence that recreating the master does not conflict with an existing exam.

Required approval: Examination Controller, technical owner, independent verifier, and production change authority.

Recovery is prohibited when only marks timestamps or a guessed name are available; configuration affecting calculation is unknown; the ID is occupied; evidence conflicts; or the record is merely suspected to be demo data.

## Missing subject recovery

Recovery requires:

- original `subjects.id`;
- official subject name/code and type;
- class assignment;
- CQ/MCQ/practical maxima and component requirements;
- pairing, religious, optional/fourth-subject meaning where applicable;
- historical syllabus/register evidence for the relevant session.

Required approval: Academic head/curriculum owner, Examination Controller, technical owner, independent verifier, and change authority.

Forbidden actions include copying a current subject into the old ID, guessing component maxima, remapping marks to a similar name, changing grading rules, or using a seeder default as historical proof.

## Orphan academic-record policy

| Classification | Definition | Handling |
|---|---|---|
| Recoverable | Identity and full required master attributes meet the evidence rule | Prepare a separately authorized, rehearsed restoration preserving original identifiers |
| Historical preserved | Academic evidence is credible but master identity is insufficient | Preserve unchanged, restrict publication/use, document the exception; preflight remains blocking |
| Unrecoverable | Authoritative evidence proves no reliable reconstruction is possible | Preserve evidence; any retirement requires legal/academic retention approval and a separate destructive-change authorization |
| Manual retirement candidate | Authoritative evidence proves the row was non-academic test/demo or legally approved for retirement | Do not delete under this policy; prepare a distinct retirement proposal with backup and audit |
| Unknown | Evidence is absent or conflicting | HOLD; no mapping, deletion, publication, or migration bypass |

Current PB4 classifications remain:

- IDs 5, 6, 48, 64, 257: historical preserved, pending exact identity evidence;
- IDs 332, 344, 347: unknown/manual decision;
- marks row 1 with missing exam 1 and subject 1: unknown/manual decision;
- no confirmed demo record.

## Change package and rollback

Any future remediation package must be atomic, narrowly scoped, peer-reviewed, backed up, checksummed, and rehearsed on a new restored copy. It must include exact before/after manifests and postcondition SQL. Partial success is prohibited.

Rollback must restore the complete pre-change database or execute a rehearsed inverse transaction that preserves every academic row and audit record. A rollback plan based on deleting newly recovered masters without restoring dependent state is prohibited.

