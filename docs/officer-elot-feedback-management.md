# Municipal Officer E-Lot and Feedback Management implementation audit

## A. Initial audit

The current repository, schema, controllers, related Collector/Public/Recycler models, Officer layouts, tests and workflow documentation were inspected. The working tree was clean. Earlier assignment and Collection Reviews changes were preserved.

| Feature before this task | Initial finding | Delivered result |
|---|---|---|
| E-Lot listing/details and source scope | WORKING, but limited status explanations | Retained real queries; add counts, bidding phases, source integrity messages, contextual actions and requested-versus-actual risk/quantity. |
| Collector submission feeding Officer queue | BROKEN on current schema | `ELot::submitCollectorLot()` referenced nonexistent `e_lots.submitted_at`; remove that unsupported assignment. Actual Collector creation/submission and rejected resubmission now run in tests. |
| Officer approval/rejection | WORKING backend, PARTIAL UI | Preserve rules; add custom confirmation, UTF-8/Unicode whitespace handling, conflict feedback and recorded management history. |
| Bid listing and Officer winner selection | WORKING backend, limited proof | Test real bids and lower eligible bid selection; clarify deadline semantics, guard existing winner and hide known-invalid actions. |
| Handover | PARTIAL | Existing status/date rules and completion trigger retained; stale rescheduling now requires the current version token. |
| Feedback listing/scope | WORKING, general area display BROKEN | General feedback now displays the submitter's actual area; counts and pagination agree with scoped data. |
| Feedback response/status | WORKING, history PARTIAL | Retain supported transitions and single current-response field; record before/after response/status snapshots atomically in existing audit storage. |
| Confirmation UI | PARTIAL; native `window.confirm()` | Shared accessible native HTML dialog; no browser confirm/prompt; cancellation, focus restoration and duplicate guard. |
| Summary counters | MISSING | Scoped counts independent of search/page, with clearly stated semantics. |
| Anonymous feedback, ratings, attachments | UNSUPPORTED | No invented fields, averages, priorities or anonymous identities. |
| Manual bidding close, automatic highest winner, no-bid rollover | UNSUPPORTED/UNSPECIFIED | No invented actions, database statuses or winner policy. |

Neither scoped module used fake authoritative data or localStorage. No unrelated module was rebuilt. Some documentation describes a future canonical licence-scope integration; the current operational bid/award implementation still uses the account/licence/category/risk rules described below. That future integration is not claimed as completed here.

## B. E-Lot implementation and actual lifecycle

An E-Lot allocates existing `collection_record_items` through `e_lot_items`. Its primary key on `record_item_id` prevents the same entire source item from appearing in two lots. There is no partial-allocation quantity column. Source collections must be VERIFIED; actual item result must be COLLECTED/PARTIAL; actual quantity/weight/condition/risk, category, Collector ownership, request identity and schedule identity are checked before approval and award.

An Officer may access a non-DRAFT lot only when it has source schedules and **every** source schedule belongs to that Officer. Mixed-owner lots fail closed. A source-less lot cannot establish Officer ownership and remains inaccessible; it is not silently assigned a jurisdiction.

### Transition matrix

