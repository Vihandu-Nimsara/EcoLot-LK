# Schedule and pickup CRUD verification

## Supported operations

Municipal officers create and list area schedules, view details, change capacity and allowed statuses, cancel eligible schedules, and permanently delete unused schedules. Dates, campaign and area stay immutable. Each campaign/area can have up to two non-cancelled schedules on different dates. The legacy index migration must be applied to older installations.

Public users create requests with catalogue items, view their own history/details, edit pending requests before cut-off/assignment, and cancel eligible requests while preserving history. Approved/final requests and closed/assigned work are protected. Cancellation frees request capacity. Officer cancellation also cancels outstanding requests atomically.

## Automated checks

Run from the project root using PHP with PDO MySQL and access to create/drop disposable databases:

```sh
php -d session.save_path=/tmp tests/area-schedules.php
php -d session.save_path=/tmp tests/public-pickup-crud.php
php -d session.save_path=/tmp tests/public-pickup-pages.php
php -d session.save_path=/tmp tests/pickup-concurrency.php
php -d session.save_path=/tmp tests/ewaste-catalogue-seed.php
php -d session.save_path=/tmp tests/seed-compatibility.php
php -d session.save_path=/tmp tests/schedule-index-migration.php
php -d session.save_path=/tmp tests/otp-locking.php
node tests/escaping.js
```

The concurrency test holds a schedule lock until both worker processes have read their item data and reached that lock. Both compete for the last slot. Exactly one succeeds. Request mutations use READ COMMITTED so waiting for a lock cannot leave eligibility checks reading a stale earlier snapshot.

## Browser regression

Requires Playwright and Chrome. Set PLAYWRIGHT_MODULE to the installed Playwright module path if not available through Node resolution. Optional PHP_BINARY and CHROME_PATH select alternative executables.

```sh
node tests/pickup-browser.cjs
```

The harness creates a disposable database, seeds test accounts, starts a temporary multi-worker PHP server, opens separate officer/public browser sessions, and removes the server/database afterwards. It exercises the real login forms, schedule popup, two-schedule limit, opening a schedule, public creation/details/edit/cancel, used-schedule delete protection, unused schedule deletion, history filters and mobile item popup. No application business records are modified. Login rate-limit accounting uses the existing application limiter.

SQL integration tests cover invalid inputs, statuses, ownership, role guards, CSRF, GET mutation rejection, output escaping, item rules, rollback, capacity and cancellation history. Collector/verification workflows and external notifications are outside these two CRUD modules.
