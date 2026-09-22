# Area collection schedules

The Municipal Officer schedule page now reads and writes MySQL through the existing plain-PHP MVC code. It uses the existing layout, forms, buttons, session authentication, CSRF helper and PDO base model. The create form appears below the table only after clicking Create Schedule (or after validation errors), so the whole CRUD works without JavaScript. JavaScript only filters already-rendered rows.

## 1. Files changed or created

Paths below are relative to the repository root. Existing unfinished changes to MonthlyCampaign, PostalCodeArea, MunicipalOfficerController, routes, and AreaCollectionSchedule were retained and completed.

| File | Purpose |
| --- | --- |
| `app/Controllers/MunicipalOfficerController.php` | List/detail actions, create/edit/delete validation, flash notices, transaction coordination |
| `app/Models/AreaCollectionSchedule.php` (new) | Joined reads, counts, dependency checks, locks, manual transitions and available public schedules |
| `app/Models/MonthlyCampaign.php` | Existing new `openCampaigns()` method supplies database dropdown options |
| `app/Models/PostalCodeArea.php` | Existing new `activeAreas()` method supplies database dropdown options |
| `routes/web.php` | GET list/details and POST create/update/delete routes |
| `app/Views/municipal_officer/area-schedules.php` | Escaped database table and five-field create form |
| `app/Views/municipal_officer/area-schedule-details.php` (new) | Details, restricted edit form, protected delete form |
| `public/assets/js/municipal_officer/area-schedules.js` | Removes demo/localStorage persistence; keeps campaign filtering |
| `public/assets/css/municipal_officer/area-schedules.css` | Small additions for details, inline forms and validation messages |
| `app/Controllers/PublicUserController.php` | Loads the logged-in user's real postal area and available schedules |
| `app/Views/public_user/pickup-request-form.php` | Uses eligible database schedules instead of a free date field and removes the fake address/area |
| `app/Models/EWasteRequest.php` | Guards inserts with schedule eligibility checks and the same schedule lock used by officer edits |
| `app/Core/Database.php` | Sets the connection time zone to +05:30, matching schema DATETIME conventions |
| `tests/area-schedules.php` (new) | Integration tests using a disposable database |
| `docs/area-collection-schedules.md` (new) | This walkthrough and manual test checklist |

## 2. GET and POST routes

GET retrieves information. POST requests change database state:

| Method | Path | Controller action |
| --- | --- | --- |
| GET | `/officer/area-schedules` | `areaSchedules` |
| POST | `/officer/area-schedules` | `storeAreaSchedule` |
| GET | `/officer/area-schedules/{id}` | `showAreaSchedule` |
| POST | `/officer/area-schedules/{id}/update` | `updateAreaSchedule` |
| POST | `/officer/area-schedules/{id}/delete` | `deleteAreaSchedule` |

The existing router already supports `{id}`. It passes the matched text to the controller, which validates it as a positive integer. Missing or invalid schedule IDs return 404. GET requests to update/delete return 405. Every action first calls `Auth::requireRole('MUNICIPAL_OFFICER')`.

## 3. How the create form sends data

The form uses `method="post"` and the existing configured base path. The browser sends campaign ID, postal area ID, maximum requests, cutoff date, collection date, and a hidden CSRF token. No creator or status input is offered. The Create Schedule link loads the page with `?create=1` and scrolls to the form. Cancel returns to the list with the form hidden; no script intercepts submission.

## 4. How POST reaches the controller

`public/index.php` loads the router and dispatches using the HTTP method and URL. PHP has already placed form fields in `$_POST`. `storeAreaSchedule()` reads only the allowed fields using the existing `postString()` helper. Array-shaped malicious inputs become empty strings and fail validation. Values posted under extra field names cannot set creator, initial status, or immutable edit fields.

## 5. Server-side validation

Positive IDs and capacities are checked before casting. Capacity must fit the existing unsigned INT column. Dates must exactly round-trip through `DateTimeImmutable::createFromFormat('!Y-m-d', ...)`, so an impossible date cannot silently roll into another month. This is stricter than the generic Validator's `strtotime` date rule, and avoids changing validation in unrelated modules.

The controller then checks database facts: campaign exists and is OPEN, area exists and is ACTIVE, collection belongs to the campaign month/year, collection is not before today in Sri Lanka, cutoff is strictly earlier than collection, no duplicate date exists, and there are fewer than two non-cancelled schedules for that campaign/area pair.

