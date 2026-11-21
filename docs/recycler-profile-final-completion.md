# Recycler Profile Management — final completion

Date: 2026-11-20. Branch: `feature/admin-recycler-clean-rebuild`.

**Verdict: FEATURE COMPLETE WITH EXTERNAL DEPENDENCIES.** The assigned Profile/Admin workflows pass targeted fresh/upgraded tests on MySQL 8.0.46 and MariaDB 10.11.19. They are ready for owner acceptance and teammate integration review. This is not certification of bidding/Officer workflows or automatic deployment to genuine data.

## Final scope and retained implementation

Retained Authorized Recycler owner data and approved two-column Profile; contact Save/Cancel; password-bound phone OTP changes; current selected licence and protected PDFs; renewal/replacement requests; independent document-linked scope; capability ADD/UPDATE/DEACTIVATE; related Admin review, reasons/history and initial-verification integration.

Authentication, current account/owner checks, CSRF, OTP expiry/limits/replay prevention, atomic decisions, immutable request metadata/history, PDF/hash/version validation, pending-state separation and stale/concurrent request protection remain. Registration and original OTP source files were not changed. The old draft Profile JavaScript remains deleted because real server forms replace it; other roles' assets are untouched.

Shared dependencies intentionally retained: existing Admin layout/buttons and small Admin mobile-header correction; role routes limited to Profile/Admin actions; selected-licence/owner FKs and scope tables; existing SMS transport with fake delivery in tests; database replay helper; read-only obligation checks/lot-first locking inside capability changes. Existing bids/awards/handovers are consulted to prevent conflicting permission reductions, never rewritten by this feature.

## Recoverability and exact cleanup

Before edits, tracked/staged/untracked source plus index metadata were snapshotted outside the repository:

`/tmp/ecolot-profile-cleanup-backup-20261120-112842-b56c82`

The archive/manifest exclude environment secrets, private documents, storage/runtime files, dependencies and database credentials configuration. Source archive, SHA-256 manifest, original status/index entries and index snapshot permit reviewed recovery; nothing was overwritten. Do not blindly restore the index after later user work. No blanket reset, git clean, branch switch or commit was used. Previously staged paths were updated selectively so staged content does not retain removed out-of-scope work.

These confirmed branch-introduced files were restored byte-for-byte to `dev`:

```text
README.md
docker-compose.yml
app/Models/ELot.php
app/Models/RecyclerBid.php
app/Services/RecyclerBidService.php
database/demo/recycler-bids-fixture.php
tests/recycler-bids-integration.php
tests/recycler-reporting.php
tests/seed-compatibility.php
app/Views/admin/dashboard.php
app/Views/admin/layouts/main.php
```

These confirmed branch-created files were removed (recoverable from the snapshot):

```text
app/Services/ELotRequirementReviewService.php
app/Views/admin/e-lot-requirements.php
public/assets/css/admin/e-lot-requirements.css
tests/admin-elot-requirements.php
tests/installation-compatibility.php
tests/recycler-elot-authorization-boundary.php
database/migrations/20261119_recycler_bid_authorization.sql
database/migrations/20261119_recycler_bid_guard_cutover.sql
database/migrations/20261119_mariadb_legacy_fk_compatibility.sql
docs/admin-recycler-cross-module-remediation-plan.md
docs/admin-recycler-final-acceptance-audit.md
docs/admin-recycler-installation-remediation.md
docs/admin-recycler-stage2-admin-implementation.md
docs/pending/officer-elot-authorization.patch
docs/recycler-elot-authorization-integration.md
docs/recycler-authorization-reconciliation-plan.md
```

Mixed files were separated: AdminController and routes lost only requirements-authoring endpoints; schema lost only requirements tables/canonical bid functions/bid-guard changes while retaining Profile tables, owner/current-licence constraints and Profile history guards. Original dev bid/Officer/Collector behavior and Docker/README configuration remain. Seed keeps **only two licence duplicate-key no-ops** that protect genuine existing document references/identity; all conditional bid/deployment edits were removed. Original unrelated bid tests and demo fixture are restored, including any pre-existing limitations; they were not investigated or fixed.

