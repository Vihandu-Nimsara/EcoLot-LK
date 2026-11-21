# Recycler authorization foundation implementation — Phase 2A.2

> Historical milestone evidence. Current feature scope, MySQL/MariaDB compatibility and acceptance results: [final completion](recycler-profile-final-completion.md). Prior engine-specific deployment instructions are superseded.
Date: 19 November 2026 (Asia/Colombo). Branch: `feature/admin-recycler-clean-rebuild`.

## Status and boundary

Implemented the controlled Admin/Recycler foundation from `recycler-authorization-reconciliation-plan.md`. No main development migration, record update, scope backfill, PDF replacement, branch change or commit was performed. Existing uncommitted work was preserved.

**The foundation is ready for a separate E-Lot eligibility integration milestone. It is not a claim that bidding is reconciled or ready for deployment as complete authorization.** Bid/application/award code and all legacy triggers remain unchanged. The current differences between fresh and legacy bid enforcement still require the separately authorized integration below.

## Changes implemented

1. Add nullable `authorized_recyclers.current_license_id`, an index and owner-bound composite FK to `(recycler_licenses.license_id,recycler_user_id)`. The FK restricts deletion/update of a selected identity. It is selection metadata, not an authorization grant. No existing owner is automatically assigned a selection.
2. Install the same foundation changes in the fresh schema and the new additive migration, after the existing scope migration. Add a canonical scope CHECK for risk 0/1, no EPL-only high-risk grant, lowercase hexadecimal SHA-256 and nonblank evidence fields. Existing decisions are validated, never rewritten to pass a constraint.
3. Separate selected/current approved/initial/history/pending request reads in `AuthorizedRecycler`. A selected VALID row must have recorded reviewer and review time to qualify as approved. Expiry is checked independently by consumers. No fallback to an older VALID licence or a newer upload occurs.
4. Successful existing final initial approval assigns its checked licence ID inside the existing transaction. Registration/OTP and initial licence/capability/activity/scope decision flow remain intact. No completed VERIFIED registration is reopened or automatically reverified.
5. Profile, owner PDF, dashboard compliance and completed Admin details/PDF reads use the selected identity. PENDING initial Admin review retains the original latest-submission contract; rejected initial Admin records remain readable. The only Profile presentation change is accurate selected/initial record wording and an unverified state for a VALID row lacking review metadata. No layout/CSS changes.
6. Extend the existing `RecyclerLicenseReviewService` with `requireApprovedScope(owner, category, reviewedActivities, highRisk)`. It requires an ACTIVE RECYCLER with VERIFIED registration, APPROVED category and every supplied APPROVED activity, valid reviewed selected licence, validated private PDF, exact metadata/path/hash identity and matching canonical VERIFIED scope. Explicit high-risk capability and scope must both exist. Empty activities deny. It creates no reviews, grants, scopes or bid mutations.
7. Licence seed conflicts are now no-ops. Existing documents, expiry/status, owner and reviewer fields are not overwritten by the two demo licence upserts. No change was made to other modules' seed logic; the entire demo seed is still not a production migration tool.

## Exact files

Modified during this task:

- `app/Models/AuthorizedRecycler.php` — selection/compliance and owner-filtered audit reads.
- `app/Services/RecyclerApprovalService.php` — atomic initial approval selection.
- `app/Services/RecyclerLicenseReviewService.php` — reviewed identity and operational canonical checks, reusing existing scope predicate and document validator.
- `app/Controllers/AdminController.php` — initial versus selected completed-record reads.
- `app/Controllers/RecyclerController.php` — selected Profile/PDF and explicit review metadata.
- `app/Views/recycler/profile.php` — record wording/unverified state only.
- `database/schema.sql` — additive foundation DDL matching migration.
- `database/seed.sql` — non-overwriting licence conflicts.
- `tests/recycler-profile-ui.php` — honest unselected legacy-record presentation.

New:

- `database/migrations/20261119_recycler_authorization_foundation.sql`.
- `tests/recycler-authorization-foundation.php`.
- `docs/recycler-authorization-foundation-implementation.md`.

The earlier `20261116_recycler_initial_scope.sql`, bid models/services/controllers, Officer/Collector modules, legacy tables/triggers, registration/OTP files, CSS and JavaScript were not changed in this task.

## Canonical licence selection rules

| Record/context | Rule |
|---|---|
| Current selected identity | `selectedLicenseForRecycler`: explicit pointer, owner-bound join, including expired/revoked state for honest display; never chooses by timestamp |
| Approved current identity | `currentApprovedLicenseForRecycler`: selected + VALID + reviewer/time present. Expiry/PDF/scope are additional checks, not implied by this read |
| Pending initial submission | `profileLicenseForRecycler` uses latest initial submission only while registration is PENDING. Existing initial Admin stale-ID locks/order remain unchanged |
| Pending renewal/replacement | Existing legacy request read is owner-filtered and audit-only. No endpoint/approval engine/table was introduced. It cannot alter current selection |
| Rejected submissions/history | `licenseHistoryForRecycler` returns recorded licence rows including rejected states. Rejected initial Admin details remain available; none is selected by timestamp |
| Historical revisions | `licenseRevisionHistoryForRecycler` reads existing owner-linked legacy before-images only. Fresh schema without that workflow returns no revisions; archived versions cannot authorize |
| Legacy VALID without selection/review metadata | Not a current approval. No migration inference or fabricated review history. Original data remains available to audit reads |
| Missing selection infrastructure | Selected read returns no candidate; dashboard does not fall back to MAX expiry. This is fail-closed, not an automatic schema upgrade |

