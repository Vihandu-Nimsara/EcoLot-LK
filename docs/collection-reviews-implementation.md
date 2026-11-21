# Municipal Officer Collection Reviews implementation audit

## A. Initial findings

The existing feature was database driven, not a mock. `ScheduleCollection::forOfficer()`/`findForOfficer()` already excluded DRAFT and scoped submissions to `area_collection_schedules.created_by_officer_user_id`. The schema has no municipality-to-Officer mapping, so creator ownership is the existing resource boundary. Detail retrieval already grouped multiple request records and requested items. `decide()` already persisted Verify/Reject metadata in a transaction, checked completeness for verification, locked the schedule before the submission, and completed the schedule on verification.

Missing functionality included search, useful sorting, pagination, scoped counters, collected totals, clear Officer terminology and accessible custom review dialogs. The old JavaScript used `window.confirm()`. Stale decisions returned generic validation responses. The old detail query could conceal actual items linked to another request, even though a separate validation query rejected them. Historical requests marked COMPLETED could be treated as currently ineligible when checking their details.

The working tree was clean at the start. Earlier assignment/draft lifecycle changes were retained. No archive versions were used.

## B. Implemented features

- Officer sidebar, page heading, description and page title now say **Collection Reviews**. Collector terminology and both existing Officer routes remain unchanged.
- Pending Review, Verified, Rejected and All Reviews links show real creator-scoped totals. Tab totals cover all non-DRAFT submissions in scope, independent of search and pagination. The matching-result total is separate.
- Prepared search covers area, Collector name, `SCH-ID` and `#submissionID`. `%`, `_` and `!` are escaped as literal search characters. Four whitelisted sorts cover newest/oldest submission, collection date and submission identifier.
- Pagination serves at most 20 submissions, with stable identifier tie-breakers, clamped page numbers and retained search/status/sort parameters.
- Details show submission/schedule/area/date, submitting and currently assigned Collector, submission time, separate schedule/review statuses, grouped requests, pickup times/results, escaped notes, requested versus actual quantities, actual weights/conditions/risks/results, and item notes.
- Requested items without actual rows remain visible with a warning. Requests without items remain visible. Mislinked actual items are included with a warning rather than silently omitted. Integrity failures disable verification; POST independently validates again.
- Overview counts each request once and each associated actual item once. Actual quantity and measured weight come exclusively from collected item data. Weight uses integer thousandths to avoid floating-point summation. Missing measurements remain “Not recorded”; known totals with missing collected weights carry an incomplete warning.
- Pending submissions offer custom Verify and Reject dialogs. The reject dialog requires a reason; both support Cancel, Escape, native modal keyboard containment, focus restoration and a submission/loading guard. With no dialog support or JavaScript, decision triggers remain disabled. Both dialog forms use the original protected POST endpoint.
- Review metadata is the actual stored latest decision, actor, timestamp and note. Reviewed submissions have no decision controls. There is no fabricated decision-history log.

## C. Files changed

| File | Purpose |
|---|---|
| `app/Models/ScheduleCollection.php` | New `reviewQueue()`, review labels/sort whitelist and `overview()`; enhance `findForOfficer()`, `records()`, `assertComplete()` and `decide()` for totals, visible integrity concerns, historical COMPLETED requests, UTF-8 notes and HTTP-conflict signalling. Existing draft cleanup remains intact. |
| `app/Controllers/MunicipalOfficerController.php` | New `collectionReviewFilters()`; update `renderCollectionRecords()` and `reviewCollection()` for validated filters, pagination, retained validation notes, 409 conflicts, precise feedback and PRG to the resulting history status. |
| `app/Views/municipal_officer/collection-records.php` | Queue/history, search/sort/pagination, summaries, grouped items, integrity feedback, review metadata and protected dialog forms. |
| `app/Views/municipal_officer/layouts/sidebar.php` | Rename only the Officer navigation label. |
| `app/Views/municipal_officer/layouts/main.php` | Allow an escaped page title; other pages retain the existing Municipal Officer default. |
| `public/assets/css/municipal_officer/collection-records.css` | Existing-theme tabs, pagination, metadata, responsive dialog and loading/disabled styling. |
| `public/assets/js/municipal_officer/collection-records.js` | Replace native confirmation with modal open/cancel/focus/validation/submission behavior. |
| `tests/support/CollectionReviewFixture.php` | Controlled multi-request/item, cross-Officer, pending/history/DRAFT and multi-page fixtures. |
| `tests/collection-reviews.php` | Database/model/controller/security/aggregate/rollback/history/downstream eligibility regressions. Supports isolated browser fixture creation/deletion. |
| `tests/collection-review-concurrency.php` | Two-process Verify-versus-Reject contention with a held schedule-lock barrier; assert one winner and one 409 conflict. |
| `tests/collection-reviews-browser.cjs` | Real Chrome queue, details, modal, persistence, scope, CSRF, mobile and Collector rejection visibility checks. |
| `tests/run-all.php` | Register review integration/concurrency tests and the review browser test. |
| `docs/collection-reviews-implementation.md` | This audit, manual guide and examination notes. |

