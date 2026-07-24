# Result Engine Security Model

## Trust boundary

The browser supplies requested academic identifiers and raw marks, but never supplies trusted actor, role, lifecycle status, audit UUID, or timestamps. `CurrentAdminResolver` resolves the actor from the authenticated admin session. Lifecycle services then resolve the canonical population and verify relationships.

## Authorization matrix

| Operation | Teacher | Cash Admin | General Admin | Super Admin | Server-side control |
|---|---:|---:|---:|---:|---|
| Save assigned Draft | Yes | No | Yes | Yes | `ResultMarksScopeService::assertActor` and population authorization |
| Confirm complete assigned scope | Yes | No | Yes | Yes | same, with complete teacher coverage |
| Reopen | No | No | Yes | Yes | `assertActor(..., true)` |
| Publish / unpublish | No | No | Yes | Yes | `ResultPublicationScopeService::assertActor` |
| Edit Confirmed or Published | No | No | No | No | state and publication locks |

Route role middleware is supplemental. Service authorization is authoritative, including direct service calls.

## Request and scope controls

- Mutation routes use POST, the `web` middleware group, session authentication, CSRF middleware, role middleware, and named throttles.
- IDs must be positive canonical integers. Publication scope resolution verifies exam, session, classes, sections, and enrolled population.
- Draft services derive authorized students from session/class/section/department/religion/fourth-subject rules. A submitted student outside that population rejects the entire request.
- Duplicate students are rejected by request validation. Draft arrays are limited to 500 entries; publication class arrays to 100.
- Revisions are positive integers and are rechecked under row locks. Client actor, role, state, timestamp, and UUID fields are ignored.
- Marks accept ordinary non-negative decimal notation only, with at most two fractional digits and the configured component maximum. Zero remains different from null.

## Mass assignment and audit

`MarksScopeState` and `ResultPublish` expose only identity fields to mass assignment. Trusted services use explicit property assignment or `forceFill` for lifecycle fields. `ResultLifecycleEvent` guards every field and can only be created by `ResultLifecycleEventService`.

Lifecycle events reject update and delete through Eloquent. The event service generates UUIDs, minimizes evidence, and rejects encoded payloads over 1 MiB without truncation. State mutation and audit insertion occur in the same database transaction.

For production, prefer a database account with `SELECT` and `INSERT`, but no `UPDATE` or `DELETE`, on `result_lifecycle_events`. No database trigger was added because hosting compatibility and rollback have not been rehearsed.

## Replay, locking, and failures

Draft, confirmation, reopen, publish, and unpublish use revision checks and deterministic canonical scope ordering. Repeated no-change saves and already-completed transitions are idempotent only at the same revision. Stale or substituted revisions fail.

Global lock order is:

1. publication scope rows in canonical scope-key order;
2. marks scope-state rows in canonical scope-key order;
3. marks rows in student ID order where required;
4. lifecycle event insert.

Transactions use Laravel's bounded three-attempt deadlock retry. Validation and authorization exceptions are not retried. Unique races are translated to controlled publication conflicts where implemented. Audit insertion failure rolls back academic mutation.

## Operational logging

Rejected controller operations log only action, actor ID/role, canonical identifier scope, category, revision, and HTTP status. Raw marks, cookies, tokens, headers, and request dumps are excluded. Authorization denials are warnings, stale conflicts notices, and ordinary validation blocks informational events. Academic audit events remain the permanent source of lifecycle evidence; application logs are diagnostic only.

## Rate limits

- Draft and legacy Draft save: 30 requests/minute per admin session and IP.
- Confirm and reopen: 12 requests/minute.
- Publish, unpublish, and legacy publication mutation: 6 requests/minute.

These limits mitigate double clicks and automation while retaining normal marks-entry throughput. Authorization and revisions remain authoritative.

## Audit view/export decision

No new audit UI or export is introduced in C5. Direct database access must be read-only and restricted to authorized General/Super administrators. An export must not be added until an operational requirement, bounded scope, streaming, authorization, and CSV-injection controls are approved.

