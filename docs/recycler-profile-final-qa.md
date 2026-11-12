# Recycler Profile final QA — 2026-11-12

**Assessment: CONDITIONAL for merge; ready for code review.** Scoped automated checks and browser layout/interaction checks pass. Final live-browser submission/PDF-response acceptance and real SMS delivery remain manual acceptance items. No merge, commit, branch switch, schema change or main-database mutation was performed.

## Baseline

- Branch: `feature/recycler-profile-management`.
- HEAD: `5c606495e641a1cb15d90de2eb8edd16025a08d7` (unchanged).
- Initial working tree: clean.
- Baseline `dev...HEAD`: 45 files, 6,919 insertions, 159 deletions. These existing changes include profile workflows, migrations, evidence-based authorization and tests; this task does not reimplement them.
- Inspected Auth/login, Recycler/Admin controllers, profile model/view/CSS/JS, phone/licence/capability services and review services, authorization predicates, private PDF validator, routes, schema/migrations and guarded test runners. No applicable AGENTS.md was found.

## Confirmed findings and fixes

1. **Suspended verification could pass login.** Recycler login explicitly denied PENDING/REJECTED but omitted SUSPENDED. Login now rejects suspended/rejected/missing Recycler membership and suspended/disabled accounts before registration verification setup. Verified ACTIVE accounts still sign in; legitimate unverified PENDING registration still redirects to the original OTP setup. No SMS gateway or registration OTP implementation was changed.
2. **Stale sessions could enter protected Recycler/Admin routes.** `Auth::requireRole()` trusted session roles. It now performs one indexed current-state query for ADMIN/RECYCLER only, checking database role, ACTIVE account and membership; Recycler verification must be VERIFIED. Denied sessions are destroyed and receive 403. Database check failures fail closed with 503. Successful checks are not cached across calls. Transactional services continue their locking checks for mutations. Collector, Municipal Officer and Public User retain their prior behavior; no global authentication refactor was made.
3. **Phone services did not recheck Recycler verification.** Start, completion and resend now reject non-VERIFIED owners, including suspension between sending a fake code and completing a change. The extra read uses the injected connection and locks the membership row inside transactions, following users → Recycler ordering.
4. **Licence submission service lacked an explicit session-owner check.** `submit()` now rejects a foreign owner/non-Recycler before validation/staging. Existing uploaded-file checks, private storage and review transactions remain intact.
5. **Contact writes lacked an atomic account/verification predicate.** The existing owner-qualified UPDATE now also requires ACTIVE/VERIFIED, preventing an update after authorization is revoked between controller entry and SQL execution.
6. **Phone/capability CSRF failures redirected instead of returning forbidden.** These profile actions now return 403 before invoking mutation/SMS services, matching contact/licence actions. Tests verify missing and invalid tokens and unchanged data.
7. **Logs could include raw exception details.** Recycler reads/contact updates and Admin licence, capability and activity review handlers now log exception class/category instead of SQL messages or full exception stacks.
8. **Profile feedback and mobile layout were ambiguous/cramped.** Success is green with `role=status`, errors red with `role=alert`, and review warnings amber. Feedback can receive focus after redirect. Capability rows stack with retained labels below 600px. Long request reasons use native disclosures without losing full text or review feedback. The modal trap excludes hidden controls, and dialog/textarea sizing supports narrow screens.
9. **An inherited test could terminate early with exit status zero.** Stricter authorization exposed the nonexistent cached user in bidding tests. Shared smoke/bidding tests now select genuinely authorized fixtures. The licence runner additionally requires the child suite's completion marker, preventing a false PASS after an authorization exit.

## Files and methods changed in this task