## D. Database policy

No schema changes or migrations were needed. No ENUM, FK, unique constraint, existing user record or SCH-4 data was changed. All mutating tests used disposable databases on an isolated `mysql:8.0` container, with no host data volume. The container is removed after verification.

No automatic repairs occur during listing/detail reads. Inconsistent data is shown and verification is refused. The existing historical-draft diagnostic/reconciliation guidance remains in `docs/collection-assignment-workflow-fix.md`.

## E. Exact state transitions

| Operation | Schedule status | Submission status | Pickup/item outcomes |
|---|---|---|---|
| Assign | CLOSED → ASSIGNED | No submission required | Unchanged |
| First valid draft save | ASSIGNED → IN_PROGRESS | DRAFT | Derived by existing Collector validation |
| Submit complete records | ASSIGNED/IN_PROGRESS → COLLECTION_SUBMITTED | DRAFT → PENDING | Preserved |
| Verify eligible pending submission | COLLECTION_SUBMITTED → COMPLETED | PENDING → VERIFIED | Preserved |
| Reject pending submission with reason | COLLECTION_SUBMITTED remains | PENDING → REJECTED | Preserved |
| Review already VERIFIED/REJECTED | No change; conflict | No change; conflict | Preserved |
| Final pristine draft deletion | IN_PROGRESS → ASSIGNED | Empty DRAFT parent removed | Existing final-record deletion rules |

Item result: actual quantity zero means NOT_COLLECTED; positive but less than requested means PARTIAL; equal to requested means COLLECTED. Per-request pickup result is the common item result when all agree, otherwise PARTIAL. Submission verification status is a separate Officer decision over the whole batch. Verification does not rewrite request status, pickup outcome, quantities or weights.

Rejection remains final/read-only under the existing workflow. Collectors can see the saved reason but cannot edit/delete/resubmit reviewed data. No reopening route was invented. Only VERIFIED collected/partial items are available through `ELot::getVerifiedPool()`; E-Lot verification and bidding retain their own separate eligibility rules.

## F. Test results and evidence

Actual runs used `DB_PORT=33316 DB_DATABASE=ecolot_review_test` against disposable MySQL. Browser tests used the installed Chrome binary and `PLAYWRIGHT_MODULE=/tmp/ecolot-workflow-browser/node_modules/playwright`. Each integration test creates/drops its own database; the base test database schema was initialized for existing smoke tests. No runtime library was added to the application.

| Category / command | Result | Evidence |
|---|---|---|
| `php -d session.save_path=/tmp tests/run-all.php` | PASS | Final complete PHP regression output ends `PASS: complete regression suite`; includes new review tests, assignment/draft races, Collector CRUD, schedule CRUD, public CRUD, seeds and escaping. |
| `php tests/collection-review-concurrency.php` | PASS | Two independent workers reach the schedule lock together; exactly one commits, the other reports a 409 conflict; source items are identical afterward. |
| `node tests/collection-reviews-browser.cjs` | PASS | Scoped queue, search/sort/pagination, grouped details/totals, mobile modal, Cancel/Escape/focus, persisted Verify/Reject, CSRF and Collector-visible reason. No page errors or native dialog calls. |
| `node tests/officer-browser.cjs` | PASS | Executed within the aggregate browser run; existing assignment/unassign modal, navigation, risk review and schedule flow passed. |
| `node tests/collector-browser.cjs` | PASS | Executed independently after the aggregate run stopped; existing draft create/edit/delete/recreate, submit, read-only, forged access, CSRF and mobile flow passed. |
| Changed PHP lint, Node syntax, `git diff --check` | PASS | All modified/new PHP and JS/CJS checked. |
| `php tests/run-all.php --browser` | FAIL, unrelated fixture assertion | PHP, Officer and review browser stages passed. Unchanged `tests/pickup-browser.cjs:55` expects two schedule rows after creating two; unchanged `tests/browser-fixture.php` loads existing demo schedules, producing eleven rows. Public Pickup browser stages after that assertion were not executed. |
| Existing `tests/recycler-bids-integration.php` with `ECOLOT_BID_TEST=1` in an isolated `ecolot_test_review_*` database | FAIL during fixture setup | Unchanged `database/demo/recycler-bids-fixture.php:26` inserts removed `authorized_recyclers.verified_by_admin_user_id`; MySQL reports unknown column. Bidding lifecycle assertions were not reached. No unrelated fixture/schema rewrite was made. |
| Live development DB/SCH-4 review or repair | NOT TESTED | All work used test databases; existing historical data was left untouched. |
| Exhaustive load/deadlock proof | NOT TESTED | Deterministic pairwise contention is exercised, not long-running production stress. |