Validation errors render with HTTP 422 and the entered values. A campaign/area that has become unavailable is deliberately absent from the selectable options. An invalid submitted status is not added back as an allowed option. CSRF failure returns 403 and requires reloading the form.

## 6. Loading campaigns and areas

`MonthlyCampaign::openCampaigns()` uses `WHERE campaign_status = 'OPEN'`. `PostalCodeArea::activeAreas()` uses `WHERE area_status = 'ACTIVE'`. The controller passes their arrays into the view. The view loops over them, using IDs as option values and escaped names as labels. The controller rechecks the selected records because options can become stale and requests can be forged.

## 7. How the model talks to MySQL

`AreaCollectionSchedule` inherits the existing `Model` and its PDO connection. Its SQL uses bound parameters, not user input concatenated into SQL. Generic `find`, `create`, `update` and `delete` remain inherited. Schedule-specific methods perform joined reads, counts and dependency checks.

A transaction groups validation reads and the mutation. Creation locks the campaign row first, including when no schedule exists yet. This serializes competing creates for that campaign, making the two-per-area count reliable. It is a deliberately simple, slightly broader lock than a per-area lock. It also locks the selected area while validating its status. Edits/deletes lock the schedule. Request inserts through `EWasteRequest::create()` use that same schedule lock so a capacity edit and a new request cannot both act on stale capacity. Transactions commit on success and roll back on exceptions.

## 8. INSERT

After validation, the controller builds an explicit array for inherited `Model::create()`. The creator comes from `Auth::id()`, the status is always `PLANNED`, and a date-only cutoff receives `23:59:59`. PDO builds the INSERT with placeholders. MySQL supplies `schedule_id` and `created_at`. A success flash is stored and a 303 redirect returns to the list, avoiding accidental resubmission on refresh.

## 9. SELECT, JOIN and COUNT

The list and details queries join schedules to campaigns, areas and users, so the screen shows names rather than unexplained IDs. A correlated COUNT counts requests except CANCELLED and REJECTED. COMPLETED requests still count because the specified exclusion has only those two statuses. This avoids multiplying counts when a schedule has multiple assignments or collection records.

Details show campaign/month, postal code/area, dates, maximum and current requests, remaining capacity, status, creator and creation time. Remaining capacity is capacity minus the active count, displayed with a zero floor for legacy inconsistent records. Every database string is HTML-escaped, including attributes and option labels.

## 10. UPDATE

The controller reloads and locks the current schedule before checking the submitted capacity and transition. Capacity must be positive and at least the current active request count. It passes exactly `request_capacity` and `schedule_status` to `Model::update()`. Forged campaign, area, date, cutoff and creator fields are ignored. Keeping the current status allows capacity-only edits, including workflow-controlled statuses.

## 11. Protected DELETE

Delete uses a POST form with a CSRF token. The controller locks the schedule and checks all requests, all assignments (including historical/unassigned ones), and all schedule collection submissions. Cancelled/rejected requests still block deletion because they prove prior use. Only a record with no dependencies reaches inherited `Model::delete()`. Database errors return a safe message rather than exposing SQL or credentials.

## 12. Foreign keys

The schema already links campaigns, postal areas and officer profiles to schedules. An authenticated officer therefore also needs a valid `municipal_officers` profile. Requests, assignments and schedule collections reference schedules with `ON DELETE RESTRICT`. These constraints are a second line of protection against deleting used records, including a concurrent write. The unique campaign/area/date constraint also backs up application duplicate validation. It includes CANCELLED records: cancellation frees a slot in the two-schedule limit, but does not free the same date.

## 13. CSRF

The existing `Csrf::token()` stores a random secret in the session and renders it in a hidden form field. `hasValidCsrfToken()` calls the existing constant-time validator. Every schedule mutation checks it after role authentication and before database writes. A token proves the form belongs to this session; it does not replace authorization or input validation.

## 14. Status transitions and public availability

`AreaCollectionSchedule::manualStatuses()` is shared by the form and controller:

- PLANNED may become OPEN or CANCELLED.
- OPEN may become CLOSED or CANCELLED.
- CLOSED may become OPEN only while the cutoff has not passed.
- ASSIGNED, IN_PROGRESS, COLLECTION_SUBMITTED, COMPLETED and CANCELLED have no manual transition.