| File | Methods/area | Purpose |
|---|---|---|
| `app/Core/Auth.php` | new `currentAccountAllows()`; `requireRole()` | Scoped current DB-state authorization, revoked-session rejection, fail-closed DB errors |
| `app/Controllers/AuthController.php` | `submitLogin()` | Reject denied Recycler states before OTP setup; require verified status for login |
| `app/Controllers/RecyclerController.php` | `readPage()`, `requestCapabilityChange()`, `handlePhoneChange()`, `updateProfileContact()` | Sanitized logs and consistent forbidden CSRF responses |
| `app/Controllers/AdminController.php` | licence review/update, capability and authorized-activity review error handlers | Category-only logging |
| `app/Models/AuthorizedRecycler.php` | `updateContactPerson()` | Atomic ACTIVE/VERIFIED predicate |
| `app/Services/RecyclerPhoneChangeService.php` | `requireActiveRecycler()` | Verification recheck on start/verify/resend |
| `app/Services/RecyclerLicenseUpdateService.php` | `submit()` | Session role/owner binding |
| `app/Views/recycler/profile.php` | feedback, request reason and modal explanation | Accessible notice variants, expandable reasons, concise explanation |
| `public/assets/css/recycler/profile.css` | profile-only additions | Notice styles, mobile capability rows, narrow dialog/forms |
| `public/assets/js/recycler/profile.js` | feedback focus and Tab handling | Focus feedback and exclude hidden controls from modal trap |
| `tests/recycler-profile-security.php` | **new guarded suite**, subprocess route checks | Login/state revocation, ownership, contact validation, CSRF and private-document authorization |
| `tests/recycler-profile-ui.php` | render helper/assertions | Notice semantics/escaping, labels, disclosures and dialog invariants |
| `tests/recycler-phone-change.php` | fixture, CSRF and suspended verification cases | Genuine verified fixtures; block completion/resend after suspension |
| `tests/recycler-capability-changes.php` | CSRF assertions | Verify 403 and no inserted request |
| `tests/recycler-licence-coverage.php` | child regression runner | Require completion marker, not exit status alone |
| `tests/recycler-bids.php` | controller fixture selection | Exercise amount/ID/CSRF checks with a real authorized disposable actor |
| `tests/run.php` | Admin/Recycler dashboard smoke fixtures | Preserve role enforcement instead of fabricating an unauthorized user |
| `tests/run-all.php` | new `--recycler-profile` option | Run only scoped guarded profile regressions; default runner unchanged |
| `docs/recycler-profile-final-qa.md` | this report | Evidence, scope, limitations and review handoff |

No existing tests were deleted or disabled. Of the two new files, only `tests/recycler-profile-security.php` is a test; the other is this report. The existing dashboard UI and escaping tests were executed unchanged. Services for capability review, licence review/update review, licence coverage and the schema/migrations were reviewed but not rewritten.

## Executed checks

Baseline: profile UI **57**, phone **322**, capability **92**, licence coverage **84**, dashboard **53**, and escaping checks passed before edits. These are distinct from final results.

Final scoped runner:

```sh
/opt/lampp/bin/php -d session.save_path=/tmp tests/run-all.php --recycler-profile
```

**PASS**, including explicit child completion markers:

| Check | Final executed result |
|---|---|
| `tests/recycler-profile-ui.php` | PASS — 65 assertions |
| `tests/recycler-dashboard-ui.php` | PASS — 53 assertions |
| `tests/recycler-profile-security.php` | PASS — 119 in aggregate; subsequent focused run passed 121 after adding two pending-registration preservation assertions |
| `tests/recycler-phone-change.php` | PASS — 331 assertions; fake SMS only |
| `tests/recycler-capability-changes.php` | PASS — 92 assertions, including concurrency and unchanged/rejected permission states |
| `tests/recycler-licence-coverage.php` | PASS — 84 assertions, including incremental migration, approval, evidence, eligibility and concurrency |
| shared smoke, OTP PDO double, bidding amount/ID/CSRF | PASS with completion markers |
| `node tests/escaping.js` | PASS — HTML text/attribute escaping |

Individual guarded commands (also executed):

```sh
ECOLOT_PROFILE_SECURITY_TEST=1 /opt/lampp/bin/php -d session.save_path=/tmp tests/recycler-profile-security.php
ECOLOT_PHONE_TEST=1 /opt/lampp/bin/php -d session.save_path=/tmp tests/recycler-phone-change.php
ECOLOT_CAPABILITY_TEST=1 /opt/lampp/bin/php -d session.save_path=/tmp tests/recycler-capability-changes.php
ECOLOT_COVERAGE_TEST=1 /opt/lampp/bin/php -d session.save_path=/tmp tests/recycler-licence-coverage.php
```

PHP `-l` passed for all 16 changed/new PHP files. `node --check public/assets/js/recycler/profile.js` and `git diff --check` passed. The default cross-team `tests/run-all.php` suite was inspected but not executed against the main database; the scoped option uses disposable databases only.