Focused assertions also cover invalid IDs, arrays and filter values; literal wildcard/injection search; DRAFT exclusion; foreign-Officer reads/writes; invalid CSRF and roles; missing/whitespace/overlong/invalid-UTF-8 reasons; an accepted 500-character Sinhala reason; forged actor fields; verified/rejected history; missing actual rows; an item linked to another request; a request with no items; valid all-NOT_COLLECTED verification; and unknown weights remaining NULL.

A deliberately injected schedule-update failure after writing review metadata rolls the entire decision back to PENDING with no review actor/time. The expected test log `Collection review failed: 45000` is this injected failure, not an unhandled production error. Request rows, pickup records and actual items are independently compared before/after normal decisions.

### Aggregate arithmetic verification

The review fixture's first submission has these actual rows:

| Request | Item | Requested qty | Actual qty | Actual weight kg |
|---|---|---:|---:|---:|
| First | Laptop | 2 | 1 | 1.250 |
| First | Monitor | 2 | 0 | NULL |
| Second | Laptop | 2 | 2 | 2.375 |
| Third | Laptop | 2 | 0 | NULL |

Quantity: `Q = 1 + 0 + 2 + 0 = 3`.

Measured weight: `W = 1.250 + 2.375`. Integer-thousandth substitutions are `1 × 1000 + 250 = 1250` and `2 × 1000 + 375 = 2375`. Therefore `W = (1250 + 2375) / 1000 = 3625 / 1000 = 3.625 kg`.

Independent recalculation: the test performs SQL `SUM(actual_quantity)` and `SUM(actual_weight_kg)` over actual rows and compares both results with the PHP overview. The SQL total agrees exactly. NULL rows contribute no fabricated measurement.

Sanity checks: each actual quantity is between zero and its requested quantity. Requested quantity is `2 + 2 + 2 + 2 = 8`, so `0 ≤ 3 ≤ 8`. Request outcome counts are `1 COLLECTED + 1 PARTIAL + 1 NOT_COLLECTED = 3 saved requests`. A separate all-NOT_COLLECTED fixture keeps overview weight NULL and remains legitimately verifiable.

## G. Manual testing guide

Use disposable test submissions for consequential actions. Use existing authorized accounts; this guide does not prescribe development-account credentials.

The repository documents Docker at `http://localhost:8080` with an empty base path, and XAMPP at `http://localhost/EcoLot-LK/public`.

Docker URLs:

- Login: `http://localhost:8080/login`
- Default pending queue: `http://localhost:8080/officer/collection-records`
- Verified history: `http://localhost:8080/officer/collection-records?verification_status=VERIFIED`
- Rejected history: `http://localhost:8080/officer/collection-records?verification_status=REJECTED`
- All submitted history: `http://localhost:8080/officer/collection-records?verification_status=`

For XAMPP, prepend `/EcoLot-LK/public` to the same route paths, for example `http://localhost/EcoLot-LK/public/officer/collection-records`.

1. Sign in as the schedule's creating Municipal Officer. Open **Collection Reviews** in the sidebar. Confirm that Collector DRAFTs are absent.
2. Select each status. Search a real area/Collector or `SCH-` identifier. Change sort and use Next/Previous on a sufficiently large fixture. Confirm filter retention and that tab totals remain global to your scope.
3. Open **Review Submission**. Inspect every request and its items. Check that requested quantity, actual quantity, item result, pickup result and review status remain distinct.
4. Open Verify, cancel, reopen and press Escape. Confirm no decision changed. Reopen, optionally enter a note and confirm. The browser redirects to Verified history; refresh and confirm actor/time/note and COMPLETED schedule.
5. On another pending fixture, open Reject. Attempt an empty/whitespace reason; it must fail. Enter a reason and confirm. Refresh Rejected history and inspect stored metadata.
6. Sign in as the assigned Collector. Open the rejected collection record through Assigned Schedules. The rejection note must be visible and editing/deletion/submission must remain blocked.
7. In another session, sign in as a different Officer and attempt the first submission's URL. Expect 404. A replayed decision on a reviewed submission returns 409; an invalid CSRF POST returns 403.
8. At a narrow viewport, inspect scrollable tables and modal focus/buttons. A missing/inconsistent item fixture must remain visible and verification must be blocked; do not modify genuine saved records merely to run this test.

