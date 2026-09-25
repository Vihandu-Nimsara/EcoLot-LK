# Collector Collection Record CRUD — implementation report

## Final workflow and architecture decision

Assigned Requests → Record Collection → Save Draft → View / Edit / Delete → Submit Schedule for Verification → read-only.

The schema has a unique `schedule_collections.schedule_id`, and each collection record requires a schedule collection parent. Verification, the submitting Collector, and the submission timestamp belong to that parent. Therefore this implementation uses the requested **option B: schedule-level submission**, rather than creating a submission for each request. Individual records inherit DRAFT/SUBMITTED from their schedule collection. The database's existing PENDING verification state is displayed as SUBMITTED; it means awaiting Municipal Officer verification, not verified.

The first draft creates one DRAFT parent for the schedule. Further records reuse it. Every active request must be eligible and have a valid saved record before submission. Submission atomically changes the parent to PENDING, sets submitted_at, and changes the schedule to COLLECTION_SUBMITTED. Original public requests are never edited. Request/item uniqueness constraints remain intact. Deleting a draft permanently deletes its item rows through the existing cascading foreign key; the empty schedule draft container can be reused.

Only ASSIGNED/IN_PROGRESS schedules permit collection mutations. Requests must be SUBMITTED or APPROVED and have NOT_REQUIRED or APPROVED risk review. Pending/rejected risk review does not grant permission to collect. Cancelled/rejected requests can be viewed but cannot receive records and are excluded from submission completeness.

## Database changes and installation

Migration: `database/migrations/20260925_collector_collection_record_crud.sql`.

- Extend schedule_collections.verification_status with DRAFT.
- Allow submitted_at to be NULL for an unsubmitted draft.
- Update the verification audit CHECK constraint to require no submission or verification timestamps for DRAFT, and a submission timestamp for PENDING.
- Preserve the existing PENDING default for compatibility with existing insert callers; Collector CRUD explicitly creates DRAFT with a NULL timestamp.
- No new tables, duplicated item fields, per-record ownership columns, or record status columns.

This was necessary because the original mandatory parent represented an already-submitted schedule, with no draft state and an immediate submission timestamp. schema.sql and the migration agree. The migration was applied and verified on the configured local application database. For another existing installation, select its EcoLot database and import the migration before using this feature. Fresh installations use the updated schema.sql. Keep the previous schedule-date uniqueness migration where relevant.

Existing VERIFIED/REJECTED outcomes and officer audit information remain unchanged and read-only. There was no implemented backend correction/reopening lifecycle to preserve; no such lifecycle was invented.

## Routes and CRUD mapping

Routes below are relative to the existing application base path, normally `/EcoLot-LK/public`. All methods belong to CollectorController.

| Operation | Exact UI action | Method and route | Controller method |
|---|---|---|---|
| CREATE | Record Collection → Save Draft | POST /collector/collection-records | storeRecord |
| READ | View | GET /collector/collection-records/{id} | showRecord |
| UPDATE | Edit → Save Changes | POST /collector/collection-records/{id}/update | updateRecord |
| DELETE | Delete → confirm | POST /collector/collection-records/{id}/delete | deleteRecord |
| Submit | Submit Schedule for Verification → confirm | POST /collector/schedules/{id}/submit | submitSchedule |
| List | Assigned Requests | GET /collector/my-requests | myRequests |
| Original request / new form | Record Collection or View Request | GET /collector/my-requests/{id} | showRequest |

The six added routes are request details, record create/read/update/delete, and schedule submission. Existing dashboard and initial-request entry points lead into the real assigned-request workspace. The existing sidebar Assigned Requests link remains valid. There is deliberately no per-record submit endpoint because verification applies to the whole schedule.

## Validation and security

- Auth::requireRole('COLLECTOR') on every endpoint, with Auth::id() supplying ownership.
- Every mutation checks the session CSRF token before accessing the model. Invalid tokens return HTTP 403.
- IDs are positive integers; unknown/foreign GET targets return HTTP 404. Mutation failures use the existing flash-message and redirect convention without persisting changes.
- Reads and writes require a current schedule_assignments row with unassigned_at IS NULL. Parent ownership must match the current Collector; former assignments do not retain access.
- Schedule and assignment rows are locked inside PDO transactions before mutation. Create/update/delete/submission serialize on the schedule; existing unique keys also prevent duplicate records and schedule parents.
- Browser-supplied Collector IDs, statuses, parent IDs and request fields are not accepted as writable attributes. Update derives the request ID from the owned record.
- Every request item must appear exactly once and belong to the target request. Quantities must be non-negative integers no greater than the original quantity.
- Weights use the existing DECIMAL(10,3) limits. Collected items require positive weight and WORKING/DAMAGED/UNKNOWN condition. Uncollected items require zero/blank weight and no condition, matching the existing CHECK constraint.
- Item results are derived from quantities. The allowed pickup result must agree with the item results. Notes are limited to the actual VARCHAR(500) size.
- Actual risk is calculated from the active condition-specific risk rule, falling back to the catalogue's existing default. No browser-provided risk is trusted; existing defaults are not a new safety assessment.
- PDO prepared statements, explicit writable attributes, transactional item replacement, HTML escaping, and confirmation dialogs.
- Submitted/verified/rejected records have disabled controls and no mutation buttons. Backend checks independently reject changes.
- Collector workspace JavaScript now only handles confirmations. No localStorage, fabricated requests, browser-generated ownership, or frontend status persistence remains in that workflow.

