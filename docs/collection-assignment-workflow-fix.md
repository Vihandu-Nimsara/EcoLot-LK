# Collection assignment workflow implementation and audit

## A. Root cause

`CollectionRecord::saveDraft()` already validated items, saved the record/items and moved ASSIGNED to IN_PROGRESS inside one transaction. No current application caller bypassing that method was found. `ScheduleCollection::draft()` has one application caller: that save method.

A proven alternative writer is `database/seed.sql`: it explicitly set the September Wellawatte demo schedule to ASSIGNED and then inserted a DRAFT parent and one saved record directly. That produces the reported combination without calling the application transition. The seed now initializes that saved-draft schedule as IN_PROGRESS. This change is for future demo imports; the seed was never applied to the development database.

The actual origin of the user's SCH-4 remains unverified because the configured development MySQL endpoint refused connections. Legacy writes, manual edits and old code remain possibilities, not established causes.

A second proven defect was `CollectionRecord::deleteDraft()`: it removed only the child record. The empty parent remained, `hasCollectionSubmission()` stayed true and IN_PROGRESS never returned to ASSIGNED.

## B. Exact changes

| File | Method or area | Purpose |
|---|---|---|
| `app/Models/CollectionAssignment.php` | `listing()`, `change()` | Preserve draft counts/status; expose submission time; align readiness with risk clearance; enforce schedule creator's assignment authority. |
| `app/Models/CollectionRecord.php` | `requestSelect()`, `editable()`, `deleteDraft()` | Check record-to-parent/schedule consistency; reconcile after child deletion in the same transaction. Existing atomic first-save transition is retained. |
| `app/Models/ScheduleCollection.php` | `lockAssigned()`, `draft()`, new `removeEmptyDraft()` | Require pristine unsubmitted/unreviewed draft; require transaction and active ownership for draft creation/cleanup; protect audited parents; remove only empty editable parent and restore ASSIGNED. |
| `app/Views/municipal_officer/collection-assignments.php` | Lock explanations and modal | Singular/plural counts, explicit empty/inconsistent/rejected/pending/verified reasons; Unassigned confirmation wording; disabled no-JS unassign button. |
| `public/assets/js/municipal_officer/collection-assignments.js` | Existing modal | Feature checks, explicit focus restoration and duplicate-submit guard; preserve search and original CSRF POST. |
| `database/seed.sql` | Wellawatte draft fixture | Correct saved-draft schedule status for future imports. |
| `tools/diagnose-collection.php` | New read-only CLI | Schedule, all assignment history, collection/review timestamps, saved records, cross-schedule links, lot links, audit rows and actual incoming FK inventory. |
| `tests/collection-assignments.php` | Existing integration test | Ownership, no requests, state/history, cleanup-to-unassign, historical empty-container recovery and diagnostic execution. |
| `tests/collector-crud.php` | Existing integration test | Last/non-last deletion, audit protection, injected write/delete rollback and real Officer verification. |
| `tests/collection-assignment-view.php` | New render regression | All aliases/raw data-status values, count grammar, lock reasons, eligibility, escaping and disabled no-JS fallback. |
| `tests/collection-lifecycle-concurrency.php` | New MySQL/process regression | Simultaneous assign/assign, save/unassign and delete/submit using a held schedule lock as the barrier. |
| `tests/officer-browser.cjs` | Existing Chrome test | Current labels, modal cancellation/Escape, one confirmed POST, search and persisted result. |
| `tests/seed-compatibility.php` | Existing seed test | Reject seeded nonempty drafts outside IN_PROGRESS. |
| `tests/run-all.php` | Test registration | Include new view and lifecycle race tests. |

No ENUM, route, controller, CSS, E-Lot, Recycler, Feedback or area-schedule CRUD changes. The working tree was clean before implementation.

## C. Implemented lifecycle

```text
CLOSED → assign collector and vehicle → ASSIGNED
ASSIGNED → first successful saveDraft() → IN_PROGRESS
IN_PROGRESS → further save/update or non-final deletion → IN_PROGRESS
IN_PROGRESS → submitSchedule() → COLLECTION_SUBMITTED + PENDING
COLLECTION_SUBMITTED → decide(VERIFIED) → COMPLETED + VERIFIED
COLLECTION_SUBMITTED → decide(REJECTED) → COLLECTION_SUBMITTED + REJECTED
```

