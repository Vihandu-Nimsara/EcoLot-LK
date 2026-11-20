# Phase 2B-3 — Capability change management

> Historical milestone evidence. Current feature scope, MySQL/MariaDB compatibility and acceptance results: [final completion](recycler-profile-final-completion.md). Prior engine-specific deployment instructions are superseded.
## Design and minimum migration (before implementation)

Adopt the existing legacy recycler_capability_change_requests table, including ADD/UPDATE/DEACTIVATE, previous/requested risk, review audit and one pending request per owner/category. Fresh capability_status gains the existing legacy INACTIVE value; no historical rows are converted. Add nullable full capability-review snapshot JSON, approved-activity snapshot JSON and selected licence/document fingerprint. Legacy requests are not backfilled and cannot grant permission without these snapshots; they remain rejectable. A new trigger preserves submitted snapshots and completed decisions. Existing registration, initial review, renewal, canonical bid and Officer/handover contracts remain unchanged.

ADD requires an absent capability. UPDATE supports APPROVED risk changes and INACTIVE reactivation, as the established CHECK permits; no-op changes are rejected. DEACTIVATE retains the row as INACTIVE after approval. Pending requests never modify capability rows. Full before-images preserve the original approval/reviewer history when a live row is updated.

Approval uses the existing canonical requireScope(..., final=true) method against the proposed capability inside the same transaction; failed checks roll back that prospective write. This preserves the established category-review contract: matching current coverage for an existing APPROVED activity, with explicit risk coverage when requested. Per-E-Lot authorization continues to require every reviewed lot activity; a capability change cannot expand documentary scope to unrelated activities. There is no new eligibility engine. A request-specific entry into the existing documentary scope proposal/review engine is needed so an absent ADD capability can obtain independently reviewed coverage without pre-granting a live capability. Existing initial and renewal defaults remain unchanged; authorized activities cannot be edited here.

Reduction locks follow existing lot → owner → licence/capability order. Lock relevant existing lots first, then the owner, and re-read lot membership with READ COMMITTED; membership changes cause a bounded retry. New bids serialize on the same owner lock. Existing Officer award/handover methods already take the lot lock, so no teammate-owned source change is needed.

Existing SUBMITTED bids remain outstanding until withdrawn/rejected or their lot is cancelled/rejected, including closed bidding awaiting an award. WINNING bids with missing/PENDING/SCHEDULED handovers remain obligations. COMPLETED and CANCELLED handovers are final in OfficerELot::act and do not block reductions. DEACTIVATE checks all category obligations; high-risk reduction checks HIGH applied/actual source risk, treating missing source evidence as unproven safety. Historical rejected/withdrawn bids and completed obligations are retained and do not create blanket restrictions.

Apply no migration or record changes to the main database. Rehearse additive DDL on disposable fresh/upgraded databases; back up and compare existing definitions before later manual deployment. No legacy snapshot or scope grant is inferred.

## Existing components and ownership

The original RecyclerCapabilityReviewService remains unchanged and purpose-bound to initial registration. It cannot safely process VERIFIED accounts or preserve approved permissions during a new PENDING request. One focused RecyclerCapabilityChangeService therefore owns post-registration requests and decisions, while reusing User locking, Auth/Session/CSRF, AuthorizedRecycler selection/capability reads, RecyclerLicenseRenewalService::currentDocument and the existing canonical RecyclerLicenseReviewService. Existing Profile controls, Admin full-width forms, badges, disclosures and native confirmations are reused. No new JavaScript, modal framework, activity-editing endpoint or eligibility engine was added.

Read-only SHOW CREATE inspection of the main database confirmed that it already has the legacy request table and INACTIVE capability enum, without the three new snapshot columns. No main records were read or modified by the feature tests and no main migration was run. The implementation used the current source and metadata contract, not deleted-branch source code.

## Request lifecycle and authoritative decisions

