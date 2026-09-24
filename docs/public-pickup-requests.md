# Public pickup requests

Public users create requests at `/user/new-request` and view, edit or cancel them at `/user/my-requests`. Existing `/my-requests/{id}/delete` POST URLs now cancel instead of physically deleting records. Request items and audit history remain available.

## Rules

- User identity and pickup address come from the authenticated session and database profile.
- A new schedule must belong to the user's postal area, be OPEN, remain before its cut-off and have spare capacity.
- Only SUBMITTED and PENDING_REVIEW requests on an OPEN schedule before its cut-off can be edited or cancelled. Active assignments or recorded collection work prevent changes.
- Items come from active database categories/items. DO_NOT_COLLECT items are excluded. Unknown submitted item names never create catalogue entries.
- Quantity must be a positive unsigned INT. Total row weight must be positive, fit DECIMAL(10,3), and use at most three decimal places. Notes are limited to 500 characters. Maximum 100 item rows per request.
- Weight means the combined estimated weight of all units in a row, not per-unit weight.
- An active condition-specific risk rule overrides default item risk. HIGH risk or REVIEW_REQUIRED collection policy sets request status PENDING_REVIEW and risk review status PENDING; otherwise SUBMITTED / NOT_REQUIRED. Editing recomputes review state.
- Create, edit and cancel share schedule locks with officer schedule changes. Schedule moves lock both schedules in ID order; owner/status checks are repeated under a request-row lock. Request and item writes commit together.
- Every public mutation requires CSRF. Successful mutations rotate the token, and validation errors retain a draft. Only the owner's records are loaded into history.
- Completed-pickup weight on the dashboard is explicitly labelled as estimated.

## Verification

Use a disposable test database (scripts create/drop their own database; no application data changes):

```sh
php -d session.save_path=/tmp tests/public-pickup-crud.php
php -d session.save_path=/tmp tests/public-pickup-pages.php
php -d session.save_path=/tmp tests/area-schedules.php
node tests/escaping.js
```

Browser check: create an item request, view its details, edit its quantity/condition, filter history, cancel through the confirmation popup, and verify the cancelled record remains. Invalid submissions should reopen the draft; no available schedule/catalogue/address should disable new submission.

This implements public request management and persisted risk flags. Officer review actions, collector workflow integration and notifications are separate features.