REJECTED remains read-only to the Collector under existing rules. There is no implemented Collector rework/reopen route; follow-up remains an Officer concern.

```text
IN_PROGRESS + DRAFT → delete final record → remove empty pristine parent → ASSIGNED
ASSIGNED with active assignment and no parent → normal Officer unassign → CLOSED
```

Resources remain assigned after final-record deletion. Assignment history is never deleted. If other records remain, the parent remains. Audited or reviewed containers cannot be cleaned up. An existing inconsistent ASSIGNED draft can be explicitly edited/deleted by its authorized Collector; ordinary save restores IN_PROGRESS, and final deletion retains ASSIGNED. Page loads do not repair data.

## D. UI

Collection Assignments alone aliases CLOSED as **Unassigned**, ASSIGNED as **Assigned**, IN_PROGRESS as **In Progress**, COLLECTION_SUBMITTED as **Submitted for Review**, and COMPLETED as **Completed**. Raw database values stay in `data-status`. Area Schedules retains its existing raw labels. PLANNED, OPEN and CANCELLED remain excluded from this listing.

Draft messages distinguish an empty container from one/many saved records and flag a nonempty draft whose schedule is outside IN_PROGRESS (or any draft on an incompatible schedule). Other messages distinguish pending review, verification, rejection and completion. CLOSED eligibility still depends on requests, risk clearance, existing work and active assignment; resource conflicts are checked again during assignment.

The existing native HTML dialog remains. Initial click opens it without POST; Keep assignment/Escape cancel; confirmation submits the existing CSRF form once. Native dialog focus containment is retained and focus returns to the trigger. Without JavaScript/dialog/requestSubmit support, the button stays disabled. Search remains unchanged.

## E. Security, integrity and concurrency

Controllers retain authentication, role checks and CSRF validation. Model ownership is rechecked under the schedule lock; draft ownership requires the active Collector and matching parent. Assignment changes now follow the same creator-based Officer boundary used for collection review. Prepared statements, output escaping and safe controller error handling remain.

Assignment, save, delete, submit and review serialize first on the schedule row. Collector operations then check assignment and relevant request/container state. Existing request and schedule checks run again after the lock. Reacquiring an already-held schedule lock in helpers adds no new cross-schedule lock order. Race tests fail on SQL errors/deadlocks rather than treating them as expected rejection.

The schema provides one collection parent per schedule and one record per request. Final cleanup runs in the deletion transaction. Removing records cascades to actual items; E-Lot item references use ON DELETE RESTRICT, so dependent lot data prevents deletion and rolls back. Empty parent cleanup checks generic audit references as well. `hasCollectionSubmission()` and `has_work` continue protecting every persisted parent, including DRAFT. No history-protection predicate was weakened.

## F. Verification

All database-mutating runs used an isolated local `mysql:8.0` container with no host data volumes, published only on loopback port 33316. Every integration test created/dropped its own disposable database. The empty base database schema was initialized for read-only smoke tests.

Commands (prefix all database commands with `DB_PORT=33316 DB_DATABASE=ecolot_workflow_test`):

- **PASS:** `php -d session.save_path=/tmp tests/run-all.php` — existing full PHP suite, new view/race tests, seed repeatability and JS escaping.
- **PASS:** `PLAYWRIGHT_MODULE=/tmp/ecolot-workflow-browser/node_modules/playwright node tests/officer-browser.cjs` — real Chrome, Officer workflow, modal, CSRF, search, persistence, responsive layout and no page errors.
- **PASS:** `PLAYWRIGHT_MODULE=/tmp/ecolot-workflow-browser/node_modules/playwright node tests/collector-browser.cjs` — real Chrome, create/update/delete/recreate, submission, read-only protection, foreign resource denial, CSRF and mobile rendering.
- **PASS:** diagnostic CLI exercised against disposable Collector fixtures and through assignment regression; an initially ambiguous metadata-query column was corrected and rerun successfully.
- **PASS:** PHP lint on all modified/new PHP, Node syntax on modified JS/CJS, and `git diff --check`.