Owned renewal/capability tests now use `tests/support/RecyclerProfileFixture.php`, not our reverted bid fixture. Four bidding-specific renewal assertions were removed/replaced with direct current-scope and unchanged-bid-amount assertions. All Profile/security/history checks remain. Capability tests keep only the narrow existing-obligation race through the unchanged dev bid interface. Historical feature milestone reports are retained with a supersession notice; out-of-scope reports/patches are removed.

## Database requirements and compatibility

Shared Docker/README are back to original dev **MySQL 8** behavior. No new engine, service, volume or deployment setting remains. Actual feature tests pass on **MySQL 8.0.46** and **MariaDB 10.11.19**.

Feature-owned conditional ALTERs now use information_schema checks and prepared DDL supported by both engines. Profile history guards use standard DROP IF EXISTS/CREATE TRIGGER with the same denial expressions, rather than MariaDB-only CREATE OR REPLACE. Scope evidence CHECK uses a case-sensitive character collation instead of REGEXP BINARY. Tests accept the exact corresponding MySQL/MariaDB CHECK error codes without weakening assertions. Renewal owner FK uses restrictive update/delete actions because MySQL disallows cascading base columns of stored generated reservation keys; this also preserves request history. No authorization CHECK, uniqueness or owner binding was removed.

Fresh empty installs use current schema, which contains the Profile structures. Do not replay all historical migrations over it or import demo seed over genuine data. Reviewed upgrades require existing initial-review columns, then:

1. `20261116_recycler_initial_scope.sql`
2. `20261119_recycler_authorization_foundation.sql`
3. `20261119_recycler_contact_phone.sql`
4. `20261119_recycler_license_renewal.sql`
5. `20261119_recycler_capability_changes.sql`

Phone storage is independent; the order above covers the complete feature. Preflight same-named objects/constraints and conflicting active reservations. DDL autocommits; pause writes and use a fail-fast deployment client. Trigger replacement must occur under that maintenance pause. Back up database, matching application and private evidence together; rehearse restoration and prefer reviewed forward repair after partial failure. Never delete history or disable security to force success. No bidding migration/cutover is required or supplied by this feature.

Disposable upgraded tests use original dev schema plus synthetic pre-feature Profile/archive tables: correct owner index, legacy document-version field, restrictive generated-key FKs and schema-unique CHECK names. This preserves each constraint's expression and historical fields while making the fixture portable. It is not proof that arbitrary raw MariaDB dumps import unchanged into MySQL, nor permission to convert real records automatically. Existing legacy selections/scope remain unset until authentic Admin review; migrations do not fabricate documents or VERIFIED scope.

## Functional/UI and actual targeted tests

| Executed check | MySQL 8.0.46 | MariaDB 10.11.19 |
|---|---|---|
| Fresh feature schema creation | PASS | PASS |
| Registration/session/security (`tests/run.php`), OTP locking | PASS | PASS |
| Initial Admin review / scope / CSRF / protected access | 106 PASS | 106 PASS |
| Licence selection/scope, migration replay/history/seed-reference protection | 65 PASS | 65 PASS |
| Contact/password/phone OTP, uniqueness, replay, concurrency | 91 PASS | 91 PASS |
| Renewal/independent scope/history/stale review/concurrency, fresh/upgraded | 128 PASS | 128 PASS |
| Capability lifecycle/snapshots/obligations/concurrency, fresh/upgraded | 166 PASS | 166 PASS |
| Profile data/permissions/escaping presentation | 69 PASS | 69 PASS |

Real HTTP/Chrome checks at **375px and 1280px**: capability 76 PASS; renewal 59 PASS; contact/phone 37 PASS. Forms, state badges, primary/secondary buttons, pending-vs-approved information, reviewer history, protected PDFs, success/error feedback and Admin panels passed. Screenshots were visually inspected; desktop remains two columns and mobile is readable without page overflow. No layout redesign was made. Fake SMS and synthetic PDFs only.