| Current state | Supported action and preconditions | Result / affected records | Reversibility |
|---|---|---|---|
| DRAFT | Collector submits selected verified inventory; not an Officer action | PENDING_VERIFICATION | Existing Collector rules apply. |
| PENDING_VERIFICATION | Verify with valid source inventory, no bid/handover history, valid window opening no earlier than database time and closing after opening | OPEN_FOR_BIDDING; Officer/time/note and window stored; audit entry | No Officer reopen/unverify action. |
| PENDING_VERIFICATION | Reject with nonblank reason, at most 500 Unicode characters; no bid/handover history | REJECTED; actor/time/reason and audit; allocations retained | Existing Collector correction/resubmission remains supported. |
| REJECTED | Existing Collector resubmits; not an Officer action | PENDING_VERIFICATION | Requires a new Officer decision. Earlier recorded audit decisions remain. |
| OPEN_FOR_BIDDING, before opening | No premature award | State retained; display Bidding scheduled | No manual closing action. |
| OPEN_FOR_BIDDING, open ≤ now < close | Recyclers submit/revise/withdraw through existing services | Real bids retained; display Bidding open | Existing bid rules apply. |
| OPEN_FOR_BIDDING, now ≥ close | Officer chooses an eligible SUBMITTED bid for this lot; valid prior approval/window, no existing winner, current source and Recycler eligibility | Selected bid WINNING, other SUBMITTED bids REJECTED, actor/time on bid decisions; lot AWARDED; one PENDING handover and audit | No supported reassignment/repeated award. |
| OPEN_FOR_BIDDING, deadline passed, no eligible bids | No award | Data/history retained; explain that no eligible submitted bid is available | No rollover/cancel/reopen policy invented. |
| AWARDED + PENDING/SCHEDULED handover | Schedule/reschedule with a future Sri Lanka timestamp and the current handover version | Handover SCHEDULED; date/remarks/Officer updated; audit | Further scheduling permitted using the new version. |
| AWARDED + PENDING/SCHEDULED handover | Complete with time between award and now, valid winning bid, current handover version | Handover COMPLETED; existing database trigger marks lot COMPLETED; audit | Final. |
| COMPLETED/CANCELLED or final handover | View history only | No unsupported reopening/update | Read-only in this Officer feature. |

OPEN_FOR_BIDDING is the actual approved database state, including scheduled, live and deadline-passed bidding. Reaching the deadline does **not** rewrite the ENUM. The UI shows the time phase separately. There is no separate VERIFIED lot status or stored submission timestamp in this schema.

Winner selection remains the existing Officer choice among eligible bids, not an invented highest-price rule. The test intentionally selects an eligible lower bid while retaining correct financial DECIMAL values. Current award checks require an ACTIVE RECYCLER account, VERIFIED registration, a VALID unexpired licence, APPROVED matching category capability and high-risk permission when either requested/applied or actual source risk is HIGH. These checks are repeated under locks after the deadline; they do not require bidding still to be open.

The details page exposes real source submission/record/request/item IDs, area, schedule/date, requested and actual quantities, actual weights/conditions/results, applied and actual risk and item notes. Actual totals use separate source aggregates, so bid JOINs do not multiply them. NULL weight stays Not recorded; integrity concerns block approval/award rather than rewriting source data. Bid amounts remain DECIMAL strings for display.

## C. Feedback domain and lifecycle

Feedback is a named Public User's COMPLAINT, SUGGESTION or COMPLIMENT in `complaint_feedback`, optionally linked to that same user's request. It is not a rating system or an anonymous ticketing system. There are no attachment, subject, priority, assignee or rating fields.

Request-linked feedback belongs to the request schedule's creating Officer, with requester identity checked. Unclaimed general feedback is visible to Officers who have created schedules in the submitter's postal area. The first permitted update claims it through `reviewed_by_officer_user_id`; only that reviewer may continue. These existing scope rules are retained across list, counts, details, POST and audit-history reads.

| Current state | Allowed next state | Response requirement | Result |
|---|---|---|---|
| OPEN | IN_REVIEW | Optional | Claim/review metadata and latest response stored. |
| OPEN | RESOLVED or CLOSED | Required, nonblank | Decision, actor/time and response stored. |
| IN_REVIEW | RESOLVED or CLOSED | Required | Advance status and preserve prior response in audit. |
| RESOLVED | CLOSED | Required | Final closure; prior response retained in audit. |
| CLOSED | None | N/A | Read-only; no reopen/delete/escalate operation. |

Responses must be valid UTF-8, trimmed and at most 2000 characters, matching the existing application message policy. The base table still stores one current response. New management changes add actual before/after audit snapshots; this is not a fabricated historical thread. Existing latest responses are captured before replacement. Earlier unrecorded revisions cannot be reconstructed.

The Public feedback page already renders responses for its latest-ten list. The Chrome test updates a recent fixture entry and confirms the saved response through that unchanged Public UI. An initial test incorrectly used an older entry outside that list; it was corrected without changing Public code.

## D. Changed files

