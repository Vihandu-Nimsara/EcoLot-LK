# Final Collector collection workflow

Login → Dashboard → Assigned Schedules → Schedule Workspace → Request → Save Draft / View / Edit Draft / Delete Draft → Back to Schedule → Submit Schedule for Verification → Read-only.

## Scope and implementation

Only the Collector collection workflow was changed. My E-Lots, its model/view/JS/routes, Recycler bidding and Officer E-Lot verification remain untouched.

The dashboard uses the same ownership-filtered, indexed assignment/count query as the schedule list, without mutations or separate reporting joins. It renders zero, one or multiple schedules. In-progress assignments sort first, then assigned dates, then completed/submitted schedules. A next-assignment card and four workload summaries use that result.

Each workspace checks active assignment and collection-parent ownership, and queries requests for that schedule only. Cancelled/rejected requests do not require outcomes. Pending/rejected risk review cannot be collected and blocks submission of active requests. Missing/foreign resources return 404 with a back link; CSRF failures return 403.

Draft changes retain the existing PDO transactions and schedule/assignment/request locking. The first record creates or reuses the unique DRAFT schedule_collection and moves ASSIGNED to IN_PROGRESS. Item quantities determine the stored pickup result server-side; browser result fields are ignored. Zero quantity accepts zero/blank weight and normalizes condition to NULL. Positive quantities require positive weight and condition. Original public request data is unchanged.

Submission rechecks ownership, allowed schedule state, DRAFT parent, completeness, risk clearance and item validation. It atomically sets verification_status=PENDING, submitted_at, and schedule_status=COLLECTION_SUBMITTED. Backend create/update/delete/re-submit are blocked afterward.

## Routes

- GET /collector/dashboard
- GET /collector/schedules
- GET /collector/schedules/{id}
- GET /collector/requests/{id}
- POST /collector/collection-records
- GET /collector/collection-records/{id}
- POST /collector/collection-records/{id}/update
- POST /collector/collection-records/{id}/delete
- POST /collector/schedules/{id}/submit

Legacy /collector/my-requests and /collector/initial-request redirect to schedules; /collector/my-requests/{id} redirects to /collector/requests/{id}. E-Lot routes are unchanged.

## Database and seed

No schema, migration or seed edits in this delivery. The current schema already supports DRAFT and IN_PROGRESS. Existing older installations still need the repository's pre-existing collection-record migration. The existing seed has Collector 0773333333, multiple assignments, and a two-request draft/no-record demonstration. No seed was applied to the application database. Pre-existing seed modifications are preserved in the full archive but excluded from the changed-files archive.

## Verification actually run

PASS: Collector MySQL/controller regression, including zero/one/multiple schedule dashboards, schedule isolation, foreign schedule/request/record denial, draft CRUD, quantity/weight validation, server-derived result, zero-condition normalization, state transition, incomplete/review-pending submission denial, complete submission, read-only mutation blocks, CSRF, RBAC, escaping and original-request preservation.

PASS: Collector Chrome browser regression: login, dashboard, multiple assignments, correct workspace, request opening, create/reload/read/update/delete/recreate, complete submission, read-only UI, forged delete, CSRF, mobile navigation, legacy entry points, My E-Lots GET availability and logout. The E-Lot browser application was not changed or reimplemented.

PASS: PHP syntax across app/routes and Collector test; changed JS test syntax; git diff whitespace check; HTML escaping regression.

PASS within full regression run: authentication/session/role/rate limits, OTP lock test double, Officer Schedule CRUD, Public User CRUD/pages/concurrency, catalogue seed. Schedule index migration was run separately and passed.

LIMITATION: tests/run-all.php --browser stops at seed-compatibility.php. Its naive semicolon splitter breaks the current seed's quoted note beginning 'Resident was available'. A separate pickup-browser.cjs run also fails before browser launch in browser-fixture.php for the same reason. These unrelated fixtures/seed were intentionally not modified. Consequently the full suite and Public/Officer browser suite are not reported as passing; their PHP/MySQL CRUD regressions did pass.

## Packaging

The full ZIP contains repository source including existing seed data, without .git, runtime logs, dependencies or prior deliverables. The changed-files ZIP contains only this task's source changes plus a deletion manifest and a patch. Apply the listed deletions when installing it; extracting a ZIP alone cannot remove obsolete files.