The current status is also accepted so capacity can change without moving workflow state. The normal edit form cannot advance collector/verification operations. PLANNED → OPEN is permitted even if cutoff has already passed, as specified; this still does not make the schedule publicly available.

Public availability independently requires OPEN, current time at or before the cutoff, active count below capacity, and the same postal area as the user's database profile. The list query, request model guard and existing database INSERT trigger support those conditions. All new date comparisons use Sri Lanka time; the PDO session now uses the same time zone so MySQL CURRENT_TIMESTAMP agrees.

**Existing boundary:** the public pickup submission screen still explicitly says it is a prototype and has no POST submission endpoint. This task connects its schedule options and adds the request model guard; it does not implement item submission, review, or public request history. No public request is falsely presented as saved.

## 15. MVC responsibilities

The router selects the action. The controller authenticates, reads inputs, applies business rules and chooses a response. The model owns SQL, database locks and schedule queries. The views render escaped data and forms. JavaScript enhances filtering only. This keeps persistence and rules on the server while retaining the existing architecture.

## Database setup

No table, enum, index, or trigger migration is required for a database matching `database/schema.sql`. Existing schema already includes the required unique key and restrictions. Do not re-import seed data into an existing database just for this feature. The runtime connection time-zone setting needs no persisted database change.

## Automated verification

From the project root:

```sh
/Applications/XAMPP/xamppfiles/bin/php -d session.save_path=/tmp tests/run.php
/Applications/XAMPP/xamppfiles/bin/php -d session.save_path=/tmp tests/area-schedules.php
node tests/escaping.js
node --check public/assets/js/municipal_officer/area-schedules.js
```

The integration test uses configured database credentials to create a random `ecolot_schedule_test_*` database, imports the schema there, exercises actual controller/model operations, and drops that database in `finally`. It requires CREATE/DROP DATABASE privileges. It does not add fixtures to the application database. Redirects are intercepted only in the test controller so successful requests can be inspected without terminating the test process. Separate PHP processes exercise real role guards.

## Manual test checklist

Use test accounts and a future OPEN monthly campaign with active areas. Campaign management remains its existing module, so ensure the campaign exists in MySQL rather than only in its browser demo.

1. Log in as a Municipal Officer; load the list and create a schedule. Refresh and reopen in another browser session: it should persist and start PLANNED.
2. Confirm only OPEN campaigns and ACTIVE areas appear. Submit forged IDs, a closed campaign and inactive area: all must fail.
3. Try zero, negative, fractional and oversized capacity, invalid dates, past collection, wrong campaign month/year, and cutoff on/after collection. Expect errors and retained valid inputs.
4. Create two different dates for one area/campaign. A third must fail. Two schedules in a different area must be allowed. Cancel one of the original pair and create a new date; the cancelled date itself must still fail as a duplicate.
5. Open details. Check creator, creation time, dates, count and remaining capacity against MySQL. Verify names containing `<`, `>` or quotes display as text.
6. Edit capacity. With existing active requests, lowering below the count must fail; equality must succeed. Cancelled/rejected requests should not affect that count.
7. Exercise PLANNED → OPEN/CANCELLED, OPEN → CLOSED/CANCELLED, CLOSED → OPEN before cutoff; reject reopening after cutoff and all other transitions. Workflow states should display read-only.
8. Forge immutable fields in an update POST. Campaign, area, dates and creator must remain unchanged.
9. Permanently delete an unused schedule. Try deleting one with a cancelled request, a historical assignment or a collection submission; each must fail.
10. Remove/change the CSRF token on each mutation; expect 403 and no write. Try as a guest and PUBLIC_USER; expect login redirect or 403. GET to a delete/update URL must not mutate anything.
11. Log in as a public user. Only their own area's OPEN schedules before cutoff with remaining capacity should appear. Full, planned, cancelled and expired schedules must be absent. Public submission itself remains the existing explicit prototype.
12. In two officer sessions, attempt simultaneous creates when one slot remains. At most one should succeed. Repeat a capacity edit while a test request is inserted through the request model: capacity must not fall below accepted requests.
13. Disable JavaScript and repeat officer CRUD. Forms should still submit and persist; only table filtering is unavailable.