GreenLoop is not bootstrapped by this migration. Its original registration, categories, activities and unlinked licence are preserved. An authorized, auditable legacy identity/evidence assessment remains necessary before operational authorization. Do not use a direct pointer UPDATE as a substitute for reviewing authentic evidence. Pointer alone does not satisfy review metadata or canonical scope.

Same-number renewal/version semantics remain deferred: global licence-number uniqueness remains unchanged. Legacy `document_version` and its status-sensitive trigger are preserved, not reinterpreted. No Phase 2B renewal engine has been implemented.

## Scope authority and legacy compatibility

`recycler_license_scopes` is the only store read by the new operational scope check. No evidence/coverage fallback and no automatic VERIFIED import exist. Existing initial review continues to use this same scope store with PENDING peer decisions allowed during that initial context; the operational method requires approved peers.

Legacy evidence, coverage, coverage reviews, update requests, revision snapshots and their FKs remain intact. Owner-filtered revision/request reads do not confer authorization. Scope infrastructure absence still differs from an installed table with no verified records in the Profile. Missing selected identity is not converted into a scope approval.

This checker is a reusable foundation, **not** an E-Lot policy engine: its caller must supply the complete independently reviewed activity set and take the documented transaction/owner/licence/policy locks before using it for a mutation. It does not infer required activities or claim to serialize external filesystem writes. Initial review locking/concurrency remains covered by the existing Admin tests.

## Migration and recovery instructions

Nothing below has been executed on the main development database.

1. Pause authorization/review writes; capture consistent DB/private-file backups and SHOW CREATE definitions. Compare deployed metadata against the tested baseline, especially names/types/column order of composite keys, FK references and CHECK expressions. Review invalid scope rows and current identity ambiguities; do not repair them automatically.
2. For an existing database, apply `20261116_recycler_initial_scope.sql` if needed, then `20261119_recycler_authorization_foundation.sql`, first on a disposable clone. For a **new empty** database, load current `database/schema.sql`; it already incorporates both migrations. Never load the full schema on top of the legacy development database or replay all older review-column ALTERs blindly.
3. The new migration adds no legacy grants, selections, approval history, E-Lot requirements or triggers. Validate canonical scope/FK/index metadata and compare historical row contents and trigger definitions before restoring writes.
4. Replay was tested, but IF NOT EXISTS is not a substitute for preflight: same-named objects with different definitions require explicit investigation. Invalid pre-existing scope evidence fails hardening without row deletion. MariaDB DDL auto-commits, so earlier additive columns/indexes may remain after failure; correct the cause through a reviewed plan and resume, do not delete history to force success.
5. Do not backfill `current_license_id` by MAX(submitted_at) or registration VERIFIED. Existing reviewed identities need an explicit authorized bootstrap decision; ambiguous/unreviewed cases stay unselected. Fresh successful initial approvals populate it atomically after existing prerequisites pass.
6. Prefer forward repair under a write pause. Do not roll back by dropping pointer columns/scopes, deleting decisions or restoring NULL document references. Any full-backup restore needs explicit approval and reconciliation of subsequent writes. A matching previous app/database combination must remain write-gated if it cannot enforce current scope. Legacy coverage triggers must not be disabled to make bids pass.

Fresh/upgraded parity here covers this foundation. Upgraded DBs intentionally retain extra legacy metadata/triggers; the future bid milestone must converge their operative predicates before claiming end-to-end parity.

## Validation actually executed

A metadata-only `mysqldump --no-data --skip-lock-tables` of the local legacy database was imported into an isolated MariaDB instance under `/tmp`; no private records or PDFs were copied. Synthetic records then populated historical evidence/coverage/reviews, a pending replacement request and an archived revision, including rejected/uncertain/revoked history. The same test ran on a fresh repository schema. Both tests created random `ecolot_test_foundation_*` databases and dropped their own databases afterwards; synthetic PDF files were cleaned up.