Evidence/screenshots: `/tmp/ecolot-profile-final/`, with `mysql-results.txt`, `maria-results.txt`, engine-specific logs and `capability/`, `renewal/`, `phone/` PNGs. PHP lint: **166 files PASS**; relevant Recycler/Admin JavaScript syntax: **2 files PASS**. Final working/staged diff whitespace checks are clean. Other teams' feature-specific regression suites were not run to investigate their defects.

Intermediate compatibility failures (conditional DDL, generated-key cascade restrictions, legacy fixture indexes/CHECK names and engine-specific CHECK codes) were corrected and rerun. A stale removed-bid-migration reference in the owned renewal test was removed, and migration-file loading now fails explicitly if a file is unavailable. Final feature test logs contain no PHP warnings/fatal errors.

## Remaining acceptance/deployment dependencies

- Owner should accept the real company/evidence flows and fake-tested SMS configuration before genuine use; no real SMS was sent.
- Main database was not migrated. Apply only the five Profile migrations through an approved backup/preflight/maintenance process. Legacy VERIFIED records without selected current evidence need an audited reconciliation; never auto-select or grant scope.
- Database/role-route/Admin shared-layout changes need teammate review. Restrictive owner FKs intentionally block deletion of accounts with retained request history; account-deletion policy must respect that history.
- Bidding/Officer/requirements enforcement is explicitly external and remains original dev behavior. This feature provides authoritative licence/scope information and protects its own mutations; it makes no cross-module enforcement claim. Existing interfaces are preserved and obligation smoke checks pass.
- Full real-data upgrade/restore, production credentials/permissions and owner acceptance remain external checks. No unresolved independently fixable defect was found in the assigned workflows after the final targeted runs.

## Final complete feature inventory against dev

`A` = created, `M` = modified, `D` = deleted. This includes the new report and Profile-owned fixture; no out-of-scope staged payload remains.

```text
M	app/Controllers/AdminController.php
M	app/Controllers/RecyclerController.php
M	app/Models/AuthorizedRecycler.php
M	app/Models/RecyclerAuthorizedActivity.php
M	app/Services/RecyclerApprovalService.php
M	app/Services/RecyclerAuthorizedActivityReviewService.php
A	app/Services/RecyclerCapabilityChangeService.php
M	app/Services/RecyclerCapabilityReviewService.php
A	app/Services/RecyclerLicenseRenewalService.php
M	app/Services/RecyclerLicenseReviewService.php
A	app/Services/RecyclerProfileService.php
M	app/Services/SmsGatewayService.php
M	app/Views/admin/recycler-details.php
M	app/Views/admin/recycler-verification.php
M	app/Views/recycler/profile.php
A	database/migrations/20261116_recycler_initial_scope.sql
A	database/migrations/20261119_recycler_authorization_foundation.sql
A	database/migrations/20261119_recycler_capability_changes.sql
A	database/migrations/20261119_recycler_contact_phone.sql
A	database/migrations/20261119_recycler_license_renewal.sql
M	database/schema.sql
M	database/seed.sql
A	docs/recycler-authorization-foundation-implementation.md
A	docs/recycler-capability-change-management.md
A	docs/recycler-contact-phone-management.md
A	docs/recycler-license-renewal.md
A	docs/recycler-profile-final-completion.md
M	public/assets/css/admin/recycler-details.css
M	public/assets/css/admin/recycler-verification.css
M	public/assets/css/admin/theme.css
M	public/assets/css/recycler/profile.css
D	public/assets/js/recycler/profile.js
M	routes/web.php
A	tests/admin-recycler-verification.php
A	tests/recycler-authorization-foundation.php
A	tests/recycler-capability-changes.php
A	tests/recycler-contact-phone.php
A	tests/recycler-license-renewal.php
M	tests/recycler-profile-ui.php
A	tests/support/RecyclerProfileFixture.php
M	tests/support/TestDatabase.php
```

Final cleanup: both local preview servers were stopped, their exact disposable databases and referenced synthetic PDFs/fake OTP capture were removed, and only the two task-created ephemeral database containers were removed. Screenshots/test logs and the recoverable source/index snapshot remain outside the repository. The required new fixture/report are staged with the existing feature so a later owner commit does not omit them. Final staged and working-tree whitespace checks pass; no commit was created.