## Tests actually executed

Final result: PASS. The complete regression suite, both browser workflows, syntax checks and diff whitespace checks passed.

Final suite command:

```sh
PLAYWRIGHT_MODULE=/Users/nimsara/.cache/codex-runtimes/codex-primary-runtime/dependencies/node/node_modules/playwright \
php -d session.save_path=/tmp tests/run-all.php --browser
```

The module path is this workstation's bundled Playwright installation. Elsewhere, use an installed Playwright module or set PLAYWRIGHT_MODULE, and set CHROME_PATH/PHP_BINARY if needed. Test fixtures live only in disposable databases and are removed afterward.

- PHP syntax checks across all app, route and test PHP files.
- Existing authentication/session/CSRF/logout/role routing/validation/rate limit/rendering tests.
- Existing OTP transaction/lock-order test (PDO test double; not a MySQL concurrency test).
- Officer schedule CRUD, date/capacity/status/foreign-key/CSRF/escaping tests.
- Collector MySQL/controller tests: own list/read, foreign request/record denial, malformed IDs, create/read/update/delete, cascade deletion, duplicate prevention, forged parent/owner/status values, quantity/weight/condition/notes/item validation, missing draft submission, valid schedule submission, submission timestamp/state, backend update/delete/re-submit blocks, original-request preservation, revoked assignment, VERIFIED/REJECTED preservation, role guards, escaping, migration preservation.
- Existing public pickup CRUD/page tests, and real simultaneous last-slot capacity submissions.
- Existing catalogue seed, seed compatibility, and schedule-index migration tests.
- Existing JavaScript escaping tests.
- Real Chrome Officer/Public User CRUD browser regression.
- Real Chrome Collector login/navigation/create/reload/edit/reload/delete/recreate/schedule-submit/read-only/forged request/CSRF/server-side delete denial checks, no localStorage use, and no browser JavaScript or PHP warning/fatal messages.
- Desktop draft and mobile submitted screenshots visually inspected.
- git diff --check; route-to-method and schema-column review; changed files reopened for inspection.

The first full regression exposed an existing Officer bug: `$today` was undefined and past request cutoff dates were accepted. A narrow date validation repair was made. The existing browser fixture also collided with the October campaign already present in seed.sql; its test-only campaign setup now reuses that month. Collector browser test harness issues were corrected before the successful final run. These initial failed runs are not counted as passing tests.

## Limitations and scope

- Officer collection assignment and verification screens remain frontend-only in the supplied repository. This work reads actual schedule_assignments and persists submissions for later backend verification; it does not implement Officer assignment/verification CRUD. No operational demo assignments were inserted into the application database.
- Submitted state is inherited from the schedule parent, not an independent per-record state. All active requests need valid drafts before the shared submission.
- Drafts must already satisfy existing item CHECK constraints; incomplete quantities/positive-weight collections cannot be saved as partially filled drafts.
- There is no Collector correction/reopen action after an Officer rejection. Existing rejection status and notes remain visible and unchanged.
- Reassignment of a schedule with an existing parent owned by another Collector does not transfer ownership; the new Collector cannot access that parent's records. A future Officer reassignment workflow must resolve existing work explicitly.
- No new concurrent Collector race test was added; the existing Public User concurrency test ran. Collector serialization is implemented with schedule/assignment row locks and database uniqueness constraints.
- Full ZIP contains project source/assets/docs/tests, not a live database dump, Git history, local credentials, runtime logs, uploads, or generated ZIPs. Changes-only ZIP uses repository-relative paths for overlaying the existing project.

## Modified / added files
- `app/Controllers/CollectorController.php`
- `app/Controllers/MunicipalOfficerController.php`
- `app/Models/CollectionRecord.php`
- `app/Models/CollectionRecordItem.php`
- `app/Models/ScheduleCollection.php`
- `app/Views/collector/assigned-requests.php`
- `app/Views/collector/collection-record.php`
- `database/migrations/20260925_collector_collection_record_crud.sql`
- `database/schema.sql`
- `docs/collector-collection-record-crud.md`
- `public/assets/css/collector/collection-workflow.css`
- `public/assets/js/collector/collector-workspace.js`
- `routes/web.php`
- `tests/browser-fixture.php`
- `tests/browser-router.php`
- `tests/collector-browser.cjs`
- `tests/collector-crud.php`
- `tests/run-all.php`