| File | Purpose |
|---|---|
| `app/Controllers/MunicipalOfficerController.php` | Update only shared Officer module methods: validated UTF-8 filters, clamped page/counters/title data, retained validation input, handover-version input and 409 conflict handling. Collection Reviews/Assignments logic unchanged. |
| `app/Models/OfficerELot.php` | Scoped counts/pagination/literal search, labels/time phases, source warnings, award readiness, existing-winner guard, stale handover version and transactional audit snapshots. |
| `app/Models/OfficerFeedback.php` | General area display, scoped counts/pagination/literal search, Unicode validation, feedback/context locks, conflict handling and response/status audit snapshots. |
| `app/Models/ELot.php` | Essential one-line SQL integration fix in `submitCollectorLot()`: remove nonexistent submitted_at assignment. No Collector UI or lifecycle redesign. |
| `app/Services/OfficerActionAudit.php` | Small shared prepared-statement writer/reader for real management events in existing `audit_logs`, with before/after JSON and actual actor/time. |
| `app/Views/municipal_officer/elots.php` | Clear labels/bidding phases, contextual review/award/handover controls, source traceability/risk/quantity/notes, version field and recorded history. |
| `app/Views/municipal_officer/feedback.php` | Management terminology, real message preview/context, readable statuses, protected response form and recorded history. |
| `app/Views/municipal_officer/module-filters.php` | Scoped counters, human labels, page totals and retained scalar validation inputs. |
| `app/Views/municipal_officer/module-confirmation.php` | Shared labelled custom confirmation dialog. |
| `app/Views/municipal_officer/module-history.php` | Render actual management entries and preserved response/review/handover snapshots. |
| `app/Views/municipal_officer/layouts/main.php` | Load shared management JS/CSS only on the two scoped pages. |
| `app/Views/municipal_officer/layouts/sidebar.php` | E-Lot Management and Feedback Management labels. |
| `public/assets/js/municipal_officer/module-management.js` | Feature checks, contextual form validation, confirmation/cancel/Escape/focus, loading state and duplicate-submit guard; original CSRF POST preserved. |
| `public/assets/css/municipal_officer/module-management.css` | Existing-theme counters, modal, history and responsive/disabled styles. |
| `public/assets/js/municipal_officer/elots.js` (deleted) | Superseded native-confirm handler, replaced by shared script. |
| `public/assets/js/municipal_officer/feedback.js` (deleted) | Superseded native-confirm handler, replaced by shared script. |
| `tests/support/OfficerManagementFixture.php` | Current-schema multi-source/Officer/Recycler/feedback fixtures using real Collector and review methods. |
| `tests/officer-management.php` | Real MySQL integration/controller/security/rollback/history/eligibility/regression assertions; disposable browser-fixture mode. |
| `tests/officer-management-concurrency.php` | Process-level races for review, award, handover revisions, feedback decisions and general-feedback claims. |
| `tests/officer-management-browser.cjs` | Real Chrome management workflows, confirmations, scope, responsive layout and Public response visibility. |
| `tests/run-all.php` | Register focused integration/concurrency/browser regressions. |
| `docs/officer-elot-feedback-management.md` | This audit, transitions, evidence and code-checker guide. |

## E. Database safety and compatibility

No schema, ENUM, unique constraint, FK or migration changed. Existing audit storage is reused. New audit inserts commit in the same transaction as the business decision; failure rolls back the entire update. No old audit entries, responses, bid rows, collection records or allocations are deleted. No historical audit events are backfilled or invented.

All data-changing tests used an isolated `mysql:8.0` container, loopback port 33316, no development volumes, and disposable test databases. The base test schema was initialized for existing smoke tests. No development records or SCH-4 data were changed. The temporary container is removed after testing.

The handover form now posts an `expected_handover` hash of the actual persisted version. Old/custom clients must reload the current form before saving; a missing/stale version is rejected safely. Existing routes remain unchanged.

## F. Security and concurrency

