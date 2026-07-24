# Result Engine Phase C6 Rehearsal Report

Date: 2026-07-24 (Asia/Dhaka)

## Executive summary

Phase C6 stopped at the mandatory backup-identification gate. Three local SQL dump candidates were found, but none has sufficient external provenance to establish that it is the latest full production backup. No database was created, restored, migrated, or mutated.

Final classification:

```text
HOLD — VERIFIED PRODUCTION BACKUP NOT AVAILABLE
```

## Candidate inventory

| Candidate | Dump generation metadata | Size | SHA-256 | Content assessment |
|---|---|---:|---|---|
| `cultivation.sql` | 2026-07-21 19:02 UTC; database label `cultivation`; MariaDB 10.4.32 | 24,732 bytes | `7D11626FC53F1600A2BA7C3946F71A848FCEDF045FAC2F26744FE57AC8C7FB9E` | Complete SQL transaction marker, but only 9 tables; no `marksheets`, `result_publishes`, or `new_admissions`. Not a full Result Engine production backup. |
| `cultivation_shs.sql` | 2026-07-21 17:09 (dump metadata); database label `cultivation_shs`; MariaDB 10.6.27 | 147,940 bytes | `20EAF28F6A314238BD7F1CDBAF588B278D01C81DAF812AFF5E1E98A61EB85DA4` | 67 table definitions and 21 tables with data. Required result tables exist, but no marks or publication data statements were found. Production identity and completeness are unverified. |
| `cultivation_rhs.sql` | 2026-07-20 22:23 (dump metadata); database label `cultivation_rhs`; MariaDB 10.6.27 | 3,131,482 bytes | `062C81295D6CDA1BB54D886929EBC3925FC2F1A7210F5EEA5B7EA144CE7DC8A7` | 68 table definitions and 36 tables with data. Contains admissions and substantial marks data; publication table exists but has no data statement. This is the strongest technical candidate, but source, operator, and “latest production” status are unverified. |

The workspace copy of `cultivation.sql` is byte-identical to the Downloads copy and therefore is not independent backup evidence.

## Verification performed

- Files were readable.
- SHA-256 checksums were calculated.
- phpMyAdmin headers, database labels, dump generation metadata, MariaDB versions, table definitions, data-statement presence, and final transaction markers were inspected.
- No credentials, private URLs, or row-level student data were copied into this report.

## Missing mandatory evidence

- authoritative production database name/institute;
- confirmation of which candidate corresponds to the live production system;
- backup source and export/download procedure;
- responsible operator;
- evidence that the selected file is the latest full backup;
- creation/export timestamp confirmation independent of local file metadata;
- storage/retention record.

## Actions not performed

Because the identification gate failed, Phase C6 correctly did not:

- create or overwrite any database;
- restore a dump;
- run production-data preflight;
- run Phase C2 migrations;
- capture migration or lock timing;
- select or mutate a lifecycle rehearsal scope;
- run role/concurrency rehearsal against restored production data;
- rehearse destructive schema rollback;
- claim a downtime classification or deployment certification.

## Required unblock

An authorized operator must identify the latest full production dump and provide:

1. the exact local file path or securely delivered file;
2. production database/institute identity;
3. backup source;
4. creation and export/download timestamps;
5. operator identity;
6. confirmation that it is a full data-and-schema backup and the latest available backup.

After that evidence is recorded, checksum verification and isolated restoration may proceed. The restore target must be a new database such as `cultivation_rehearsal_20260724`, never `cultivation` or `cultivation_test`.