1. The owner must have an ACTIVE RECYCLER account, VERIFIED registration and a verified mobile, rechecked inside the transaction. The POST has no owner identifier; Auth::id is authoritative. Category, operation, risk and a meaningful 5–500 character reason are validated server-side. Form text survives validation failures; form Cancel is read-only navigation.
2. ADD requires an absent category row. UPDATE supports APPROVED risk changes and INACTIVE reactivation. PENDING, REJECTED and SUSPENDED rows are not silently repurposed. DEACTIVATE requires an APPROVED row and preserves it as INACTIVE upon approval. These choices follow the existing legacy request CHECK contract; no-op approved-state updates are rejected.
3. Store the existing structured previous/requested values, full capability before-image (including prior reviewer, timestamp and note), ordered approved activities and selected licence/document identity. The licence identity includes selected record metadata and actual protected PDF SHA-256. The generated unique index prevents a second PENDING request for the same owner/category. No live capability row is created or changed at submission.
4. An authenticated ACTIVE Administrator supplies APPROVE or REJECT and meaningful feedback. Owner/account status, pending state, category and snapshots are checked under locks. Completed requests cannot be replayed or reopened. The new trigger also prevents submitted snapshots and completed decisions from being rewritten through SQL.
5. For ADD/UPDATE, require the same current approved, unexpired, protected licence and unchanged approved activity set. Apply the proposed capability inside the transaction, then invoke the existing canonical requireScope with final=true. It requires an active category, an APPROVED capability, an APPROVED activity and matching VERIFIED scope for the exact current licence number/expiry/path/hash; requested high-risk handling needs explicit high-risk evidence. Any denial rolls back the capability write and decision together.
6. Category approval follows the established category-review contract: verified coverage for an existing approved activity. It does not assert coverage for every globally approved activity. Unchanged E-Lot authorization still checks every activity in that lot's reviewed requirement set and its actual/applied HIGH risk. A capability change cannot manufacture missing lot policy or documentary coverage.
7. DEACTIVATE grants no permission and does not need a fresh licence/scope grant. It checks the capability before-image and live obligations. UPDATE risk reductions additionally retain the ADD/UPDATE licence and scope requirements. Existing permissions and review records remain unchanged on rejection; Admin rejection can resolve a request for a suspended owner without reactivating that account.
8. Every submission and decision records an audit_logs actor/action/request reference. The request retains the full prior approval even when the current capability's reviewer fields are updated by an approved change. No capability rows, historical decisions, bids, awards, handovers or original registration records are deleted.

## Canonical scope and renewal integration

The existing scope proposal/review methods gain an optional capability-request context; initial and renewal defaults remain unchanged. Dedicated Admin routes select that context. It requires the same eligible VERIFIED owner, pending trusted request, category, current document and approved activities. ADD evidence can be proposed without first inserting a live capability. Independent verification still requires the exact current document, active category, supporting evidence, reviewer feedback and explicit inspection confirmation. The canonical scope table remains the only documentary authority.

The existing evidence form is reused with a review-context selection when capability requests are pending. Selecting renewal preserves the existing approved-permission review path. Selecting a request binds the category to that request; mismatches fail server-side. No authorized activity is created or edited.

A PENDING licence renewal does not change the selected licence or invalidate current approved permissions. After renewal approval changes the selected document, an old ADD/UPDATE request fails its licence identity check even if a newer document later obtains verified scope. Admin must reject that stale request with feedback; the owner submits a fresh request for the current document. Old scopes remain historical and are never copied or silently reverified. The required renewal/request race is tested in both database variants. DEACTIVATE remains a non-granting reduction subject to live obligation checks.

## Bid, award, handover and concurrency handling

The implementation reads these modules but does not modify their source or policy. OfficerELot::act already takes the lot lock for awards and handovers; canonical bid mutations take lot then owner locks. Capability decisions take relevant owner/category lot IDs in numeric order, then the owner and licence/capability state. READ COMMITTED re-reads membership after the owner lock; a newly committed bid on a previously unseen lot causes a bounded retry rather than acquiring that lot in the reverse order. Deadlock/lock-timeout retries are bounded to three attempts.

Once the owner lock is held, a new canonical bid cannot commit until the capability decision finishes. Once an existing relevant lot lock is held, its award/handover changes cannot pass the decision unnoticed. Live obligation reads lock the relevant bid/handover rows. Independent-process tests prove that a new bid and deactivation cannot both succeed, while at least one legitimate action makes progress. Two concurrent reviewers produce exactly one successful decision.

| Record | Reduction treatment |
|---|---|
| SUBMITTED bid, including closed bidding awaiting award | Blocks while the lot is not CANCELLED/REJECTED |
| WINNING bid with no handover, PENDING or SCHEDULED handover | Blocks until an explicit final handover state |
| COMPLETED/CANCELLED handover | Final under existing Officer handling; retained without a blanket block |
| WITHDRAWN/REJECTED bid | Historical; retained without blocking |
| DEACTIVATE | Checks all relevant category obligations |
| Approved high-risk true → false | Checks HIGH applied or actual risk; missing source evidence cannot prove a safe reduction |
| Proven LOW obligations | Do not prohibit a high-risk-only reduction when current documentary requirements pass |

