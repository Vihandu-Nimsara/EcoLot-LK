# Phase 2B-2 — Licence renewal

> Historical milestone evidence. Current feature scope, MySQL/MariaDB compatibility and acceptance results: [final completion](recycler-profile-final-completion.md). Prior engine-specific deployment instructions are superseded.
## Design and migration implications (before implementation)

Reuse the existing legacy recycler_license_update_requests and recycler_license_revisions contracts. Fresh installations receive matching tables; upgrades add nullable immutable uploaded-document SHA-256, baseline identity and baseline PDF SHA-256, explanation and archived PDF SHA-256. Existing requests are not backfilled: legacy pending requests without a submission fingerprint can be rejected with a reason but cannot be approved. Preserve all legacy records, constraints and the existing document_version trigger unchanged.

Global licence-number uniqueness is retained. Same-number renewal archives the complete selected row before updating that row within one transaction. Different-number replacements follow the same selected-row revision contract; an owner without a selection receives a new explicitly reviewed row. No inferred selection or automatic scope grant occurs. An unselected legacy row with the requested globally unique number is an explicit reconciliation blocker, not silently adopted. Request ID, immutable document path/hash and archived revision ID identify renewal versions independently of the optional legacy status-sensitive document_version counter.

New PDFs are created exclusively under the existing private storage directory, validated through the existing validator, SHA-256 bound to the request and made read-only. No existing file is overwritten. File hashes are checked on review and protected access; approved scope still uses the existing canonical exact document identity. Privileged filesystem administrators can replace files, so filesystem write permissions remain a deployment boundary; changed bytes deny access/authorization rather than being trusted.

Apply nothing to the main database automatically. Rehearse migrations against disposable fresh and metadata-faithful upgraded databases first. DDL auto-commits; compare SHOW CREATE definitions and back up DB/private files before a later manual deployment. IF NOT EXISTS does not repair incompatible legacy definitions. Preserve additive columns/history during rollback and prefer forward repair; do not delete documents or decisions.

## Lifecycle, security and authorization

- Submission requires the authenticated owner, ACTIVE account, verified mobile and VERIFIED registration, checked again under the existing user row lock. Validate number, real calendar date/expiry, bounded explanation, PHP upload provenance, PDF extension, 5 MB limit and the existing MIME/signature/EOF validator. Do not retain unsuccessful uploads. Safe text inputs survive validation errors; files must be selected again.
- One pending request per owner is enforced by the reused generated unique index and owner lock. A pending request cannot modify the approved row, selected pointer, original PDF, capabilities, activities or scopes.
- Each new upload uses exclusive file creation with a random filename, then private read-only mode and a stored SHA-256. A failed exclusive create never deletes an existing file. Existing project root rewrite rules deny access outside public/; deployment must retain that rule or use public/ as document root. Owner/Admin document routes keep owner predicates, MIME/nosniff/private-cache headers and hash checks; no storage paths are rendered.
- A PDF already present as the selected document or canonical scope evidence for that licence is rejected as a duplicate, rather than altering the existing scope uniqueness contract. Renewal requires updated evidence; this is not a workflow for re-uploading an identical historical PDF.
- Admin approval requires an authenticated ACTIVE Administrator and locks the owner before the selected licence and request. Recheck owner/mobile/registration status, unchanged baseline row and baseline PDF identity, current selected pointer, pending state, unexpired requested licence and exact uploaded bytes. Unique licence-number collisions roll back the archive and replacement together. Rejections require a meaningful reason, preserve the authoritative row and remain available for suspended owners without granting permission.
- Requests with submission hashes have immutable submitted metadata. The additive `trg_recycler_renewal_submission_bu` trigger also prevents completed decisions from being rewritten or reopened. It does not change any registration, initial-review, legacy-version or bid trigger. Optional previous_document_version retains a deployed legacy counter in the archive without changing its behavior; fresh installations identify versions by request/revision ID and immutable path/hash.
- Same-number renewal and different-number replacement archive the full prior licence metadata and PDF identity before updating the selected row. Missing original PDFs are recorded as missing, never fabricated. A missing selection is not inferred from the newest legacy row. Existing global number uniqueness remains authoritative.
- New selection/document metadata cannot match old canonical scope snapshots. Scope rows retain their historical status and evidence, but old document paths/hashes grant no coverage to the new version. Listing, bid creation and amount-revision enforcement remain in the unchanged canonical ELot/RecyclerBidService implementation. Existing bids, awards and handovers are not rewritten or cancelled.
- To close the renewal lifecycle, existing proposeScope/reviewScope methods now have an explicit post-renewal context. Their default initial-review behavior and existing routes remain unchanged. Separate renewal-scope endpoints require an approved current renewal and an ACTIVE VERIFIED owner; proposals must match existing APPROVED capabilities and activities. The same canonical table, evidence validation, independent inspection confirmation, notes and review methods are reused. A proposal alone grants nothing. Initial registration approval and capability/activity decisions are never reopened.
- Auth, CSRF, Session feedback, owner locking, AuthorizedRecycler selection reads, RecyclerLicenseDocumentValidator, RecyclerLicenseReviewService and existing layouts/buttons are reused. No additional eligibility engine, SMS workflow, modal framework, CSS framework or JavaScript handler was introduced.