The phone suite intentionally emits connection-retirement warnings during simulated failure tests. These are expected injected failures, not real SMS failures or application-database connection termination. All test-created databases were dropped in `finally`; a read-only SHOW DATABASES check found no remaining databases matching these four random test prefixes. Synthetic PDF files are removed by the fixture cleanup.

## Browser verification

Command: `python3 /tmp/ecolot-profile-final-browser.py` — **PASS, 41 assertions** at **375×812**, **768×1000** and **1440×1000**.

Used the actual PHP view/layout/CSS/JS rendered with synthetic in-memory data; no browser form POST, database mutation or SMS was triggered. Verified distinct notice colors, no page overflow, expanded contact/phone/licence forms, capability labels/stacking, full expanded reasons, modal width, background inert/scroll locking, hidden deactivation risk field, Tab wrapping, Escape/backdrop closing and focus restoration. Browser screenshots were visually inspected.

Temporary screenshots:

- `/tmp/ecolot-profile-final-375.png`
- `/tmp/ecolot-profile-final-768.png`
- `/tmp/ecolot-profile-final-1440.png`
- `/tmp/ecolot-profile-final-dialog-375.png` (also tablet/desktop variants)

## Read-only QA database observations

Observed on 2026-11-12; no assumptions were made from earlier session reports:

- ID 5: ACTIVE/VERIFIED; ID 12: ACTIVE/VERIFIED; ID 13: ACTIVE/REJECTED; IDs 14–16: ACTIVE/PENDING.
- Licence update request #6 belongs to ID 5 and is REJECTED, reviewed `2026-11-11 15:31:49`.
- Capability request #1 belongs to ID 5/category 2 and is REJECTED, reviewed `2026-11-11 15:46:37`.
- Capability request #2 (`QA DUPLICATE TEST`) belongs to ID 5/category 2 and is still PENDING.

**Cleanup recommendation, requiring explicit approval:** have an authorized Administrator inspect request #2 and decide whether it remains useful for duplicate-pending QA. If obsolete, reject through the existing review action with a clear QA note after confirming ownership and requested permission. Do not delete history, reset accounts, change approvals or manipulate the request directly in SQL. No cleanup is required merely because it is pending.

## Remaining limits and merge conditions

- Real SMS delivery/carrier behavior has not been acceptance-tested. Fake sender tests verify the lifecycle, rate limits, replay and failure handling; they are not SMS-delivery acceptance.
- Current browser tests cover rendered interaction, not a complete live HTTP login/upload/review flow. Earlier live checks supplied by the user are context only. Recheck valid PDF upload, protected response headers and review feedback in the intended XAMPP configuration before merge acceptance.
- CLI security tests exercise actual controller guards, service paths and synthetic private PDFs, including MIME/signature/EOF/size/path rejection. They do not simulate a browser-origin `is_uploaded_file()` success or an interrupted HTTP licence-upload COMMIT. The existing filesystem/database compensating-cleanup design is not a distributed transaction; crash/uncertain-COMMIT reconciliation remains a resilience limitation outside the verified ordinary transaction paths.
- Account revocation here means SUSPENDED/DISABLED or changed DB role/membership; `users.account_status` has no REVOKED value. A revoked **licence** blocks scope-based handling/bids but does not bar an otherwise verified account from its profile/renewal workflow.
- Collector, Municipal Officer and Public User stale-session behavior intentionally remains unchanged. Broader authentication policy work requires separate cross-role approval.
- Historical migrations are not uniformly rerunnable; use the documented order and a fresh disposable database when validating deployment. No main DB migration was attempted.
- No unresolved defect remains in the specific suspended-session, owner binding, CSRF, feedback and mobile interaction scenarios tested here. The acceptance/resilience limits above prevent an unconditional claim that every production failure mode is covered.

## Final workspace and presentation notes

The working tree started clean. This task modifies **17 tracked files** and adds **2 untracked files** (the security suite and this report): **19 files total**. There are no staged changes or deletions. HEAD is unchanged. `git diff --check` is clean; the prior `dev...HEAD` implementation remains intact.

For the university presentation: explain the distinction between a cached session and current database permission, show a suspended Recycler's POST and disabled Admin's PDF request returning 403, demonstrate that normal pending registration still enters the existing OTP setup, then show a rejected capability request preserving its original approved permission. Finally demonstrate stacked mobile rows, differently colored feedback and keyboard-safe modal closing. Run the scoped test command above to reproduce the evidence without touching university QA accounts.