No bids, awards or handovers are cancelled, rewritten or silently released. The separately documented Officer canonical award-selection boundary remains outside this milestone. No teammate-owned source modification or new cross-module approval was necessary.

## Exact files changed and created

Inventory is relative to saved task-start hashes, not all earlier uncommitted work on this branch.

Modified existing files:

- `app/Controllers/RecyclerController.php` — owner submission, safe PRG feedback and Profile request/category data.
- `app/Controllers/AdminController.php` — post-registration review and request-bound scope entry points; expected scope validation is shown clearly while SQL failures remain generic.
- `app/Services/RecyclerLicenseReviewService.php` — optional request-bound documentary scope context; initial and renewal defaults and the canonical operational predicate remain unchanged.
- `app/Views/recycler/profile.php` — compact request form, pending indicator, actual versus proposed permissions and historical before-image disclosure.
- `app/Views/admin/recycler-details.php` — separate full-width reviews, current documentary coverage/conflicts, latest decision and one grouped history disclosure; existing evidence form reused.
- `public/assets/css/recycler/profile.css` — three scoped capability form/pending-panel rules.
- `routes/web.php` — owner request, Admin decision and request-bound evidence endpoints.
- `database/schema.sql` — matching fresh-install request storage, INACTIVE enum support and immutability guard.
- `tests/recycler-profile-ui.php` — eleven added editor assertions; all previous 58 assertions retained.

Created:

- `app/Services/RecyclerCapabilityChangeService.php` — focused request, snapshot, conflict, locking and decision lifecycle.
- `database/migrations/20261119_recycler_capability_changes.sql` — compatible legacy adoption, nullable snapshots and guard; no grant/backfill.
- `tests/recycler-capability-changes.php` — guarded disposable fresh/upgraded integration, independent workers, renewal/obligation cases and local real-UI router.
- `docs/recycler-capability-change-management.md` — required design, acceptance and deployment report.

Task-start checksums confirm no changes to initial approval/capability review, registration/OTP, contact/phone service, licence renewal service, canonical ELot/RecyclerBidService, OfficerELot, shared Admin CSS or JavaScript. Other dirty/deleted files predate this milestone and were preserved. Existing automated tests were not deleted or disabled.

## Actual validation results

All writes used isolated MariaDB port 13374 and randomly named disposable schemas. Upgraded databases imported metadata only, then synthetic records. The main database received only read-only schema inspection. No real SMS was sent; existing OTP regressions use their fakes.

| Suite | Actual result |
|---|---|
| Capability lifecycle/security/history/fresh-upgraded/concurrency | **166 assertions PASS** |
| Real HTTP/browser capability workflow | **76 assertions PASS** |
| Canonical bid fresh/upgraded security and concurrency | **441 PASS** |
| Authorization foundation fresh/upgraded | **65 PASS** |
| Initial Admin review/scope/registration OTP/security/HTTP | **106 PASS** |
| Contact/phone security/concurrency | **91 PASS** |
| Licence renewal fresh/upgraded/security/concurrency | **132 PASS** |
| Existing bid integration | **213 PASS** |
| Existing bid concurrency | PASS |
| Reporting/Profile/handover regression | **104 PASS** |
| Complete tests/run-all.php | PASS: 15 PHP suites plus JS escaping |
| Profile presentation | **69 PASS**, including all prior assertions |
| Dashboard presentation | **53 PASS** |
| PHP lint | **162 application/routes/test files PASS** |
| JavaScript syntax | **21 files PASS**; no JS changes |
| git diff --check | Clean |

The focused tests cover ADD/UPDATE/DEACTIVATE, no-op and duplicate pending requests, owner/role/account/mobile/registration denial, absent/rejected/current/high-risk scope, missing/changed protected PDFs, disabled reviewers, inactive categories, activity-state changes, prospective-write rollback, reductions with active/closed/winning bids, PENDING/SCHEDULED/COMPLETED handovers, LOW-only reductions, rejection/stale/repeated/concurrent decisions, immutable history, prior reviewer before-images, new bid versus deactivation safety and progress, pending and approved renewal races, old-scope denial and independently re-evaluated new scope.

