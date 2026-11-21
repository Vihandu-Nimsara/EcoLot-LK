# Phase 2B-1 — Recycler contact and phone management

> Historical milestone evidence. Current feature scope, MySQL/MariaDB compatibility and acceptance results: [final completion](recycler-profile-final-completion.md). Prior engine-specific deployment instructions are superseded.
## Minimum schema need (documented before implementation)

Registration OTP challenges are purpose-bound to registration/account activation and do not bind a proposed phone, current password state or authenticated session. They must remain unchanged.

Read-only SHOW CREATE inspection found an existing legacy `recycler_phone_change_challenges` table with owner, original/proposed number, password and session fingerprints, hashed OTP, attempts/expiry, delivery state and terminal timestamps. Adopt that same table definition for fresh installations; preserve existing challenge history on upgrades. Add one generated active proposed-number column and unique index to prevent simultaneous reservations of the same proposed number across accounts. Retain its existing one-active-challenge-per-owner constraint. No new parallel OTP table/purpose is introduced for registration.

The migration must fail for conflicting active legacy reservations rather than invalidate or delete them automatically. Expired/terminal challenges grant no permission. Apply only on disposable databases for this task; main development data/schema must remain untouched. Back up and compare existing definitions before any later manual deployment; MariaDB DDL auto-commits. No automatic migration or legacy approval/backfill.

## Implementation and ownership

Only authenticated ACTIVE, mobile-verified, VERIFIED Recyclers can edit. Existing `User::lockForVerification`, Auth/Session, CSRF, Validator, MobileNumber, LoginRateLimiter, audit_logs, shared Profile/layout/button styles, and the generic SMS transport are reused. One focused `RecyclerProfileService` owns these mutations; registration OTP state and activation services are untouched. No JavaScript framework or extra client handler is needed.

Contact saves update only users.full_name; no-ops create no audit event. Phone requests require the current password, normalize numbers, check global uniqueness, and store a password-hashed six-digit code with session and password-state fingerprints. The existing number remains active until a locked transaction rechecks account status, registration approval, current password fingerprint, original number, session, expiry, delivery and attempts, then updates users and consumes the challenge atomically. Successful phone verification rotates session ID and CSRF token. Contact saves refresh only the authenticated display name.

Challenges expire after five minutes; five wrong attempts consume the challenge. Resends replace the code and preserve the attempt budget. Issuance is limited to one per minute, five per hour and ten per day across owner/target. Password checks reuse the shared file-lock limiter with separate phone namespaces (ten/account, twenty/IP per fifteen minutes). Existing single-host limiter deployment assumptions remain unchanged. Network I/O holds no database transaction; pending database reservations prevent parallel sends. Delivery failure releases the reservation. All terminal rows remain as history. Responses, flash state and application logging never contain passwords or codes. The SMS adapter receives the raw code only for transport; tests inject fake senders.

## Manual deployment and recovery (not executed on development)

1. Back up the target schema and challenge history. Confirm MariaDB support for generated columns and `ADD ... IF NOT EXISTS`. Inspect `SHOW CREATE TABLE recycler_phone_change_challenges` against the migration; stop on incompatible definitions rather than forcing a replacement.
2. Inspect duplicate active proposed numbers using `SELECT proposed_mobile_number, COUNT(*) FROM recycler_phone_change_challenges WHERE verified_at IS NULL AND invalidated_at IS NULL GROUP BY proposed_mobile_number HAVING COUNT(*) > 1;`. Stop on conflicts; let challenges expire and reconcile only through an explicitly authorized procedure. Do not delete history or grant verification.
3. In a maintenance window, manually apply `database/migrations/20261119_recycler_contact_phone.sql` with a fail-fast client (no `--force`). Fresh schema and upgraded legacy tables receive the same constraints. The migration is repeatable and does not backfill or update account records.
4. Inspect both unique indexes and generated column definitions after application. Phone mutations and the Edit control fail closed until the owner and target reservation indexes are installed. Contact editing remains independent of the phone migration.
5. DDL auto-commits: a failed unique-index addition can leave the generated column installed. Preserve data, inspect the error, resolve only the explicit preflight conflict, and rerun the additive migration. Do not drop the legacy table or its constraints. Application rollback can leave the additive storage intact; restore only from a verified backup if necessary.

The main development database was not migrated or edited. Phone editing there remains unavailable until its owner schedules this migration. Live SMS delivery was intentionally not tested.

## Exact task inventory

Modified existing files (relative to the saved task-start hashes, independently of prior uncommitted phases):

- app/Controllers/RecyclerController.php — owner-only POST handlers, safe feedback/session refresh and read-only edit state.
- app/Services/SmsGatewayService.php — phone-purpose adapter for the existing generic SMS transport.
- app/Views/recycler/profile.php — compact contact/request/verification forms within the approved company card.
- public/assets/css/recycler/profile.css — Profile-scoped form and focus styling.
- routes/web.php — five POST endpoints under existing Recycler middleware/routing.
- database/schema.sql — matching additive phone challenge storage for fresh installations.
- tests/recycler-profile-ui.php — retain existing 36 assertions and add 14 editing/accessibility/read-only field assertions.

Created:

- app/Services/RecyclerProfileService.php — focused contact and secure phone lifecycle.
- database/migrations/20261119_recycler_contact_phone.sql — compatible adoption and active-target reservation index.
- tests/recycler-contact-phone.php — guarded disposable DB, fake SMS, migration, security and concurrent-process tests; includes an opt-in local HTTP router with injected fake sender.
- docs/recycler-contact-phone-management.md — required schema rationale, deployment/recovery and acceptance evidence.

Other dirty/deleted files shown by Git predate this task and were preserved. In particular, Admin, registration, initial OTP, canonical authorization, bids, renewal and capability workflows were not edited in this task. Existing automated tests were retained.

## Validation evidence

Commands use `/opt/lampp/bin/php -d session.save_path=/tmp`; isolated MariaDB runs on port 13374. The new suite requires `ECOLOT_PROFILE_EDIT_TEST=1`; it creates/drops random `ecolot_test_profile_edit_*` databases and uses injected fake SMS and private temporary limiter files. The explicit `ECOLOT_KEEP_PROFILE_TEST=1` option supports local browser acceptance only; retained browser databases are dropped after capture.

Existing suites passed: complete run-all.php (15 PHP suites plus JS escaping), initial Admin review 106 assertions, authorization foundation 65 fresh/upgraded assertions, canonical E-Lot boundary 441 fresh/upgraded assertions, bids integration 213 assertions, bid concurrency, reporting/Profile/handover 104 assertions, Profile view 50 assertions and Dashboard view 53 assertions.

Browser acceptance uses real local login, routes, forms and database mutations with an injected fake SMS sender. It verifies HTTP403 CSRF/foreign-role denial, owner-scoped contact saves despite foreign ID/company inputs, inline password/OTP errors, OTP consumption, old-number preservation, session rotation, old-CSRF revocation, editor autofocus, shared header, mobile navigation and no horizontal overflow. Screenshots cover contact, phone request and OTP verification at 375px and 1280px. Test harness and artifacts reside in `/tmp/ecolot-contact-phone/`; screenshots depict disposable synthetic accounts, not development records.

Final acceptance results: 91 contact/phone security/migration/concurrency assertions and 37 real HTTP/browser assertions passed. PHP lint passed for 158 application/routes/test files; JavaScript syntax passed for 21 files. Final changed PHP lint and `git diff --check` passed. Desktop/mobile screenshots were regenerated after the fail-closed migration guard was added and visually inspected during acceptance. No real SMS was sent. No commit, merge, push or branch switch occurred.