- Existing controllers enforce authenticated MUNICIPAL_OFFICER sessions and CSRF-protected POST. Acting identity always comes from Auth, never submitted actor fields.
- Resource access is enforced again in each model. Source-wide E-Lot ownership and feedback scope protect details, decisions and history; hidden buttons are not trusted.
- IDs/pages/filter enums/sort direction and field types are validated. Search parameters are prepared; literal wildcard escaping prevents `%`/`_` from silently broadening search. All displayed database text and retained form input is escaped.
- E-Lot decisions lock the lot first, consistent with existing bid mutations. Award checks lock the selected bid, account/registration/licence/capability rows, then source rows before rechecking eligibility. Database one-winner rules remain intact.
- Feedback decisions lock the feedback row first, then the request or public-profile row used for authorization, and reread the scoped record with READ COMMITTED. This stabilizes a related request move/profile-area change during the decision without locking every joined context row.
- Stale feedback transitions and handover revisions return conflict rather than overwriting a concurrent change. Repeated awards/lot reviews are blocked. Final records remain read-only.
- Custom dialogs use native HTML dialog focus containment, Cancel/Escape, explicit focus restoration and confirmation of the original protected form. Submitter values are preserved before disabling buttons; a synchronous guard blocks duplicate submissions. Unsupported JavaScript/dialog environments leave consequential buttons disabled.
- Safe controller errors expose no SQL text. An injected audit failure proves both E-Lot and feedback rollback, not a fake success response.

## G. Actual test results

Commands used `DB_PORT=33316 DB_DATABASE=ecolot_management_test`. Browser commands also used `PLAYWRIGHT_MODULE=/tmp/ecolot-workflow-browser/node_modules/playwright` and the installed Chrome binary. The new browser harness copies the application into a temporary directory so runtime rate-limit storage is isolated too; login and rate-limit enforcement still execute normally. A final repeated run initially reached the shared login limit; isolation corrected the harness without clearing runtime history or changing authentication. The temporary Playwright test dependency was not added to application source.

| Feature | Status | Executed evidence |
|---|---|---|
| E-Lot list/details/counts/search/pagination | PASS | `php tests/officer-management.php`; `node tests/officer-management-browser.cjs`; correct multi-item totals, literal injection/wildcard inputs, foreign/mixed-owner denial and second page. |
| E-Lot verification/rejection | PASS | Same tests persist review actor/time/note and window; reject invalid dates/reasons and repeated decisions; injected audit failure restores pending state. |
| Collector → Officer submission | PASS | Fixture uses `createCollectorLot()` and `submitCollectorLot()` against actual schema; rejected resubmission is also asserted. |
| Bidding management | PASS for supported actions | Real bids placed through unchanged `RecyclerBidService`; premature award blocked; details display genuine amounts/statuses; no manual close/reopen invented. |
| Winner selection | PASS | Eligible lower bid chosen deliberately; wrong-lot bid, suspended account, missing HIGH permission, repeated award and competing winners are rejected. Chrome confirms an actual award through the dialog. |
| Handover | PASS | Schedule, stale revision, complete and repeated completion checked; database trigger completion asserted. Chrome confirms completed handover and refresh. |
| Feedback listing/details | PASS | Real counts/pages/general area, linked request context, foreign/claimed scope denial and escaped XSS payloads. |
| Feedback actions/history | PASS | OPEN → IN_REVIEW → RESOLVED → CLOSED; response snapshots preserved; empty/Unicode-whitespace/overlong/invalid-UTF-8 input and reopening rejected; injected audit rollback checked. |
| Public feedback response | PASS | Recent fixture response appears after refresh in unchanged `/user/feedback`; latest-ten behavior respected. |
| Authorization / CSRF | PASS | Role subprocess guards, invalid token/type/filter checks, foreign/mixed-owner reads/writes and Chrome protected POST checks. |
| Concurrent management | PASS | `php tests/officer-management-concurrency.php`: held-row barrier and independent processes for Verify/Reject, winner selection, handover revision, feedback decision and two-Officer general-feedback claim. Exactly one compatible result, no observed SQL error/deadlock. |
| Full PHP regression suite | PASS | `php -d session.save_path=/tmp tests/run-all.php` ends `PASS: complete regression suite`; includes assignments, reviews, Collector/public CRUD, schedules, seeds and escaping. |
| Existing Officer browser | PASS | `node tests/officer-browser.cjs`: navigation, schedules, risk review and custom assignment/unassign dialog. |
| Existing Collection Reviews browser | PASS | `node tests/collection-reviews-browser.cjs`: complete review workflow unchanged. |
| Existing Collector browser | PASS | `node tests/collector-browser.cjs`: draft CRUD/submission, read-only rules, ownership, CSRF and mobile navigation. |
| PHP/JS syntax and diff whitespace | PASS | All changed/new PHP and JS/CJS checked; `git diff --check` passed. |
| Manual bid closing / automatic highest bidder / no-bid rollover | N/A | Not part of the established implementation; no policy or new action invented. |
| Handover quantity/evidence inputs, anonymous feedback, ratings, deletion/escalation/reopening | N/A | Unsupported schema/workflows; source quantities remain read-only. |
| Live development data and upgraded/custom schemas | NOT TESTED | Deliberately isolated tests; no historical repairs. |
| Exhaustive load / all browsers / full regulatory-scope reconciliation | NOT TESTED | Pairwise contention and Chrome only; future canonical policy integration remains separate. |