Automated fixture-only credentials exist in `tests/support/CollectionReviewFixture.php`; the browser harness creates and destroys those accounts in disposable databases. They are not claimed to be development credentials.

## H. Code-checker explanation

1. **Why Collection Reviews?** Collectors create records; Officers review whole submissions. Only the Officer-facing name changed.
2. **Schedule Collection vs Collection Record:** `schedule_collections` is one unique submission container per schedule. `collection_records` holds one request's pickup outcome inside that container.
3. **Pickup Result vs Verification Status:** COLLECTED/PARTIAL/NOT_COLLECTED describes collection outcomes. PENDING/VERIFIED/REJECTED describes Officer review of the parent batch. Neither replaces the other.
4. **Multiple requests:** `records()` queries every child where `cr.schedule_collection_id = :id`, then returns a PHP array grouped by request record.
5. **Multiple items:** items refer to `collection_record_id` and `request_item_id`; a grouping map attaches all matching rows to each record.
6. **JOINs:** parent queries join schedules, areas and Collector names. Requested-item queries JOIN catalogue/category information and LEFT JOIN actual rows so missing data remains visible. A UNION ALL adds actual rows wrongly linked to another request without duplicating valid rows.
7. **MVC:** the controller validates GET/POST, checks Auth/CSRF, calls the model and passes arrays to the view. The model owns SQL and rules; the view escapes and renders values. There is no SQL in the view.
8. **Verify:** `reviewCollection()` passes the authenticated Officer to `decide()`. It checks ownership and pending/schedule state under locks, validates every active request/item, conditionally saves VERIFIED/actor/time/note, updates COMPLETED and commits.
9. **Reject:** the same path validates a trimmed, valid UTF-8 nonempty reason of at most 500 characters, then saves REJECTED and review metadata while preserving source data and the schedule's COLLECTION_SUBMITTED state.
10. **Transactions/concurrency:** all cooperating collection writes lock the schedule first. The second review waits, rereads the updated parent and fails with conflict. If a later write fails, rollback undoes earlier metadata changes.
11. **CSRF/RBAC:** both methods require MUNICIPAL_OFFICER; POST validates the session token. The model checks creator ownership independently. Actor ID is taken from Auth, never hidden fields.
12. **History:** scoped retrieval excludes only DRAFT, so verified/rejected details stay accessible. Latest stored metadata is displayed; reviewed actions are absent and the backend still rejects replay.
13. **E-Lots:** `ELot::getVerifiedPool()` requires VERIFIED parents and collected/partial actual items. Rejecting does not release unverified material into that pool. E-Lot approval/bidding has its own rules and was not rewritten.

### Ten realistic examiner questions

| Question | Concise answer |
|---|---|
| Why not verify every collection record separately? | The schema stores verification on the schedule collection; records keep distinct pickup outcomes. |
| Can a PARTIAL or NOT_COLLECTED record be verified? | Yes. Verification checks truthful complete recording and integrity, not that every requested item was collected. |
| Why use LEFT JOIN for actual items? | INNER JOIN would hide a requested item whose actual record is missing. |
| Why not COUNT requests in one big item JOIN? | Multiple item rows multiply request rows. Requests are counted separately; actuals are summed once. |
| Why not convert NULL weight to zero? | NULL means no measurement. Zero would claim a measured value and hide missing data. |
| How do you stop SQL injection in sorting? | Only constant SQL fragments from `REVIEW_SORTS` are used; external search values are bound parameters. |
| Can another Officer change the same submission? | The schedule creator owns it. An out-of-scope Officer receives 404 even with a valid token. |
| What if Verify and Reject arrive together? | Schedule locking serializes them; after the winner commits, the other sees a non-PENDING state and receives 409. |
| What happens if schedule completion fails? | The transaction rolls back review metadata as well; an injected-failure regression proves this. |
| Can rejection reopen the Collector draft? | No. Existing project rules keep reviewed data read-only; the saved reason is visible, but no rework route was invented. |

## I. Remaining limitations

- The schema stores only the latest review decision. This implementation displays that metadata, not a fabricated timeline of multiple decisions.
- No municipality/area jurisdiction map exists; authorization follows the existing schedule-creator boundary.
- Rejection has no documented correction/reopen lifecycle, so it remains final. A future rework feature would need explicit rules for preserving review history.
- Full Public Pickup browser and downstream bidding lifecycle verification remain blocked by the unchanged stale fixtures described above. Focused review, inventory eligibility, assignment and Collector checks passed.
- Existing development records and historical mismatches were not inspected or repaired. Missing/inconsistent source data is reported rather than silently rewritten.
- Pairwise concurrency tests and responsive Chrome checks are not exhaustive load testing or coverage of every browser/assistive technology. JavaScript/dialog support is required for the custom decision UI; server-side authorization remains authoritative.