Initial upgrade safety was tested with a populated legacy request and capability rows before adding columns: every original request field and capability row stayed unchanged and the three new snapshot fields remained NULL. Snapshot-free requests could not be approved. Populated migration replay preserved all request and capability contents in both variants.

The browser used real login, CSRF, owner isolation, native confirmation dialogs and form submissions. It exercised UPDATE approval/rejection, ADD, independent request-bound scope proposal/verification without a pre-granted capability, and DEACTIVATE. It checked actionable missing-scope feedback, foreign identity inputs, no-op retry values, current versus pending permissions, closed initial registration, autofocus, mobile navigation and no horizontal overflow. Screenshot fixtures are synthetic, not development accounts.

## Screenshots

Artifacts are in `/tmp/ecolot-capability/`; the desktop/mobile set was regenerated after grouping Admin history and visually inspected.

| State | 1280px | 375px |
|---|---|---|
| Request form | request-form-1280.png | request-form-375.png |
| Pending request | pending-1280.png | pending-375.png |
| Admin review | admin-review-1280.png | admin-review-375.png |
| Approval | approval-1280.png | approval-375.png |
| Rejection | rejection-1280.png | rejection-375.png |
| Capability history | history-1280.png | history-375.png |

## Manual migration and recovery instructions

1. Do not apply anything to development automatically. Schedule a write pause for registration/capability/scope/licence/bid review writers and take consistent database/private-document backups. Inspect current definitions and values, including all capability enum states, request CHECKs, pending unique index, FKs and any same-named trigger. Stop for incompatible definitions; IF NOT EXISTS does not repair them.
2. Confirm existing foundation/current-licence and canonical scope migrations are installed. Licence renewal storage is needed for the completed renewal workflow. Operational E-Lot enforcement belongs to its owning team and is not changed by this Profile feature. This migration does not change or disable any bid/award guard.
3. Check for conflicting pending owner/category pairs with `SELECT recycler_user_id,category_id,COUNT(*) FROM recycler_capability_change_requests WHERE request_status='PENDING' GROUP BY recycler_user_id,category_id HAVING COUNT(*)>1;`. Reconcile only through an explicitly approved procedure, never by deleting historical decisions.
4. Rehearse `database/migrations/20261119_recycler_capability_changes.sql` using a fail-fast MariaDB client on a disposable clone. For a fresh **empty** installation use current schema.sql, never the full schema over a populated database. The enum append may acquire a metadata/table lock; the three snapshot columns are nullable and existing request data is not backfilled.
5. After later authorized manual application, compare enum ordering, columns, CHECK/FK/index definitions and `trg_recycler_capability_request_bu`; verify both positive and negative paths before resuming writes. Runtime availability checks for the new columns/index do not replace manual verification that all DDL completed. Application users need normal data privileges, not DDL/TRIGGER privileges just to render pages.
6. MariaDB DDL auto-commits. On partial failure keep writes paused, inspect installed additive objects and repair forward. Do not drop tables, remove guards, relabel rejected/suspended permissions, fabricate snapshots, change old scope or restore pointers blindly. Rollback must preserve completed decisions, before-images and authentic documents; full backup recovery requires reconciliation of later decisions and bids.
7. Repeat the suite with `ECOLOT_CAPABILITY_CHANGE_TEST=1`, `ECOLOT_LEGACY_SCHEMA` pointing to the reviewed metadata-only export and isolated test connection variables. The test creates/drops its own `ecolot_test_capability_*` schemas. KEEP is only for local screenshot acceptance and its data/files are removed afterwards.

## Remaining owner acceptance

Implementation is ready for owner review on disposable data. Main capability actions remain unavailable until the additive migration and documented prerequisites are manually rehearsed/applied. Existing legacy requests with NULL trusted snapshots need rejection and a new submission. Reusing PENDING/REJECTED/SUSPENDED capability rows beyond the established ADD/UPDATE contract requires an explicit policy decision; this milestone does not bypass those states. The existing separate Officer canonical-policy boundary remains unchanged.

No production/development records or historical documents were reset. No commits, pushes, merges, resets or branch changes occurred. No unresolved implementation/test failure remains.

Cleanup completed: the local browser server was stopped, all task-specific disposable capability schemas and generated synthetic renewal PDFs were removed, and screenshots/result logs remain under `/tmp/ecolot-capability/` for owner review.