The existing aggregate `--browser` run and old opt-in bidding fixture were not used as this task's acceptance gates. Prior audits record their stale fixture assumptions. New Officer tests use the current schema and real bid service, avoiding neither assertions nor database rules. Public browser CRUD is regression-covered at PHP level and Public feedback in Chrome; the older standalone Public Pickup browser script was not rerun here.

### Quantity and weight self-check

The primary test lot contains two actual source items from different request records:

| Source | Requested quantity | Actual quantity | Actual weight kg |
|---|---:|---:|---:|
| First request/item | 2 | 1 | 1.250 |
| Second request/item | 2 | 2 | 2.375 |

Quantity: `Q = 1 + 2 = 3`.

Weight: `W = 1.250 + 2.375 = 3.625 kg`.

Independent recalculation in integer thousandths: first weight is `1 × 1000 + 250 = 1250`; second is `2 × 1000 + 375 = 2375`. Then `(1250 + 2375) / 1000 = 3625 / 1000 = 3.625 kg`, agreeing with the SQL DECIMAL aggregate and the rendered/browser assertion.

Sanity: requested total is `2 + 2 = 4`, and `0 ≤ 3 ≤ 4`; both actual quantities satisfy their individual bounds. Bid rows are not joined into this aggregate, so adding two bids does not multiply quantity or weight. The tests compare source item rows before/after Officer management to prove inventory was not changed.

## H. Manual test guide

Use disposable submissions for consequential decisions and existing authorized accounts; no development credentials are prescribed.

Docker URLs documented by the project:

- Login: `http://localhost:8080/login`
- E-Lot Management: `http://localhost:8080/officer/e-lots`
- Awaiting verification: `http://localhost:8080/officer/e-lots?status=PENDING_VERIFICATION`
- Approved/bidding: `http://localhost:8080/officer/e-lots?status=OPEN_FOR_BIDDING`
- Awaiting handover: `http://localhost:8080/officer/e-lots?status=AWARDED`
- Feedback Management: `http://localhost:8080/officer/feedback`
- Open feedback: `http://localhost:8080/officer/feedback?status=OPEN`
- Public feedback: `http://localhost:8080/user/feedback`

For the documented XAMPP setup, use `http://localhost/EcoLot-LK/public` followed by the same route paths, for example `http://localhost/EcoLot-LK/public/officer/e-lots`.