| Check | Actual result |
|---|---|
| Fresh vs metadata-faithful upgraded foundation suite | **65 assertions PASS**, both variants |
| Migration replay | PASS; no licence/history mutations or automatic selections/scopes |
| Legacy row and trigger preservation | PASS; populated history unchanged and trigger bodies/names identical |
| Missing/unreviewed approval, missing PDF, changed valid PDF bytes, old scope on replacement, expired selection | PASS; operational checks deny |
| Category/activity/high-risk/account restrictions | PASS; missing/empty activity set, pending category/activity, rejected scope, inactive account and absent high-risk capability deny |
| Pending/rejected newest documents | PASS; selected approval remains authoritative, history stays readable |
| Owner FK and evidence CHECK enforcement | PASS; foreign-owner selection and invalid evidence rejected |
| Invalid existing scope during hardening | PASS; migration fails and preserves decision rows |
| Actual demo licence seed block replay | PASS; pre-existing synthetic authentic fields unchanged, including legacy version behavior |
| Existing Admin initial approval / scope / OTP / authorization / HTTP/PDF / stale/concurrent review suite | **106 assertions PASS** |
| Existing complete regression runner | **PASS** |
| Profile current-state/escaping tests | **36 assertions PASS** |
| Dashboard presentation tests | **53 assertions PASS** |
| PHP lint | **158 files PASS** |
| JavaScript syntax | **21 files PASS**; no JS changes |
| `git diff --check` | Clean |

Fresh differs only in having no legacy request/revision tables, where audit reads return empty lists. Upgraded audit reads retain their synthetic pending/history rows. Legacy document-version updates and old bid guards were intentionally not standardized in this task. No new browser screenshots were required for the unchanged layout; this milestone validated data/authorization contracts, not a visual redesign.

Repeat the targeted suite against an isolated server with `ECOLOT_FOUNDATION_TEST=1`, `ECOLOT_LEGACY_SCHEMA` pointing to its metadata export, and test-only DB connection variables. Run `tests/recycler-authorization-foundation.php`, existing opt-in `tests/admin-recycler-verification.php`, `tests/recycler-profile-ui.php`, `tests/recycler-dashboard-ui.php`, and `tests/run-all.php`. Never point these experiments at the main development database.

## Targeted separate E-Lot integration specification

No file in this section was modified here.

1. **Application eligibility:** change only Recycler-specific `ELot::eligibilitySql/eligibilityParameters/recyclerSelect/isEligible` and `RecyclerBidService::placeBid/changeBid/lockEligibility`, preserving amount, ownership, CSRF, duplicate/winner/state and window restrictions. Replace any-VALID licence inference with selected reviewed identity. Join canonical scope to that identity and exact snapshot. Require ACTIVE account, VERIFIED registration, active APPROVED category, nonempty reviewed lot requirements and **every** required APPROVED activity covered by matching VERIFIED scope. HIGH in either applied or actual recorded risk requires both explicit capability and scope risk permission. Use the new checker for private PDF/hash validation at mutation time, after locks; deny missing policy/infrastructure/evidence.
2. **Concurrency:** retain lot-first mutation serialization; coordinate owner/account/licence/capability/scope/activity/requirement/risk locks with Admin updates. Requirements edits take the lot lock; authorization edits take the owner lock. Re-read current selection and hash after waiting. Establish bounded deadlock retry rather than permissive fallbacks. Withdrawal keeps its existing owner/window/state policy.
3. **Database:** define matching canonical INSERT and amount-revision guards in the fresh schema and a separate guarded migration. Preserve core bid invariants. On upgrades retain legacy guard protection until the canonical definitions have been installed/verified under a write pause, then reconcile their definitions without retaining a permanent dual-store policy. SQL checks relational identity, not filesystem existence or current bytes; private PDF validation remains an application responsibility. Do not drop old tables/history or disable a guard just to allow a bid.
4. **Required-activity producer:** reuse the legacy lot-requirement representation after explicit ownership agreement. Missing/empty/unreviewed policy denies; no default activities or automatic policy backfill. Adding this schema/producer is outside this foundation and has not been done.
5. **Award boundary:** `OfficerELot::eligibilitySql/eligible/act` must eventually recheck current authorization when selecting a new winner, without requiring an open bidding window. **Explicit teammate approval required.** Existing awards/handovers remain historical; do not retroactively cancel or rewrite them. Collector lifecycle/input changes likewise require explicit approval; the shared ELot file must not be broadly rewritten.
6. **Acceptance:** run fresh/upgraded create/revision tests plus direct-DML guards, applied-only/recorded-only HIGH, exact window boundaries/NULL times, stale/current document, policy/account/permission races, lost authorization with existing bids/awards, migration replay and rollback recovery. Compare listing eligibility with actual mutation outcome. Passing this foundation suite does not prove those bid guards.

## Remaining decisions and conclusion

- Authorized bootstrap of legacy current identities and authentic evidence remains unresolved; no existing account was auto-classified.
- Same-number renewal/version/archive semantics and the VERIFIED-owner evidence-review context need separate workflow authorization. Existing pending requests/revisions must not be discarded when that work starts.
- The required-activity producer, bid-guard migration and award-time authorization owner must agree on the cross-module implementation. Existing local coverage guards still consult legacy tables and can block bids even if canonical scopes are populated; fresh bid guards remain weaker until the follow-up.
- The foundation provides a tested canonical identity/scope read and validation contract for that next milestone. **Proceed to separate E-Lot integration after ownership approval; do not begin Phase 2B or treat this as completed bid reconciliation.**
