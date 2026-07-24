# Result Engine Post-Deployment Checklist

Approval and evidence references: [production approval checklist](result-engine-production-approval-checklist.md), [risk assessment](result-engine-risk-assessment.md), and [historical exception manifest](result-engine-historical-exception-manifest.md).

Use only approved test institute records; never alter real student results for smoke testing.

## Immediate smoke checks

- [ ] Login and Marks Entry load
- [ ] Draft save and no-change save
- [ ] Confirm and same-revision idempotency
- [ ] Reopen with reason
- [ ] Safe publish/republish and unpublish
- [ ] Published visibility and administrative preview
- [ ] Promotion/placement Published-status guards
- [ ] Audit event creation and append-only behavior
- [ ] Teacher/Cash Admin denials and cross-scope tampering denial
- [ ] Stale revision denial and GET mutation denial
- [ ] Route, view, and config cache health
- [ ] Queue and application log health, if enabled

## Read-only integrity checks

- [ ] Historical exception counts and both manifest fingerprints are unchanged
- [ ] No new invalid reference is classified as a historical exception

- [ ] No duplicate marks business keys
- [ ] No duplicate scope states or publication scopes
- [ ] No missing required scope states
- [ ] Status values valid and revisions at least 1
- [ ] Published rows have canonical scope
- [ ] Event UUIDs present and unique
- [ ] Event updates/deletes blocked through application flows
- [ ] Retained Unpublished rows remain invisible
- [ ] No new database, deadlock, timeout, or audit-insert errors

Repeat after the first business day. Monitor Draft failures, stale conflicts, confirmation/readiness blocks, locks, duplicate conflicts, audit failures, authorization denials, and user workflow reports. Classify issues before remediation; do not weaken completeness or lifecycle rules to bypass valid failures.