1. As Collector, create/submit an E-Lot from your VERIFIED collected source items. As their schedule-creating Officer, open E-Lot Management. Other Officers and mixed-owner sources must not be accessible.
2. Use status/category/search/sort and pagination. Confirm counts mean all scoped records before filtering, while matching-result totals follow filters.
3. Open Review E-Lot. Inspect source IDs, requested/actual quantities, measured weights, conditions, applied/actual risk and notes. Integrity warnings must not be “fixed” by changing genuine source data for the test.
4. Enter valid Sri Lanka bidding dates and optionally a note. Open verification, Cancel, reopen and Escape. No database change should occur. Confirm and refresh; inspect scheduled/open/deadline-passed phase and recorded decision.
5. On a separate pending fixture, reject with a reason. Refresh and inspect the reason/history; source allocations must remain intact. Existing Collector rejected resubmission should return it to pending review.
6. As eligible Recyclers, place genuine bids during the window using existing interfaces. Before the deadline, winner selection must be unavailable. After the deadline, select an eligible submitted bid for that lot, confirm and refresh. Inspect winning/losing bid statuses and the new pending handover.
7. Schedule a future handover or complete it between award time and now. Confirm and refresh. Completing must mark both handover and lot completed. A stale tab or repeated completion must not overwrite the current record.
8. As Officer, open request-linked or in-area general feedback. Inspect the full message and context. Save IN_REVIEW, then a nonblank RESOLVED response and CLOSED response. Confirm each and inspect the preserved response snapshots.
9. As the feedback's Public User, inspect a recent entry in `/user/feedback` and its saved response. Closed entries remain in history; no delete/reopen is offered.
10. Use another Officer session, invalid CSRF POST and a stale form to confirm 404/403/conflict behavior. At a narrow viewport, check scrollable tables and keyboard dialog navigation.

Browser fixture-only accounts/passwords are explicitly created in `tests/support/OfficerManagementFixture.php` and its reused fixture, then destroyed by the harness. They are not claimed to be development credentials.

## I. Sinhala-English code-checker guide

1. **E-Lot:** me project eke E-Lot kiyanne verified actual collected items group ekak. `e_lots` header eka; `e_lot_items` allocation links.
2. **Collection relationship:** source path eka E-Lot item → collection_record_items → collection_records → schedule_collections → schedule. `OfficerELot::sources()` e path eka JOIN karanawa.
3. **Officer responsibility:** submitted lot inspect karala verify/reject, bidding window set karala, deadline passe eligible winner select karala, handover record karanawa.
4. **Verification:** `OfficerELot::act()` lot lock karala scope/source/state recheck karanawa; valid dates samaga OPEN_FOR_BIDDING + Officer metadata save karanawa.
5. **Statuses:** DRAFT → PENDING_VERIFICATION → OPEN_FOR_BIDDING → AWARDED → COMPLETED. Pending lot reject unoth REJECTED; existing Collector flow eken resubmit karanna puluwan.
6. **Bidding:** DB open/close timestamps saha current DB time compare karanawa. Deadline pass unath ENUM eka automatically CLOSED wenne na; UI phase eka separate.
7. **Winner:** Officer eligible submitted bid ekak select karanawa. Highest amount automatically winner kiyala rule ekak invent karala na. Same lot association, account/licence/capability/risk check karanawa.
8. **Handover:** winning bid primary reference eka. Date/status/remarks/Officer save karanawa. COMPLETED handover trigger eken lot COMPLETED wenawa. Version hash eka stale tab overwrite eka nawaththanawa; eka permission token ekak newei.
9. **Feedback:** Public User complaint/suggestion/compliment message ekak; optional request link ekak. Anonymous/rating fields na.
10. **Officer management:** `OfficerFeedback::review()` allowed status transition saha response validate karala actor/time save karanawa; CLOSED final.
11. **Feedback relations:** feedback.public_user_id → public profile; optional request_id → request → schedule → creating Officer. General feedback profile area eken scope karanawa; first reviewer claim karanawa.
12. **MVC:** controller Auth/CSRF/inputs handle karanawa; model SQL/rules/transactions handle karanawa; view escaped arrays render karanawa. View eke SQL na.
13. **JOINs:** source IDs/names/context related tables walin gannawa. Feedback optional request nathi unath display karanna LEFT JOIN use karanawa.
14. **PDO:** user values `:id`, `:status`, `:message` wage parameters walata bind karanawa. Sort direction validated choices walin witharai SQL ekata yanney.
15. **Transactions:** award + bid outcomes + handover + audit eka unit ekak. Any failure nam okkoma rollback; half-award ekak ithuru wenne na.
16. **CSRF/RBAC:** POST form token session token ekata validate wenawa; MUNICIPAL_OFFICER role check karanawa; model resource ownership independently check karanawa.
17. **Invalid actions:** state/date/source/eligibility/expected-version checks fail unoth exception + rollback. Disabled button eka alone security newei.
18. **Consistency:** existing unique/FK/trigger rules preserve karanawa. Audit snapshots actual changes preserve karanawa; original collection quantities, source rows and feedback user message edit karanne na.

