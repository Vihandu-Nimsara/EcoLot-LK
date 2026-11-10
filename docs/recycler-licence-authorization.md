# Recycler licence coverage authorization

## Deployment and existing accounts

Apply **only** `database/migrations/20261110_recycler_licence_coverage.sql` after the already-applied capability migrations. Do not rerun or edit the historical capability migrations. Fresh installations use `database/schema.sql` instead. This task does not apply the migration to `ecolot_lk`.

The migration creates no verified scopes and changes no capability, bid, award or handover data. After deployment, existing accounts need an evidence review before new bids or expanded permissions can be accepted. Unconfigured E-Lots also fail closed. Plan an Administrator review window before enabling bidding; this is an intentional operational change.

For each existing Recycler, open Admin → Recycler Verification → Recycler Details:

1. Review the current protected licence PDF, number and expiry. A valid overall licence does not establish any category/activity scope.
2. Record a pending coverage proposal for each supported category/activity pair. Record the actual waste description, optional document code, evidence page/clause, authorization basis, conditions and explicit HIGH-risk permission where supported.
3. Open the exact evidence document and explicitly verify, reject or record uncertainty with rationale. A pending, uncertain or rejected proposal grants nothing.
4. Review existing category and activity approvals against that evidence. Historic approvals remain recorded; they are not proof of legal scope.
5. In the existing details page, record each E-Lot's actual required handling operations with rationale. The application has no universal activity requirement, so it does not assume RECYCLING for every lot.
6. Resolve displayed existing bid/award conflicts with the responsible Officer. The feature never cancels awards, deletes bids or rewrites handovers automatically.

New documents do not always mean a new licence is legally required. Existing appropriate evidence may support a new capability after explicit review. The UI permits an EPL/SWML/supporting-authorization bundle in the existing protected PDF upload; the existing 5 MB limit remains.

## Regulatory context

CEA states that Scheduled Waste licensing is required in addition to an EPL where applicable, and describes separate waste-management operations. These sources inform evidence collection; they do not supply an automatic mapping from EcoLot categories or its HIGH risk flag to legal waste codes:

- [Environmental Protection Licensing](https://cea.lk/web/environmental-protection-licensing)
- [CEA Waste Management Unit](https://www.cea.lk/web/en/component/content/category/15-wmu)
- [Downloadable applications](https://www.cea.lk/web/en/downloadable-applications)

EPL alone is rejected as the basis for HIGH-risk scope. SWML, combined evidence and other explicitly described authorization still require human verification. No licence authenticity or legal authorization is inferred from a filename, licence number or successful upload. Administrators record evidence; EcoLot does not issue CEA authorizations.

## Database design

`recycler_licenses.document_version` is controlled by a BEFORE UPDATE trigger. A change to path, number, expiry or status increments it. A caller cannot reset or manually advance the version. This covers same-row renewals, replacements, expiry changes and revocation/restoration. A different-number replacement retains the existing superseded-licence and revision history.

`recycler_license_evidence` records an immutable application snapshot: owning Recycler, licence ID/version, number, expiry, protected path, SHA-256, capturing Admin and timestamp. The composite licence/owner foreign key prevents cross-account evidence. The unique licence/version key prevents two contradictory snapshots. Snapshot capture alone grants no scope. If the bytes change without a new revision, further verification and application bidding fail closed.

`recycler_license_coverage` records a category/activity pair against one evidence snapshot, explicit risk permission, evidence basis, document description/code, supporting reference and conditions. Proposal and review identities/times/notes remain recorded. Status is PENDING, VERIFIED, REJECTED, UNCERTAIN or REVOKED. Generated nullable slots and unique indexes permit one pending and one effective verified scope per evidence/category/activity while retaining history. A new verified proposal supersedes the prior verified scope transactionally; rejected or uncertain proposals do not displace existing verified scope.

`recycler_license_coverage_reviews` stores decision history, including revocation and supersession. No service deletes coverage, snapshots or review history. Foreign keys restrict deletion of referenced records, consistent with preserving existing regulatory history.

`e_lot_authorization_requirements` holds a versioned Admin review of required operations. `e_lot_required_activities` is its current normalized projection. `e_lot_authorization_reviews` retains every reviewed operation set and rationale. Optimistic versions reject stale/replayed lot forms. Replacing the projection does not delete the audit history or affect existing bids.

No stored functions or system-database upgrade are required. The installed server has an existing `mysql.proc` metadata mismatch for stored routines; the implementation avoids that dependency and uses ordinary tables, triggers and prepared queries, tested on MariaDB 10.4.32.

## Application rules and transactions

`RecyclerLicenceAuthorization` supplies the common category/activity and E-Lot predicates. SQL expressions are internal identifiers, never user input. The same generated E-Lot predicate is embedded in the migration and fresh-install bid triggers; a test checks exact equality.

Current evidence means the latest submitted VALID licence (ID breaks timestamp ties), unexpired, with the exact version/path/number/expiry snapshot. An expired newer VALID row cannot fall back to an older one. PHP checks the protected PDF's SHA-256 before granting approvals, enabling bid controls and submitting/revising bids. SQL cannot inspect filesystem bytes; trusted application services remain the mutation boundary.

Initial capability approval requires verified scope for that category, its risk permission and a requested activity. Initial activity approval requires verified scope intersecting a requested category. Final account approval rechecks all approved capabilities and activities, active/mobile-verified account state and the existing completed-review prerequisites. Partial supported subsets can be approved while unsupported claims are explicitly rejected; no Cartesian product of all approved categories and activities is implied. Each lot checks its precise required pairs.

Post-registration ADD, UPDATE and reactivation recheck current evidence and the original request snapshot inside the existing review transaction. DEACTIVATE keeps the existing unresolved-bid/unfinished-handover protections. Pending and rejected requests never alter current capabilities. Suspended/rejected/initially pending capabilities remain outside the reactivation workflow.

Initial licence approval and approved renewal/replacement capture a snapshot inside the licence-review transaction but do not verify coverage. Approved same-number renewals increment the version even when the licence row/number remains the same. Replacement uses a new source row and supersedes the old one. Old scopes remain historical. Pending or rejected updates preserve the old applicable scope.

New/revised bids require active verified account status, approved category capability, current valid evidence, approved activities, and current VERIFIED coverage for **every** operation required by the lot. HIGH risk in either the request or actual collection record requires both capability and coverage risk permission. Existing bid visibility and withdrawal remain available under the previous ownership/window rules even if new bidding becomes unauthorized.

Admin services validate the session actor and active database Administrator, CSRF at the controller, scalar/enum/length constraints and Recycler ownership. Protected source/evidence routes bind IDs to the Recycler; historical evidence also verifies its file hash. Private paths are not rendered as document URLs.

Lock order for authorization changes is Admin identity → Recycler user → Recycler profile → current licence → relevant scope/capability rows. Bid services lock lot → Recycler user/profile → licence/capability/risk sources before rereading scope. Required-operation changes lock the lot and never acquire Recycler locks. This serializes supported application bid submission against scope revocation, verification and document replacement. The added triggers independently reject predicate-invalid bid inserts/amount changes; they do not promise serialization for arbitrary privileged SQL clients that bypass application locking. Such clients must not be used as an application API.

All review projections and their audit events commit together; validation/duplicate/replay failures roll back. No external SMS call is introduced. Registration/password-reset OTP, phone-change transactions and session handling are unchanged.

## Verification

Run from the repository root, using the project's PHP binary where needed:

```sh
ECOLOT_COVERAGE_TEST=1 /opt/lampp/bin/php -d session.save_path=/tmp tests/recycler-licence-coverage.php
ECOLOT_CAPABILITY_TEST=1 /opt/lampp/bin/php -d session.save_path=/tmp tests/recycler-capability-changes.php
node --check public/assets/js/recycler/profile.js
git diff --check
```

The integration runners create randomly named, guarded disposable databases and remove only those databases. Evidence PDFs are synthetic, privately stored under unique fixture paths and removed afterward. The new migration is tested as an incremental upgrade as well as through the fresh schema. Historical migrations are not executed by the current runners. Phone regressions inject fake SMS senders.

Coverage tests exercise fail-closed states, review ownership/replay, initial approval, uncovered ADD/reactivation/risk expansion, source version transitions, risk/activity intersection, bid revision, protected documents, raw-insert rejection, existing-record preservation and an independent bidding connection waiting on owner serialization. Capability tests also exercise concurrent service reviews, stale snapshots, real licence review/update services, Contact Person editing and existing phone/session/CSRF regressions.

Browser checks use actual PHP views and existing local assets with synthetic in-memory data, with remote requests blocked. They exercise 1440/768/500/390 widths, registration, initial Admin verification, pending/verified coverage, expanded coverage/contact/phone/licence forms, capability-dialog focus/validation, independent card spacing and overflow. This verifies presentation, not real regulatory documents or a deployed production migration.

## Module completeness audit

| Area | Result and practical limit |
| --- | --- |
| Registration | Existing backend/OTP retained; licence labels/evidence guidance updated; rendered interface checked. Live registration/SMS not performed. |
| Initial verification | Scope-aware licence/capability/activity/final services integrated and tested. |
| Profile | Existing five cards and mobile order retained; responsive forms/dialogs checked. |
| Contact Person | Existing working form retained; controller regression tested. |
| Phone | Existing secure request/verify/resend/cancel and session flow retained; fake-SMS regression tested. |
| CEA Licence Management | Initial approval, renewal/replacement/rejection retained; versioned evidence integrated and tested. |
| Licence Coverage | Admin proposal/explicit decisions/revocation/audit, legacy review and protected evidence integrated and tested. Authenticity remains a human/regulatory decision. |
| Capability Management | Existing ADD/UPDATE/reactivate/deactivate/review/history retained; coverage gates and races tested. |
| Authorized Activities | Existing initial review retained and scope-gated. No unrelated post-registration activity request feature added. |
| Dashboard | Existing database-backed display retained; presentation and changed eligibility integration checked. |
| Eligible E-Lots | Central fail-closed scope/activity/risk checks integrated and tested; actual lot operations require Admin configuration. |
| My Bids | Existing ownership, revision and withdrawal retained; new authorization gate and record preservation tested. |
| Awarded E-Lots | Existing database-backed Recycler list/detail reads retained; winning bid/handover preservation tested. |
| Award/handover end-to-end | **Partial existing upstream implementation:** Collector/Officer E-Lot/award interfaces use browser-local state; no production award mutation routes found. This task does not implement that unrelated backend. |
| Reports | **Intentionally deferred.** Recycler/Admin report pages and routes untouched. |

The old standalone bid integration/concurrency scripts depend on an outdated demo fixture with removed verification columns; they are not represented as passing. Current feature tests build real provenance against the present schema instead. No destructive production test, real licence authenticity check or live SMS was performed.

## Delivered files and actual results

Created in this task:

```text
app/Models/RecyclerLicenceCoverage.php
app/Services/RecyclerLicenceAuthorization.php
app/Services/RecyclerLicenceCoverageService.php
app/Views/admin/recycler-licence-coverage.php
database/migrations/20261110_recycler_licence_coverage.sql
docs/recycler-licence-authorization.md
tests/recycler-licence-coverage.php
tests/support/LicenceCoverageFixture.php
```

Modified relative to the starting working tree (which already had uncommitted capability work):

```text
app/Controllers/AdminController.php
app/Controllers/RecyclerController.php
app/Models/ELot.php
app/Models/RecyclerBid.php
app/Services/RecyclerApprovalService.php
app/Services/RecyclerAuthorizedActivityReviewService.php
app/Services/RecyclerBidService.php
app/Services/RecyclerCapabilityChangeReviewService.php
app/Services/RecyclerCapabilityReviewService.php
app/Services/RecyclerLicenseReviewService.php
app/Services/RecyclerLicenseUpdateReviewService.php
app/Views/admin/recycler-details.php
app/Views/auth/register-recycler.php
app/Views/recycler/profile.php
database/schema.sql
public/assets/css/admin/recycler-details.css
routes/web.php
tests/recycler-capability-changes.php
```

Actual final verification:

- 84 licence coverage/migration/approval/eligibility/security/concurrency assertions passed.
- 92 capability schema/submission/review/concurrency/eligibility assertions passed.
- Nested existing 322 phone-change and 57 profile assertions passed; fake SMS only. Profile assertions also passed after the final status-label refinement.
- 53 dashboard presentation assertions and existing session/CSRF/amount/mutation validation checks passed.
- 22 new/changed PHP files passed lint. Recycler profile and registration JavaScript passed syntax checks.
- All 16 browser cases passed: four interfaces at 1440/768/500/390 pixels, including expanded forms, modal behavior and no horizontal overflow.
- `git diff --check` and whitespace checks for untracked text files passed.
- Starting-file hash comparison confirmed the two historical capability migrations, profile CSS/JS, profile UI tests, shared shell/global styles and Reports files were preserved. Other baseline files outside the 18 listed modifications also remained unchanged.

No migration was applied to the application database. No real account/licence data was changed, no SMS was sent, and no commit or push was made.