## Manual deployment and recovery dependencies

1. Do not execute this runbook against development automatically. Pause licence/scope/bid writes, back up database and private files consistently, and inspect deployed SHOW CREATE TABLE/TRIGGER definitions. Compare existing request/revision columns, enums, generated index and FKs against the migration. Stop on incompatible definitions or duplicate pending rows; do not delete history to force a migration.
2. Foundation prerequisites are the existing initial scope migration and authorization foundation migration. Bidding integration belongs to its owning team and is outside this Profile feature. This renewal migration neither performs that cutover nor modifies those guards. Apply the contact/phone migration separately for its own feature.
3. Rehearse `20261119_recycler_license_renewal.sql` using a fail-fast client on a disposable clone. It creates compatible missing legacy tables, adds nullable fingerprints/explanation/archive metadata and installs the submission/decision guard. It does not update existing requests, licences, selected pointers, scope decisions or original files. Fresh **empty** databases use current schema.sql; never load the whole schema over a populated database.
4. After a later authorized manual migration, compare column/index/FK/trigger definitions, verify both positive and negative paths and resume writes only after all DDL succeeds. New runtime actions stay unavailable when required request/archive columns or the pending index are missing. Do not resume writes after partial DDL: schema readiness does not replace manual verification of the installed trigger. Application accounts need ordinary data privileges; they do not need TRIGGER/DDL privileges just to render the page.
5. Configure the existing private upload directory for the PHP service account. Keep public-only routing, restrictive directory access and the new read-only file permissions. PHP upload/post limits must accommodate the documented 5 MB upload plus form overhead. The reused validator checks MIME/signature/EOF; it is not a malware scanner or an independent verifier of the licence's contents. Administrator evidence inspection remains required.
6. DDL auto-commits; on failure keep writes paused, inspect the additive objects already installed and repair forward. Do not drop legacy tables, disable guards, rewrite fingerprints or restore old pointers blindly. An application rollback may leave additive storage intact; completed renewal decisions and archived PDFs must remain. Full backup recovery requires reconciliation of decisions/bids made after the backup.

Legacy pending requests without submission identity require rejection and a new owner submission. A legacy unselected row occupying the requested licence number requires an explicitly authorized identity reconciliation; this implementation deliberately does not adopt it automatically. Known separate Officer award-selection boundaries remain as documented in the authorization integration report; that module is unchanged.

## Exact task inventory

Task-start hashes distinguish these changes from the earlier uncommitted phases.

Modified existing files:

- `app/Controllers/RecyclerController.php` — owner submission, safe PRG feedback, current/request/archive protected PDF reads and Profile data.
- `app/Controllers/AdminController.php` — renewal review/PDF endpoints, current-document verification and explicit post-renewal scope endpoints.
- `app/Services/RecyclerLicenseReviewService.php` — optional post-renewal context around the existing canonical proposal/review methods; initial defaults unchanged.
- `app/Views/recycler/profile.php` — compact Update CEA Licence form, pending request and secondary history within the existing card.
- `app/Views/admin/recycler-details.php` — separate post-registration review, secondary historical decisions, matching scope controls and exact document-path comparison for current-scope presentation.
- `public/assets/css/recycler/profile.css` — three Profile-scoped renewal form rules.
- `routes/web.php` — owner/Admin renewal document/review and post-renewal scope routes.
- `database/schema.sql` — matching fresh-install renewal storage and immutability guard.
- `tests/recycler-profile-ui.php` — eight added renewal assertions; all previous assertions retained.

Created:

- `app/Services/RecyclerLicenseRenewalService.php` — focused request, review, revision and hash-bound document lifecycle.
- `database/migrations/20261119_recycler_license_renewal.sql` — additive compatible upgrade; no data backfill.
- `tests/recycler-license-renewal.php` — guarded fresh/upgraded integration, synthetic upload injection in CLI only, real review workers and local browser router. Web uploads use the real PHP upload check, never the CLI fake.
- `docs/recycler-license-renewal.md` — required lifecycle, ownership, migration/recovery and acceptance report.

No registration, OTP, initial approval service, contact/phone service, canonical bid model/service, shared Admin stylesheet, JavaScript, capability workflow, Officer award or handover source was changed. Other working-tree changes/deletions predate this task and were preserved. No commits, merges, pushes or branch switches occurred.

## Actual validation

All database writes used random disposable schemas on isolated MariaDB port 13374. Upgraded tests imported metadata only and populated synthetic records. No main database migration or record update occurred; no real SMS was sent.

- **132** fresh/upgraded renewal assertions PASS: validated submissions, invalid/missing/oversize PDFs, invalid/expired dates, duplicate pending requests and recorded PDFs, role/owner/account denial, protected owner/history reads, pending/current separation, changed bytes/baseline rejection, inactive reviewer denial, expiry at decision time, immutable submitted/terminal metadata, approval/rejection, repeated/concurrent decisions, same-number versions, late unique-number conflict/atomic archive rollback, legacy missing PDFs and fingerprint-free requests, exact history/version retention, no automatic scope inheritance, direct SQL/application bid-revision denial, independent canonical scope recovery and existing bid/award/completed-handover preservation.
- **59** real HTTP/browser assertions PASS: PHP multipart uploads, safe-field retry, owner/Admin CSRF failures, unauthorized roles, protected PDFs, real approval/rejection forms with accepted native confirmation dialogs, repeated-action feedback, current versus pending documents, no inherited scope, closed initial approval, autofocus, mobile navigation and no horizontal overflow.
- Existing canonical bid fresh/upgraded security and concurrency suite: **441 PASS**.
- Existing authorization foundation fresh/upgraded suite: **65 PASS**.
- Existing initial Admin review/scope/registration OTP/security/HTTP-PDF suite: **106 PASS**.
- Existing contact/phone security and concurrency suite: **91 PASS**.
- Existing bid integration: **213 PASS**; bid concurrency PASS; reporting/Profile/handovers: **104 PASS**.
- Complete `tests/run-all.php`: PASS (15 PHP suites plus JavaScript escaping).
- Profile view: **58 PASS** (all prior 50 plus 8 renewal assertions); Dashboard view: **53 PASS**.
- PHP lint: **160 application/routes/test files PASS**; JavaScript syntax: **21 files PASS**. No JavaScript changes.
- `git diff --check`: clean.

The final regression batch ran sequentially after a prior parallel-DDL run hit MariaDB's prepared-statement reprepare error. No assertion or production validation was weakened. The disposable expiry test uses a near-term supported server clock advance, rather than a timestamp beyond this server's supported range.

Repeat the focused suite with `ECOLOT_RENEWAL_TEST=1`, `ECOLOT_LEGACY_SCHEMA` pointing to the reviewed metadata-only export and isolated test DB connection variables. It creates/drops its own `ecolot_test_renewal_*` schemas. Its explicit KEEP option is only for local screenshot acceptance; retained synthetic schemas/files are cleaned up afterwards.

## Screenshots and remaining acceptance

Artifacts: `/tmp/ecolot-renewal/`. Desktop and mobile images were visually inspected during acceptance. These are synthetic accounts, not development data.

| State | 1280px | 375px |
|---|---|---|
| Renewal form | renewal-form-1280.png | renewal-form-375.png |
| Pending request | pending-1280.png | pending-375.png |
| Admin review | admin-review-1280.png | admin-review-375.png |
| Approval | approval-1280.png | approval-375.png |
| Rejection | rejection-1280.png | rejection-375.png |

Implementation is ready for owner review on disposable data. Deployment remains gated on the manual migration/prerequisite rehearsal, actual service-account file permissions/public-only routing and the explicit legacy reconciliation cases above. No capability change or licence-scope shortcut was introduced.

Cleanup completed: local browser server stopped; all task-specific disposable renewal schemas and their generated synthetic PDFs removed. Screenshots and result logs remain under `/tmp/ecolot-renewal/` for review.