Injected item-insert failure leaves no parent/record or status transition. Injected parent-delete failure restores child/items and IN_PROGRESS. An audited parent also rolls back final-child deletion. Actual `decide(VERIFIED)` is tested to produce COMPLETED. Submitted, verified and rejected data remain protected. Race tests exercise one controlled contention run per pair; this is not an exhaustive deadlock/load proof.

**NOT TESTED:** live SCH-4 consistency or repair; production/custom-schema differences; exhaustive stress/load testing; unrelated E-Lot business workflows. The existing full-suite OTP locking check uses a PDO double, not concurrent MySQL OTP requests.

## G. Historical diagnostic and guarded reconciliation

Run against the intended database explicitly:

```sh
php tools/diagnose-collection.php 4
```

This command uses a read-only transaction and performs no writes. Repeating it is safe. Inspect schedule state, active assignment count/owner, parent status and timestamps, request_schedule_id versus schedule_id, saved-record count, downstream lots, audit rows and the FK inventory. A saved DRAFT means persisted work regardless of the schedule label.

Do not rerun the demo seed to repair historical data. Do not delete SCH-4's saved draft or bypass unassign protection.

For a nonempty ASSIGNED/DRAFT mismatch, first review diagnostic output and approve the specific repair. In a maintenance transaction, lock the schedule first, then active assignment, parent and records. Recheck exactly one active assignment, matching Collector ownership, DRAFT with NULL submission/review timestamps and no verification note/history, all record requests belonging to the schedule, and no incompatible downstream state. Only then conditionally update that schedule from ASSIGNED to IN_PROGRESS, verify one affected row, write an audit entry and commit. If it is already IN_PROGRESS, treat it as an idempotent no-op; if any other precondition changed, roll back. No historical repair was executed in this delivery.

For an approved historical **empty** draft, use `ScheduleCollection::removeEmptyDraft(scheduleId, activeCollectorId)` inside an explicit transaction after locking/reviewing the schedule. It validates active ownership and pristine editable state, refuses audit history, counts records and removes only an empty container. It returns false for absent/nonempty parents. It restores IN_PROGRESS to ASSIGNED without unassigning resources. Inspect changed rows before committing; then use the normal Officer unassign action if desired. This is an operator maintenance procedure, not an automatic page-load repair or a new public endpoint.

## H. Sinhala-English code-checker learning notes

- **CLOSED → Unassigned:** Database eke CLOSED kiyanne intake close wela kiyala. Assignments page eke resources assign karanna balana nisa display label eka Unassigned. DB value eka change wenne na; Area Schedules eke CLOSED thiyenawa (`collection-assignments.php`).
- **ASSIGNED vs IN_PROGRESS:** ASSIGNED = collector/vehicle allocate karala. First valid record eka save kalama IN_PROGRESS = collection work persist wela (`CollectionRecord::saveDraft()`).
- **DRAFT vs PENDING:** DRAFT collector ta edit/delete karanna puluwan, eligible unsubmitted state ekak. PENDING = Officer review ekata submit karala; Collector edit/delete lock (`submitSchedule()`, `lockAssigned()`).
- **Assignment lock:** Saved draft/submission thiyenakota collector maru karoth existing records wala ownership/history break wenna puluwan. `CollectionAssignment::change()` saha `hasCollectionSubmission()` backend eken block karanawa.
- **First draft:** Validation pass unoth parent + record + actual items save karala schedule IN_PROGRESS karanawa. Failure ekak unoth transaction rollback (`saveDraft()`).
- **Final draft delete:** Records thawath thiyenawanam IN_PROGRESS. Last record nam pristine empty parent remove karala ASSIGNED karanawa; collector/vehicle assign wela thiyenawa (`deleteDraft()`, `removeEmptyDraft()`). History/downstream data thiyenawanam cleanup refuse/rollback.
- **Transactions:** Half-save/half-delete state ekak avoid karanna related writes okkoma commit wenna one; error nam okkoma rollback. Row locks concurrent requests serialize karanawa.
- **Unassign bypass karanna ba:** Button hide karana eka data protection ekak newei. Forged POST ekak awath backend rules apply wenna one. Draft ignore karala unlock kaloth collection ownership damage wenna puluwan (`change()`).
- **Frontend + backend:** JS search/modal user interaction handle karanawa. Form eke original CSRF token submit wenawa. Controller auth/role/CSRF check karala model transaction/ownership/state validation karanawa. Refresh eke pennanne persisted database state.
