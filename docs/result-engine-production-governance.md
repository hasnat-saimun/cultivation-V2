# Result Engine Production Governance

## Purpose and current decision

This policy governs production-data remediation and Result Engine deployment approval. It does not authorize a data change.

Current evidence:

- 5,997 marks rows;
- 5,902 valid admission references;
- 95 orphan rows across eight missing internal student IDs;
- one marks row referencing missing exam 1 and subject 1;
- no reliable master identity for all affected records.

The current deployment state is HOLD until authorized people make documented decisions under this policy and a separately approved remediation/rehearsal succeeds.

## Governing principles

1. Academic identity must be proven, never inferred from a single mutable attribute.
2. Preservation is preferred to deletion when evidence is incomplete.
3. No operator may propose, approve, execute, and verify the same remediation.
4. Database changes require a separate change authorization; this document is not that authorization.
5. Preflight and lifecycle rules remain unchanged.
6. Every decision must be reproducible from retained evidence.
7. A technically successful migration is not deployment approval.

## Historical evidence hierarchy

| Tier | Evidence source | Reliability | Permitted use |
|---:|---|---|---|
| 1 | Verified full production backup from before the master deletion, with checksum and provenance | Authoritative | May establish original internal ID and full master record |
| 2 | Official signed admission register plus unique admission/student number | Authoritative institutional evidence | May confirm identity when matched to scope and at least one other stable attribute |
| 3 | Government/board registration record with unique registration number | Authoritative external evidence | May confirm name and official identity; must still be linked to internal ID using independent evidence |
| 4 | Signed official tabulation/result register | Strong academic evidence | May confirm academic scope, roll, and result; insufficient alone to recreate an internal master |
| 5 | Issued marksheet/transcript bearing verifiable institute seal/serial | Supporting evidence | May corroborate identity and scope; insufficient alone if roll can be reused |
| 6 | Archived system export, roster, attendance export, promotion export, or immutable application audit | Supporting technical evidence | May corroborate; provenance and timestamp must be verified |
| 7 | Staff/student/guardian recollection, screenshots, handwritten notes, unsourced spreadsheets | Weak evidence | Lead-generation only; never sufficient for remediation |

### Acceptance rule

Student recovery requires either:

- one Tier 1 record; or
- one Tier 2 or Tier 3 record plus an independent Tier 2–6 source agreeing on at least full name, session, class, section, roll/admission number, and the historical internal ID linkage.

Tier 4–7 evidence cannot independently authorize creation or remapping. Conflicting Tier 1–3 evidence is an automatic HOLD pending academic-owner adjudication.

## Roles and segregation of duties

| Responsibility | Accountable role |
|---|---|
| Evidence custodian | Institution records officer |
| Student identity verification | Admissions registrar |
| Exam identity verification | Examination controller |
| Subject identity verification | Academic head/curriculum owner |
| Technical remediation design | Result Engine technical owner |
| Independent technical verification | Separate database/application reviewer |
| Production change approval | Institution head or formally delegated change authority |
| Deployment execution | Named deployment operator |
| Rollback authorization | Change authority with technical owner |
| Emergency halt | Deployment operator, technical owner, or institution head |

Cash Admin, ordinary teachers, and the remediation implementer cannot approve historical identity recovery. Super Admin application access does not by itself confer academic approval authority.

## Decision and audit record

Every case must have a unique reconciliation case ID and record:

- date/time and environment;
- finding category and affected primary/business keys;
- proposed action and explicit non-actions;
- evidence inventory, tier, source, owner, checksum/reference, and capture date;
- conflicts and how they were resolved;
- academic, legal, operational, and rollback risk;
- proposer, verifier, academic approver, technical approver, and deployment approver;
- before-state query/export checksum;
- approved transactional change specification;
- postcondition queries and expected counts;
- execution operator/time/change ticket;
- observed result and independent verification;
- rollback point and retention location.

Evidence containing personal data must use controlled access, encryption, minimal distribution, and the institution's retention policy. Reports should use limited identifiers and never publish credentials.

## Emergency governance

Any person in the deployment chain may halt when identity evidence conflicts, checksums differ, preflight output changes unexpectedly, a transaction partially commits, academic outputs differ, or rollback evidence is unavailable. Halt authority does not grant authority to repair.