### Ten E-Lot examiner questions

| Question | Concise answer |
|---|---|
| Why can an Officer not see every Collector lot? | Every source schedule must belong to that Officer; mixed-owner lots fail closed. |
| Why require VERIFIED collections? | Draft/rejected actuals have not passed the parent collection review and cannot be treated as verified inventory. |
| Can the same source item enter two lots? | The existing e_lot_items primary key on record_item_id prevents duplicate whole-item allocation. |
| Are requested and actual quantities interchangeable? | No. Requested quantities bound validation; source totals use actual collected quantities. |
| What does OPEN_FOR_BIDDING mean before the opening time? | Approved with a stored window; the computed phase is Bidding scheduled. |
| Why not choose the largest amount automatically? | Existing Officer selection is manual among eligible bids; no highest-price mandate was established. |
| What if a Recycler loses eligibility after bidding? | Award rechecks current account, registration, licence, capability and risk permission under locks. |
| How is a second winner prevented? | Lot serialization, current state/existing-winner checks and existing database one-winner rules. |
| Why does completion use a handover trigger? | The schema's established business rule marks the lot completed when its winning handover becomes COMPLETED. |
| What changed outside Officer code? | Only the shared Collector submission SQL reference to nonexistent submitted_at was removed to feed the Officer queue. |

### Ten Feedback examiner questions

| Question | Concise answer |
|---|---|
| Is this anonymous feedback? | No. public_user_id is required and references a real public profile; no anonymity flag exists. |
| Is every feedback message a complaint? | No. COMPLAINT, SUGGESTION and COMPLIMENT are the supported types. |
| Can feedback exist without a request? | Yes. request_id is nullable; general feedback is scoped by the Public User's area until claimed. |
| How does the first Officer claim general feedback? | A permitted update records reviewed_by_officer_user_id under the feedback lock; later scope restricts other Officers. |
| Can another Officer read the response history directly? | No route exposes it independently; model details must pass feedback scope before loading audit entries. |
| Must every update contain a response? | Resolution and closure require one; starting IN_REVIEW permits an empty response under existing rules. |
| Does a new response erase the old one? | The base row stores the latest response; the transactional audit records actual before/after snapshots for new changes. |
| Can CLOSED feedback be reopened? | No supported transition or route permits that. |
| Why validate both Unicode encoding and length? | Malformed text is rejected and the application limit applies to characters; whitespace-only resolution/closure is also rejected. |
| Does resolving feedback complete a collection or E-Lot? | No. Feedback updates affect feedback and its audit entries only. |

## J. Remaining limitations and unresolved policies

- No no-bid cancellation/rollover/manual close/automatic ranking policy was established. Those actions remain unsupported instead of fabricated.
- Current operational award eligibility is the existing account/verified-registration/valid-licence/category/high-risk contract. `docs/recycler-authorization-foundation-implementation.md` describes a separate future canonical document/scope/activity integration and missing lot required-activity producer. This task does not invent those activities or claim full regulatory-scope reconciliation. The existing Recycler bid engine and database bid guards remain unchanged.
- Source-less lots cannot prove Officer jurisdiction. Historical inconsistent or mixed-owner records require a separately reviewed maintenance decision; this code does not silently repair/reassign them.
- Feedback uses current request/area scope. If historical linked ownership or scope is changed outside the supported Officer workflow, administrative reconciliation may be required; no automatic transfer/escalation policy was invented.
- Audit entries begin with actual management changes made through this implementation and capture the preceding latest values. Earlier unrecorded history cannot be reconstructed. The Public page still displays its existing latest-ten feedback history.
- There is no separate E-Lot submission-time column, reserve price, handover quantity/evidence field, rating or anonymous-feedback setting. These were not invented.
- Custom decision UI requires JavaScript and dialog/requestSubmit support. Browser coverage used Chrome; accessibility behavior beyond keyboard/focus and exhaustive multi-browser/load testing is not claimed.
- No development or custom upgraded database was inspected/repaired. Existing stale opt-in test fixtures documented in earlier audits were left outside this implementation's scope.
